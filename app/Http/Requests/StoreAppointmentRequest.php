<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'patient_id'      => 'required|exists:patients,id',
            'therapist_id'    => 'required|exists:users,id',
            'session_type_id' => 'required|exists:session_types,id',
            'scheduled_at'    => 'required|date|after_or_equal:now',
            'notes'           => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'يجب اختيار المريض',
            'therapist_id.required' => 'يجب اختيار الأخصائي',
            'scheduled_at.after_or_equal' => 'لا يمكن حجز موعد في الماضي',
        ];
    }
}
