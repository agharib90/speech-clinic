<?php

namespace App\Http\Controllers;

use App\Models\PatientServicePlan;
use App\Models\PatientServicePlanItem;
use App\Models\Therapist;
use App\Services\AppointmentAvailabilityService;
use App\Services\AppointmentServicePlanBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentAvailabilityController extends Controller
{
    public function __invoke(
        Request $request,
        AppointmentAvailabilityService $availability,
        AppointmentServicePlanBookingService $bookingService
    ): JsonResponse {
        $data = $request->validate([
            'patient_service_plan_item_id' => ['required', 'integer', 'exists:patient_service_plan_items,id'],
            'therapist_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);
        $item = PatientServicePlanItem::query()
            ->with(['plan', 'service'])
            ->findOrFail($data['patient_service_plan_item_id']);

        if ($item->plan->status !== PatientServicePlan::STATUS_ACTIVE || ! $item->service?->is_active) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'الخدمة المختارة غير متاحة للحجز من خطة نشطة.',
            ]);
        }

        $duration = (int) $item->service->default_duration_minutes;
        if ($duration < 1) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'يجب على الإدارة تحديد مدة الخدمة قبل عرض المواعيد المتاحة.',
            ]);
        }

        if (! $bookingService->eligibility($item)['can_book']) {
            throw ValidationException::withMessages([
                'patient_service_plan_item_id' => 'لم تعد هذه الخدمة مؤهلة لحجز موعد حاليًا. راجع الرصيد والكمية المتاحة في الخطة.',
            ]);
        }

        $therapist = Therapist::query()
            ->where('user_id', $data['therapist_id'])
            ->where('is_active', true)
            ->whereHas('services', fn ($query) => $query->where('services.id', $item->service_id))
            ->first();

        if (! $therapist || ($request->user()->hasRole('أخصائي تخاطب') && $request->user()->id !== (int) $data['therapist_id'])) {
            throw ValidationException::withMessages([
                'therapist_id' => 'الأخصائي المختار غير نشط أو غير مسند إلى هذه الخدمة.',
            ]);
        }

        return response()->json($availability->availability($therapist, $data['date'], $duration));
    }
}
