<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'guardian_mode' => ['required', Rule::in(['existing', 'new'])],
            'guardian_id' => [
                'nullable',
                Rule::requiredIf($this->input('guardian_mode') === 'existing'),
                Rule::exists('guardians', 'id')->whereNull('deleted_at'),
            ],
            'guardian' => ['nullable', Rule::requiredIf($this->input('guardian_mode') === 'new'), 'array'],
            'name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female',
            'diagnosis' => 'nullable|string|max:255',
            'referral_source' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ];

        if ($this->input('guardian_mode') === 'new') {
            foreach (StoreGuardianRequest::guardianRules() as $field => $fieldRules) {
                $rules["guardian.{$field}"] = $fieldRules;
            }

            $rules['guardian.phone'] = [
                'required',
                'string',
                'max:20',
                Rule::unique('guardians', 'phone')->whereNull('deleted_at'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الطفل مطلوب',
            'guardian_id.required' => 'يجب اختيار ولي الأمر',
            'birth_date.required' => 'تاريخ الميلاد مطلوب',
            'guardian.name.required' => 'اسم ولي الأمر مطلوب',
            'guardian.phone.required' => 'رقم الهاتف مطلوب',
            'guardian.phone.unique' => 'رقم الهاتف مسجل لولي أمر موجود. ابحث عنه واختر سجله بدلًا من إنشاء سجل مكرر.',
            'guardian.national_id.unique' => 'الرقم الوطني مسجل مسبقاً',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('guardian_mode') && $this->filled('guardian_id')) {
            $this->merge(['guardian_mode' => 'existing']);
        }
    }
}
