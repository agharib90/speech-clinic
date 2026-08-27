<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Models\Guardian;
use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientClinicalEvaluationAssignment;
use App\Models\PatientServicePlan;
use App\Services\PatientClinicalStageResolver;
use App\Services\PatientWorkspaceBuilder;
use App\Support\PatientAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    public function index(Request $request, PatientClinicalStageResolver $stageResolver)
    {
        $search = $request->input('search');
        $stage = $request->string('stage')->toString();
        $stage = array_key_exists($stage, PatientClinicalStageResolver::FILTERS) ? $stage : '';
        $query = Patient::with([
            'guardian',
            'clinicalEvaluationAssignments' => fn ($assignment) => $assignment
                ->whereIn('status', PatientClinicalEvaluationAssignment::OPEN_STATUSES)
                ->with('assignee'),
            'clinicalEvaluations.servicePlans',
            'servicePlans',
        ]);
        PatientAccess::scope($query, $request->user());

        if ($stage) {
            $stageResolver->applyFilter($query, $stage);
        }

        $patients = $query
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhereHas('guardian', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            })->latest()->paginate(10)->withQueryString();

        $patients->getCollection()->each(function (Patient $patient) use ($request, $stageResolver) {
            $assignment = $patient->clinicalEvaluationAssignments->sortByDesc('id')->first();
            $evaluation = $patient->clinicalEvaluations
                ->where('status', PatientClinicalEvaluation::STATUS_DRAFT)
                ->sortByDesc('id')
                ->first()
                ?: $patient->clinicalEvaluations
                    ->where('status', PatientClinicalEvaluation::STATUS_COMPLETED)
                    ->sortByDesc(fn ($item) => $item->completed_at?->timestamp ?? $item->id)
                    ->first();
            if ($assignment?->isPending() && ! $assignment->clinical_evaluation_id) {
                $evaluation = null;
            }
            $clinicalPlan = $evaluation?->servicePlans->sortByDesc('id')->first();
            $approvedPlan = $evaluation?->servicePlans
                ->whereNotNull('clinical_approved_at')
                ->sortByDesc(fn ($plan) => $plan->clinical_approved_at?->timestamp ?? $plan->id)
                ->first();
            $activePlan = $patient->servicePlans
                ->where('status', PatientServicePlan::STATUS_ACTIVE)
                ->sortByDesc('id')
                ->first();
            $legacyDraftPlan = $patient->servicePlans
                ->where('status', PatientServicePlan::STATUS_DRAFT)
                ->whereNull('clinical_evaluation_id')
                ->sortByDesc('id')
                ->first();

            $patient->setAttribute('clinical_stage', $stageResolver->resolve(
                $request->user(), $assignment, $evaluation, $clinicalPlan, $approvedPlan, $activePlan, $legacyDraftPlan
            ));
        });

        return view('patients.index', compact('patients', 'stage'));
    }

    public function create(Request $request)
    {
        $selectedGuardian = null;
        $duplicateGuardian = null;

        if ($guardianId = $request->old('guardian_id')) {
            $selectedGuardian = Guardian::query()
                ->select(['id', 'name', 'phone'])
                ->find($guardianId);
        }

        if ($request->old('guardian_mode') === 'new' && $phone = $request->old('guardian.phone')) {
            $duplicateGuardian = Guardian::query()
                ->select(['id', 'name', 'phone'])
                ->withCount('patients')
                ->where('phone', $phone)
                ->first();
        }

        return view('patients.create', compact('selectedGuardian', 'duplicateGuardian'));
    }

    public function guardianSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $search = trim($validated['query']);

        $guardians = Guardian::query()
            ->select(['id', 'name', 'phone'])
            ->withCount('patients')
            ->where(function ($query) use ($search) {
                $query->whereRaw('INSTR(name, ?) > 0', [$search])
                    ->orWhereRaw('INSTR(phone, ?) > 0', [$search]);
            })
            ->orderBy('name')
            ->limit(10)
            ->get();

        return response()->json(['data' => $guardians]);
    }

    public function store(StorePatientRequest $request)
    {
        $data = $request->validated();
        $patientData = Arr::only($data, [
            'name',
            'birth_date',
            'gender',
            'diagnosis',
            'referral_source',
            'notes',
        ]);
        $patientData['is_active'] = $request->has('is_active');

        $patient = DB::transaction(function () use ($data, $patientData) {
            if ($data['guardian_mode'] === 'new') {
                $duplicateGuardian = Guardian::query()
                    ->where('phone', $data['guardian']['phone'])
                    ->lockForUpdate()
                    ->first();

                if ($duplicateGuardian) {
                    throw ValidationException::withMessages([
                        'guardian.phone' => 'رقم الهاتف مسجل لولي أمر موجود. ابحث عنه واختر سجله بدلًا من إنشاء سجل مكرر.',
                    ]);
                }

                $guardian = Guardian::create($data['guardian']);
                $patientData['guardian_id'] = $guardian->id;
            } else {
                $patientData['guardian_id'] = $data['guardian_id'];
            }

            $patientData['barcode'] = 'TMP-'.Str::uuid();
            $patientData['qr_code'] = $patientData['barcode'];

            $patient = Patient::create($patientData);
            $barcode = 'PAT-'.str_pad($patient->id, 5, '0', STR_PAD_LEFT);

            $patient->update([
                'barcode' => $barcode,
                'qr_code' => $barcode,
            ]);

            return $patient;
        });

        return redirect()->route('patients.workspace', $patient)
            ->with('success', 'تم حفظ الحالة وتوليد الباركود بنجاح');
    }

    public function show(Patient $patient)
    {
        PatientAccess::authorize($patient, auth()->user());

        $patient->load('guardian', 'caseHistory.takenBy', 'therapyPrograms.therapist');

        return view('patients.show', compact('patient'));
    }

    public function workspace(Patient $patient, PatientWorkspaceBuilder $workspaceBuilder)
    {
        PatientAccess::authorize($patient, auth()->user());

        $workspace = $workspaceBuilder->build($patient, auth()->user());

        return view('patients.workspace', compact('patient', 'workspace'));
    }

    public function edit(Patient $patient)
    {
        PatientAccess::authorize($patient, auth()->user());

        $guardians = Guardian::pluck('name', 'id');

        return view('patients.edit', compact('patient', 'guardians'));
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        PatientAccess::authorize($patient, auth()->user());

        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        $patient->update($data);

        return redirect()->route('patients.index')
            ->with('success', 'تم تحديث بيانات الطفل بنجاح');
    }

    public function destroy(Patient $patient)
    {
        PatientAccess::authorize($patient, auth()->user());

        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'تم حذف الطفل بنجاح');
    }

    // دالة طباعة البطاقة
    public function printCard(Patient $patient)
    {
        PatientAccess::authorize($patient, auth()->user());

        $patient->load('guardian');

        return view('patients.print-card', compact('patient'));
    }
}
