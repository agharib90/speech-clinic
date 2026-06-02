<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // سمحنا للجميع مؤقتاً، الصلاحيات هتتكتب بعدين
    }

    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:255',
            'phone'      => 'required|string|max:20',
            'phone2'     => 'nullable|string|max:20',
            'email'      => 'nullable|email|max:255',
            'address'    => 'nullable|string|max:255',
            'national_id'=> 'nullable|string|unique:guardians,national_id',
            'notes'      => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم ولي الأمر مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'national_id.unique' => 'الرقم الوطني مسجل مسبقاً',
        ];
    }
}
