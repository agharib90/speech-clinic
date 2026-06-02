<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'phone'      => 'required|string|max:20',
            'phone2'     => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:255',
            'address'    => 'nullable|string|max:255',
            'national_id'=> ['nullable', 'string', Rule::unique('guardians')->ignore($this->guardian)],
            'notes'      => 'nullable|string',
        ];
    }
}
