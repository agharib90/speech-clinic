<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Setting;
use App\Models\Therapist;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentServicePlanBookingService
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability
    ) {}

    public function depositPercentage(): int
    {
        $percentage = Setting::query()->value('appointment_confirmation_deposit_percentage');

        return is_numeric($percentage) && (int) $percentage >= 1 && (int) $percentage <= 100
            ? (int) $percentage
            : 50;
    }

    public function requiredDepositCents(PatientServicePlanItem $item, ?int $percentage = null): int
    {
        $percentage ??= $this->depositPercentage();
        $unitPriceCents = PatientServicePlanAllocator::decimalToCents($item->final_unit_price);

        return intdiv(($unitPriceCents * $percentage) + 99, 100);
    }

    public function eligibility(PatientServicePlanItem $item): array
    {
        $item->loadMissing('plan', 'service');
        $percentage = $this->depositPercentage();
        $requiredCents = $this->requiredDepositCents($item, $percentage);
        $openAppointments = $this->openConfirmedAppointments($item);
        $fundedUnconsumedUnits = max(0, $item->authorized_quantity - $item->consumed_quantity);
        $remainingPlannedUnits = max(0, $item->planned_quantity - $item->consumed_quantity);
        $availableCreditCents = PatientServicePlanAllocator::decimalToCents($item->plan->availableCredit());
        $nextUnfundedItemId = $item->plan->nextUnfundedItem()?->id;

        $hasFullSlot = $openAppointments < $fundedUnconsumedUnits;
        $hasPartialSlot = $openAppointments === $fundedUnconsumedUnits
            && $nextUnfundedItemId === $item->id
            && $availableCreditCents >= $requiredCents;
        $withinPlanLimit = $openAppointments < $remainingPlannedUnits;

        return [
            'can_book' => $withinPlanLimit && ($hasFullSlot || $hasPartialSlot),
            'funding_type' => $hasFullSlot ? 'full' : ($hasPartialSlot ? 'deposit' : null),
            'open_appointments' => $openAppointments,
            'funded_unconsumed_units' => $fundedUnconsumedUnits,
            'remaining_planned_units' => $remainingPlannedUnits,
            'available_credit_cents' => $availableCreditCents,
            'required_deposit_cents' => $requiredCents,
            'percentage' => $percentage,
        ];
    }

    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $itemReference = PatientServicePlanItem::query()->findOrFail($data['patient_service_plan_item_id']);
            $plan = PatientServicePlan::query()->lockForUpdate()->findOrFail($itemReference->patient_service_plan_id);
            $item = PatientServicePlanItem::query()->lockForUpdate()->findOrFail($itemReference->id);
            $item->setRelation('plan', $plan);
            $item->load('service.specialty');

            $this->validatePlanItem($item, (int) $data['patient_id']);
            $therapist = $this->validatedTherapist($item, (int) $data['therapist_id']);
            User::query()->whereKey($therapist->user_id)->lockForUpdate()->firstOrFail();

            $eligibility = $this->eligibility($item);
            if (! $eligibility['can_book']) {
                $required = PatientServicePlanAllocator::centsToDecimal($eligibility['required_deposit_cents']);

                throw ValidationException::withMessages([
                    'patient_service_plan_item_id' => "الرصيد غير كافٍ أو لا توجد وحدة متاحة. يلزم توفر {$required} ج.م على الأقل لتأكيد هذا الموعد.",
                ]);
            }

            $duration = (int) $item->service->default_duration_minutes;
            if ($duration < 1) {
                throw ValidationException::withMessages([
                    'patient_service_plan_item_id' => 'يجب على الإدارة تحديد مدة الخدمة قبل حجز موعد لها.',
                ]);
            }

            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $endAt = $scheduledAt->copy()->addMinutes($duration);

            if (! $this->availability->isWithinWorkPeriod($therapist, $scheduledAt, $duration)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'الموعد المختار خارج فترات عمل الأخصائي أو لا يتسع لمدة الخدمة.',
                ]);
            }

            if ($this->hasConflict((int) $therapist->user_id, $scheduledAt, $endAt)) {
                throw ValidationException::withMessages([
                    'therapist_id' => 'يوجد تعارض في المواعيد، الأخصائي لديه موعد آخر في هذا الوقت.',
                ]);
            }

            return Appointment::create([
                'patient_id' => $plan->patient_id,
                'therapist_id' => $therapist->user_id,
                'session_type_id' => null,
                'patient_service_plan_item_id' => $item->id,
                'scheduled_at' => $scheduledAt,
                'end_at' => $endAt,
                'status' => 'مجدول',
                'notes' => $data['notes'] ?? null,
                'confirmation_deposit_percentage_snapshot' => $eligibility['percentage'],
                'confirmation_deposit_amount_snapshot' => PatientServicePlanAllocator::centsToDecimal(
                    $eligibility['required_deposit_cents']
                ),
                'financially_confirmed_at' => now(),
            ]);
        });
    }

    private function validatePlanItem(PatientServicePlanItem $item, int $patientId): void
    {
        if ($item->plan->status !== PatientServicePlan::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'يجب أن تكون خطة الخدمات نشطة لحجز الموعد.',
            ]);
        }

        if ((int) $item->plan->patient_id !== $patientId) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'الخدمة المختارة لا تخص المريض المحدد.',
            ]);
        }

        if (! $item->service || ! $item->service->is_active) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'الخدمة المختارة غير متاحة للحجز.',
            ]);
        }

        if ($item->consumed_quantity >= $item->planned_quantity) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'تم استنفاد الكمية المخططة لهذه الخدمة.',
            ]);
        }
    }

    private function validatedTherapist(PatientServicePlanItem $item, int $therapistUserId): Therapist
    {
        $therapist = Therapist::query()
            ->where('user_id', $therapistUserId)
            ->where('is_active', true)
            ->whereHas('services', fn ($query) => $query->where('services.id', $item->service_id))
            ->lockForUpdate()
            ->first();

        if (! $therapist) {
            throw ValidationException::withMessages([
                'therapist_id' => 'الأخصائي المختار غير نشط أو غير مسند إلى هذه الخدمة.',
            ]);
        }

        return $therapist;
    }

    private function openConfirmedAppointments(PatientServicePlanItem $item): int
    {
        return Appointment::query()
            ->where('patient_service_plan_item_id', $item->id)
            ->where('status', 'مجدول')
            ->whereNotNull('financially_confirmed_at')
            ->lockForUpdate()
            ->count();
    }

    private function hasConflict(int $therapistUserId, Carbon $scheduledAt, Carbon $endAt): bool
    {
        return Appointment::query()
            ->where('therapist_id', $therapistUserId)
            ->where('status', '!=', 'ملغى')
            ->where('scheduled_at', '<', $endAt)
            ->where('end_at', '>', $scheduledAt)
            ->lockForUpdate()
            ->exists();
    }
}
