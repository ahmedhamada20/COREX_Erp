<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // لو route model binding: accountType
        // أو لو عندك {account_type} استخدم request()->route('account_type')
        $id = $this->route('account_type')?->id ?? $this->route('account_type');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('account_types')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($id),
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('account_types')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($id),
            ],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
            'allow_posting' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم نوع الحساب مطلوب.',
            'name.max' => 'الاسم لا يجب أن يتجاوز 255 حرف.',
            'name.unique' => 'اسم نوع الحساب موجود بالفعل.',
            'code.max' => 'الكود لا يجب أن يتجاوز 100 حرف.',
            'code.unique' => 'الكود مستخدم بالفعل.',
            'date.date' => 'صيغة التاريخ غير صحيحة.',
        ];
    }
}
