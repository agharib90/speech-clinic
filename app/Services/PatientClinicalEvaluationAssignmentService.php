<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientClinicalEvaluationAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientClinicalEvaluationAssignmentService
{
    public function eligibleClinicians(): Collection
    {
        return User::query()
            ->permission('manage clinical evaluations')
            ->with('therapistIncludingTrashed')
            ->where(function ($query) {
                $query->whereDoesntHave('therapistIncludingTrashed')
                    ->orWhereHas('therapist', fn ($therapist) => $therapist->where('is_active', true));
            })
            ->orderBy('name')
            ->get();
    }

    public function assign(Patient $patient, User $clinician, User $assigner): PatientClinicalEvaluationAssignment
    {
        return DB::transaction(function () use ($patient, $clinician, $assigner) {
            Patient::query()->lockForUpdate()->findOrFail($patient->id);
            $candidate = User::query()->lockForUpdate()->findOrFail($clinician->id);

            if (! $this->isEligible($candidate)) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'المستخدم المحدد غير مؤهل لإجراء التقييم السريري.',
                ]);
            }

            $openAssignments = PatientClinicalEvaluationAssignment::query()
                ->where('patient_id', $patient->id)
                ->whereIn('status', PatientClinicalEvaluationAssignment::OPEN_STATUSES)
                ->lockForUpdate()
                ->get();

            $draftEvaluation = PatientClinicalEvaluation::query()
                ->with('evaluator:id,name')
                ->where('patient_id', $patient->id)
                ->where('status', PatientClinicalEvaluation::STATUS_DRAFT)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $linkedInProgressAssignment = $draftEvaluation && $openAssignments->contains(
                fn (PatientClinicalEvaluationAssignment $assignment) => $assignment->isInProgress()
                    && $assignment->clinical_evaluation_id === $draftEvaluation->id
            );

            if ($draftEvaluation && ! $linkedInProgressAssignment) {
                $owner = $draftEvaluation->evaluator?->name;

                throw ValidationException::withMessages([
                    'assigned_to' => $owner
                        ? "يوجد تقييم سريري قيد الاستكمال بالفعل بواسطة {$owner}."
                        : 'يوجد تقييم سريري قيد الاستكمال بالفعل ويجب حسمه قبل إنشاء إسناد جديد.',
                ]);
            }

            if ($openAssignments->contains->isInProgress()) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'لا يمكن تغيير المختص بعد بدء التقييم.',
                ]);
            }

            $pending = $openAssignments->first();

            if ($pending?->assigned_to === $candidate->id) {
                return $pending;
            }

            $openAssignments->each->update([
                'status' => PatientClinicalEvaluationAssignment::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            return PatientClinicalEvaluationAssignment::create([
                'patient_id' => $patient->id,
                'assigned_to' => $candidate->id,
                'assigned_by' => $assigner->id,
                'status' => PatientClinicalEvaluationAssignment::STATUS_PENDING,
                'assigned_at' => now(),
            ]);
        });
    }

    private function isEligible(User $user): bool
    {
        if (! $user->can('manage clinical evaluations')) {
            return false;
        }

        $therapist = $user->therapistIncludingTrashed()->first();

        return ! $therapist || (! $therapist->trashed() && (bool) $therapist->is_active);
    }
}
