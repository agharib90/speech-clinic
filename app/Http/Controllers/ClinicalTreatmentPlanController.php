<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientServicePlan;
use App\Services\ClinicalTreatmentPlanService;
use App\Support\PatientAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClinicalTreatmentPlanController extends Controller
{
    private function planRules(Patient $patient): array
    {
        return [
            'clinical_evaluation_id' => [
                'required',
                'integer',
                Rule::exists('patient_clinical_evaluations', 'id')
                    ->where(fn ($query) => $query->where('patient_id', $patient->id)),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('services', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'items.*.position' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.planned_quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function saveDraft(
        Request $request,
        Patient $patient,
        ClinicalTreatmentPlanService $planService
    ) {
        PatientAccess::authorize($patient, $request->user());
        $data = $request->validate($this->planRules($patient));

        $evaluation = PatientClinicalEvaluation::findOrFail($data['clinical_evaluation_id']);
        $planService->saveDraft($patient, $evaluation, $request->user(), $data['items']);

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم حفظ مسودة الخطة العلاجية السريرية.');
    }

    public function approve(
        Request $request,
        Patient $patient,
        PatientServicePlan $patientServicePlan,
        ClinicalTreatmentPlanService $planService
    ) {
        PatientAccess::authorize($patient, $request->user());
        $data = $request->validate($this->planRules($patient));
        $evaluation = PatientClinicalEvaluation::findOrFail($data['clinical_evaluation_id']);
        $planService->approve(
            $patient,
            $evaluation,
            $patientServicePlan,
            $request->user(),
            $data['items']
        );

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم اعتماد الخطة وإرسالها للاستقبال.');
    }

    public function approveNew(
        Request $request,
        Patient $patient,
        ClinicalTreatmentPlanService $planService
    ) {
        PatientAccess::authorize($patient, $request->user());
        $data = $request->validate($this->planRules($patient));
        $evaluation = PatientClinicalEvaluation::findOrFail($data['clinical_evaluation_id']);
        $planService->approve($patient, $evaluation, null, $request->user(), $data['items']);

        return redirect()->route('patients.workspace', ['patient' => $patient, 'section' => 'clinical'])
            ->with('success', 'تم اعتماد الخطة وإرسالها للاستقبال.');
    }
}
