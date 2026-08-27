<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientClinicalEvaluationAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientClinicalEvaluationService
{
    public function saveDraft(Patient $patient, User $user, ?string $clinicalSummary): PatientClinicalEvaluation
    {
        return DB::transaction(function () use ($patient, $user, $clinicalSummary) {
            Patient::query()->lockForUpdate()->findOrFail($patient->id);

            $assignment = PatientClinicalEvaluationAssignment::query()
                ->where('patient_id', $patient->id)
                ->whereIn('status', PatientClinicalEvaluationAssignment::OPEN_STATUSES)
                ->lockForUpdate()
                ->first();

            if ($assignment && $assignment->assigned_to !== $user->id) {
                throw ValidationException::withMessages([
                    'clinical_summary' => 'التقييم مسند إلى مختص سريري آخر.',
                ]);
            }

            $evaluation = PatientClinicalEvaluation::query()
                ->where('patient_id', $patient->id)
                ->where('status', PatientClinicalEvaluation::STATUS_DRAFT)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($evaluation && $evaluation->evaluated_by !== null && $evaluation->evaluated_by !== $user->id) {
                throw ValidationException::withMessages([
                    'clinical_summary' => 'توجد مسودة تقييم حالية يملكها مستخدم سريري آخر.',
                ]);
            }

            if (! $evaluation) {
                if (! $assignment && ! $user->can('edit patients')) {
                    throw ValidationException::withMessages([
                        'clinical_summary' => 'يجب إسناد تقييم جديد إليك من الاستقبال قبل بدء دورة تقييم جديدة.',
                    ]);
                }

                $evaluation = PatientClinicalEvaluation::create([
                    'patient_id' => $patient->id,
                    'status' => PatientClinicalEvaluation::STATUS_DRAFT,
                    'evaluated_by' => $user->id,
                ]);
            }

            $evaluation->update(['clinical_summary' => $clinicalSummary]);

            if ($assignment) {
                if ($assignment->clinical_evaluation_id !== null
                    && $assignment->clinical_evaluation_id !== $evaluation->id) {
                    throw ValidationException::withMessages([
                        'clinical_summary' => 'إسناد التقييم مرتبط بتقييم سريري آخر.',
                    ]);
                }

                $assignment->update([
                    'clinical_evaluation_id' => $evaluation->id,
                    'status' => PatientClinicalEvaluationAssignment::STATUS_IN_PROGRESS,
                    'started_at' => $assignment->started_at ?: now(),
                ]);
            }

            return $evaluation->fresh();
        });
    }

    public function complete(
        Patient $patient,
        PatientClinicalEvaluation $evaluation,
        User $user,
        ?string $clinicalSummary
    ): PatientClinicalEvaluation {
        return DB::transaction(function () use ($patient, $evaluation, $user, $clinicalSummary) {
            Patient::query()->lockForUpdate()->findOrFail($patient->id);
            $lockedEvaluation = PatientClinicalEvaluation::query()->lockForUpdate()->findOrFail($evaluation->id);

            abort_unless($lockedEvaluation->patient_id === $patient->id, 404);

            if ($lockedEvaluation->evaluated_by !== null && $lockedEvaluation->evaluated_by !== $user->id) {
                throw ValidationException::withMessages([
                    'clinical_summary' => 'لا يمكن إكمال مسودة تقييم يملكها مستخدم سريري آخر.',
                ]);
            }

            if ($lockedEvaluation->isCompleted()) {
                return $lockedEvaluation;
            }

            $assignment = PatientClinicalEvaluationAssignment::query()
                ->where('patient_id', $patient->id)
                ->where('clinical_evaluation_id', $lockedEvaluation->id)
                ->where('status', PatientClinicalEvaluationAssignment::STATUS_IN_PROGRESS)
                ->lockForUpdate()
                ->first();

            if ($assignment && $assignment->assigned_to !== $user->id) {
                throw ValidationException::withMessages([
                    'clinical_summary' => 'لا يمكن إكمال تقييم مسند إلى مختص سريري آخر.',
                ]);
            }

            $lockedEvaluation->update([
                'clinical_summary' => $clinicalSummary,
                'status' => PatientClinicalEvaluation::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
            ]);

            $assignment?->update([
                'status' => PatientClinicalEvaluationAssignment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            return $lockedEvaluation->fresh();
        });
    }
}
