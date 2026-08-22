<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'therapist_id' => ['required', 'integer', 'exists:users,id'],
            'patient_service_plan_item_id' => [
                'nullable',
                'required_without:session_type_id',
                'integer',
                'exists:patient_service_plan_items,id',
                Rule::prohibitedIf(fn () => $this->filled('session_type_id')),
            ],
            'session_type_id' => [
                'nullable',
                'required_without:patient_service_plan_item_id',
                'integer',
                'exists:session_types,id',
                Rule::prohibitedIf(fn () => $this->filled('patient_service_plan_item_id')),
            ],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'legacy_booking_reason' => [
                'nullable',
                'required_without:patient_service_plan_item_id',
                Rule::prohibitedIf(fn () => $this->filled('patient_service_plan_item_id')),
                'string',
                'max:1000',
            ],
            'workspace' => ['nullable', 'boolean'],
            'workspace_panel' => ['nullable', 'string', 'in:appointment'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'يجب اختيار المريض',
            'therapist_id.required' => 'يجب اختيار الأخصائي',
            'patient_service_plan_item_id.required_without' => 'يجب اختيار خدمة من خطة المريض.',
            'session_type_id.required_without' => 'يجب اختيار نوع الجلسة للموعد القديم.',
            'legacy_booking_reason.required_without' => 'سبب الحجز الاستثنائي مطلوب.',
            'legacy_booking_reason.prohibited' => 'سبب الحجز الاستثنائي غير مسموح لمواعيد خطط الخدمات.',
            'scheduled_at.after_or_equal' => 'لا يمكن حجز موعد في الماضي',
        ];
    }
}
