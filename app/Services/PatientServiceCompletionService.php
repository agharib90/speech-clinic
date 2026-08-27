<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\PatientCheckin;
use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Therapist;
use App\Models\TherapistEarning;
use App\Models\TherapistServiceRate;
use App\Models\TherapyProgram;
use App\Models\TherapySession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientServiceCompletionService
{
    public function complete(Appointment $appointment, User $actor): TherapySession
    {
        return DB::transaction(function () use ($appointment, $actor) {
            $lockedAppointment = Appointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->id);

            $this->authorize($lockedAppointment, $actor);

            if (! $lockedAppointment->patient_service_plan_item_id) {
                $this->fail('هذا الموعد لا يرتبط بخطة خدمات ويجب إتمامه من المسار القديم.');
            }

            if ($lockedAppointment->status === 'مكتمل') {
                $session = TherapySession::query()
                    ->where('appointment_id', $lockedAppointment->id)
                    ->first();

                if ($session) {
                    return $session;
                }

                $this->fail('حالة الموعد المكتمل غير متسقة مع سجل التنفيذ.');
            }

            if ($lockedAppointment->status !== 'مجدول') {
                $this->fail('حالة الموعد لا تسمح بإتمام الخدمة.');
            }

            if (! $lockedAppointment->financially_confirmed_at) {
                $this->fail('لا يمكن إتمام موعد غير مؤكد ماليًا.');
            }

            if (! PatientCheckin::query()
                ->where('appointment_id', $lockedAppointment->id)
                ->where('patient_id', $lockedAppointment->patient_id)
                ->exists()) {
                $this->fail('يجب تأكيد حضور هذا الموعد من الاستقبال قبل إتمام الخدمة.');
            }

            if (! $lockedAppointment->end_at) {
                $this->fail('تعذر تحديد وقت انتهاء الموعد.');
            }

            if (now()->lt($lockedAppointment->end_at)) {
                $this->fail('لا يمكن إتمام الخدمة قبل انتهاء وقت الموعد.');
            }

            $itemReference = PatientServicePlanItem::query()
                ->findOrFail($lockedAppointment->patient_service_plan_item_id);
            $plan = PatientServicePlan::query()
                ->lockForUpdate()
                ->findOrFail($itemReference->patient_service_plan_id);
            $item = PatientServicePlanItem::query()
                ->with('service')
                ->lockForUpdate()
                ->findOrFail($itemReference->id);

            if ((int) $plan->patient_id !== (int) $lockedAppointment->patient_id
                || (int) $item->patient_service_plan_id !== (int) $plan->id) {
                $this->fail('بيانات الموعد لا تتوافق مع خطة خدمات المريض.');
            }

            if ($plan->status !== PatientServicePlan::STATUS_ACTIVE) {
                $this->fail('يجب أن تكون خطة الخدمات نشطة لإتمام الخدمة.');
            }

            if ($item->consumed_quantity >= $item->planned_quantity) {
                $this->fail('تم استنفاد الكمية المخططة لهذه الخدمة.');
            }

            if ($item->consumed_quantity >= $item->authorized_quantity) {
                $this->fail('لا توجد وحدة ممولة بالكامل ومتاحة لإتمام هذه الخدمة.');
            }

            if (! $item->service) {
                $this->fail('تعذر تحديد الخدمة المرتبطة بالموعد.');
            }

            $therapist = Therapist::query()
                ->where('user_id', $lockedAppointment->therapist_id)
                ->where('is_active', true)
                ->whereHas('services', fn ($query) => $query->where('services.id', $item->service_id))
                ->lockForUpdate()
                ->first();

            if (! $therapist) {
                $this->fail('الأخصائي غير نشط أو غير مسند إلى هذه الخدمة.');
            }

            $rate = TherapistServiceRate::resolveFor(
                $therapist,
                $item->service,
                $lockedAppointment->scheduled_at
            );

            if (! $rate) {
                $this->fail('لا يوجد سعر استحقاق صالح للأخصائي في تاريخ تنفيذ الخدمة.');
            }

            $program = $this->lockedProgram($lockedAppointment, $item);
            $sessionNumber = (int) TherapySession::query()
                ->where('therapy_program_id', $program->id)
                ->max('session_number') + 1;
            $duration = max(
                1,
                (int) $lockedAppointment->scheduled_at->diffInMinutes($lockedAppointment->end_at)
            );

            $session = TherapySession::create([
                'therapy_program_id' => $program->id,
                'appointment_id' => $lockedAppointment->id,
                'session_date' => $lockedAppointment->scheduled_at->toDateString(),
                'session_number' => $sessionNumber,
                'duration_minutes' => $duration,
                'status' => 'مكتملة',
            ]);

            TherapistEarning::create([
                'therapist_id' => $therapist->id,
                'therapy_session_id' => $session->id,
                'amount' => $rate->amount,
                'type' => 'session_fee',
            ]);

            $item->increment('consumed_quantity');
            $lockedAppointment->update(['status' => 'مكتمل']);

            return $session;
        });
    }

    public function state(Appointment $appointment, User $actor): array
    {
        if (! $appointment->patient_service_plan_item_id) {
            return $this->stateResult('legacy', 'موعد قديم');
        }

        if (! $actor->can('edit therapy')
            || ($actor->hasRole('أخصائي تخاطب') && (int) $appointment->therapist_id !== (int) $actor->id)) {
            return $this->stateResult('unauthorized', 'غير متاح ضمن صلاحياتك');
        }

        if ($appointment->status === 'مكتمل') {
            return $this->stateResult('completed', 'مكتمل');
        }

        if ($appointment->status !== 'مجدول') {
            return $this->stateResult('ineligible', 'حالة الموعد لا تسمح بالإتمام');
        }

        if (! $appointment->financially_confirmed_at) {
            return $this->stateResult('unconfirmed', 'غير مؤكد ماليًا');
        }

        if (! $appointment->checkin
            || (int) $appointment->checkin->patient_id !== (int) $appointment->patient_id) {
            return $this->stateResult('attendance_required', 'لم يحضر بعد');
        }

        $item = $appointment->patientServicePlanItem;
        if (! $item || ! $item->plan || (int) $item->plan->patient_id !== (int) $appointment->patient_id
            || $item->plan->status !== PatientServicePlan::STATUS_ACTIVE) {
            return $this->stateResult('plan_unavailable', 'خطة الخدمة غير متاحة للتنفيذ');
        }

        if ($item->consumed_quantity >= $item->planned_quantity) {
            return $this->stateResult('exhausted', 'تم استنفاد وحدات الخدمة');
        }

        if ($item->consumed_quantity >= $item->authorized_quantity) {
            return $this->stateResult('funding_required', 'غير ممول بالكامل للتنفيذ');
        }

        $therapist = Therapist::query()
            ->where('user_id', $appointment->therapist_id)
            ->where('is_active', true)
            ->whereHas('services', fn ($query) => $query->where('services.id', $item->service_id))
            ->first();

        if (! $therapist || ! $item->service
            || ! TherapistServiceRate::resolveFor($therapist, $item->service, $appointment->scheduled_at)) {
            return $this->stateResult('rate_required', 'استحقاق الأخصائي غير مكتمل');
        }

        if (! $appointment->end_at) {
            return $this->stateResult('end_time_required', 'تعذر تحديد وقت انتهاء الموعد');
        }

        if (now()->lt($appointment->end_at)) {
            return $this->stateResult('in_progress', 'يمكن إتمام الخدمة بعد انتهاء الموعد');
        }

        return $this->stateResult('ready', 'جاهز لإتمام الخدمة', true);
    }

    private function authorize(Appointment $appointment, User $actor): void
    {
        abort_unless($actor->can('edit therapy'), 403);

        if ($actor->hasRole('أخصائي تخاطب')) {
            abort_unless((int) $appointment->therapist_id === (int) $actor->id, 403);
        }
    }

    private function lockedProgram(
        Appointment $appointment,
        PatientServicePlanItem $item
    ): TherapyProgram {
        $program = TherapyProgram::query()
            ->where('patient_id', $appointment->patient_id)
            ->where('therapist_id', $appointment->therapist_id)
            ->where('status', TherapyProgram::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first();

        return $program ?: TherapyProgram::create([
            'patient_id' => $appointment->patient_id,
            'therapist_id' => $appointment->therapist_id,
            'name' => $item->service->name,
            'status' => TherapyProgram::STATUS_ACTIVE,
            'start_date' => $appointment->scheduled_at->toDateString(),
            'session_price' => $item->final_unit_price,
        ]);
    }

    private function stateResult(string $code, string $label, bool $canComplete = false): array
    {
        return [
            'code' => $code,
            'label' => $label,
            'can_complete' => $canComplete,
        ];
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['completion' => $message]);
    }
}
