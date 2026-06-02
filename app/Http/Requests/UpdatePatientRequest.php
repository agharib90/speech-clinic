<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'guardian_id'      => 'required|exists:guardians,id',
            'name'             => 'required|string|max:255',
            'birth_date'       => 'required|date',
            'gender'           => 'required|in:male,female',
            'diagnosis'        => 'nullable|string|max:255',
            'referral_source'  => 'nullable|string|max:255',
            'is_active'        => 'nullable|boolean',
            'notes'            => 'nullable|string',
        ];
    }
}
