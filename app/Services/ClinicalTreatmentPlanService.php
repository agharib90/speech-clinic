<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientClinicalEvaluation;
use App\Models\PatientServicePlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicalTreatmentPlanService
{
    public function __construct(private readonly PatientServicePlanPricingService $pricingService) {}

    public function saveDraft(
        Patient $patient,
        PatientClinicalEvaluation $evaluation,
        User $user,
        array $items
    ): PatientServicePlan {
        return DB::transaction(function () use ($patient, $evaluation, $user, $items) {
            Patient::query()->lockForUpdate()->findOrFail($patient->id);
            $lockedEvaluation = PatientClinicalEvaluation::query()->lockForUpdate()->findOrFail($evaluation->id);

            abort_unless($lockedEvaluation->patient_id === $patient->id, 404);

            if (! $lockedEvaluation->isCompleted()) {
                throw ValidationException::withMessages([
                    'evaluation' => 'يجب إكمال التقييم السريري قبل إعداد الخطة العلاجية.',
                ]);
            }

            $plans = PatientServicePlan::query()
                ->where('clinical_evaluation_id', $lockedEvaluation->id)
                ->lockForUpdate()
                ->get();

            if ($plans->contains(fn (PatientServicePlan $plan) => $plan->isClinicallyApproved())) {
                throw ValidationException::withMessages([
                    'plan' => 'تم اعتماد الخطة السريرية ولا يمكن تعديل خدماتها أو كمياتها.',
                ]);
            }

            $plan = $plans->first() ?: PatientServicePlan::create([
                'patient_id' => $patient->id,
                'clinical_evaluation_id' => $lockedEvaluation->id,
                'status' => PatientServicePlan::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            if ($plan->status !== PatientServicePlan::STATUS_DRAFT || $plan->hasProtectedHistory()) {
                throw ValidationException::withMessages([
                    'plan' => 'لا يمكن تعديل هذه الخطة من المسار السريري بعد تقدمها تشغيليًا.',
                ]);
            }

            $preparedItems = $this->pricingService->prepareNewItems($items, false);
            $plan->items()->delete();

            foreach ($preparedItems as $item) {
                $plan->items()->create($item);
            }

            return $plan->fresh('items.service');
        });
    }

    public function approve(
        Patient $patient,
        PatientClinicalEvaluation $evaluation,
        ?PatientServicePlan $plan,
        User $user,
        array $items
    ): PatientServicePlan {
        return DB::transaction(function () use ($patient, $evaluation, $plan, $user, $items) {
            Patient::query()->lockForUpdate()->findOrFail($patient->id);
            $lockedEvaluation = PatientClinicalEvaluation::query()->lockForUpdate()->findOrFail($evaluation->id);

            abort_unless($lockedEvaluation->patient_id === $patient->id, 404);

            $plans = PatientServicePlan::query()
                ->where('clinical_evaluation_id', $lockedEvaluation->id)
                ->lockForUpdate()
                ->get();
            $lockedPlan = $plan
                ? $plans->firstWhere('id', $plan->id)
                : $plans->first();

            if ($plan) {
                abort_unless($plan->patient_id === $patient->id, 404);
                abort_unless($plan->clinical_evaluation_id === $lockedEvaluation->id, 404);
                abort_unless($lockedPlan !== null, 404);
            }

            if ($lockedPlan?->isClinicallyApproved()) {
                return $lockedPlan;
            }

            if (! $lockedEvaluation->isCompleted()) {
                throw ValidationException::withMessages([
                    'evaluation' => 'لا يمكن اعتماد الخطة قبل إكمال تقييم الحالة.',
                ]);
            }

            $lockedPlan ??= PatientServicePlan::create([
                'patient_id' => $patient->id,
                'clinical_evaluation_id' => $lockedEvaluation->id,
                'status' => PatientServicePlan::STATUS_DRAFT,
                'created_by' => $user->id,
            ]);

            if ($lockedPlan->status !== PatientServicePlan::STATUS_DRAFT || $lockedPlan->hasProtectedHistory()) {
                throw ValidationException::withMessages([
                    'plan' => 'لا يمكن اعتماد خطة سريرية متقدمة تشغيليًا.',
                ]);
            }

            $preparedItems = $this->pricingService->prepareNewItems($items, false);
            $lockedPlan->items()->delete();

            foreach ($preparedItems as $item) {
                $lockedPlan->items()->create($item);
            }

            $lockedPlan->update([
                'clinical_approved_by' => $user->id,
                'clinical_approved_at' => now(),
            ]);

            return $lockedPlan->fresh();
        });
    }
}
