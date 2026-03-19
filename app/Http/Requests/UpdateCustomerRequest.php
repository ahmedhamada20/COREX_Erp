<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    private function tenantId(): int
    {
        $u = $this->user();

        return (int) ($u->owner_user_id ?? $u->id);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->tenantId();

        $customer = $this->route('customer');
        $customerId = $customer?->id ?? (int) $customer;

        return [
            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers', 'code')
                    ->where(fn ($q) => $q->where('user_id', $tenantId))
                    ->ignore($customerId),
            ],

            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers', 'phone')
                    ->where(fn ($q) => $q->where('user_id', $tenantId))
                    ->ignore($customerId),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],

            'start_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العميل مطلوب.',
            'name.string' => 'اسم العميل يجب أن يكون نص.',
            'name.max' => 'اسم العميل لا يجب أن يتجاوز 255 حرف.',

            'code.string' => 'كود العميل يجب أن يكون نص.',
            'code.max' => 'كود العميل لا يجب أن يتجاوز 50 حرف.',
            'code.unique' => 'كود العميل مستخدم من قبل.',

            'phone.string' => 'رقم الهاتف يجب أن يكون نص.',
            'phone.max' => 'رقم الهاتف لا يجب أن يتجاوز 50 رقم.',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل.',

            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.max' => 'البريد الإلكتروني لا يجب أن يتجاوز 255 حرف.',

            'city.string' => 'المدينة يجب أن تكون نص.',
            'city.max' => 'اسم المدينة لا يجب أن يتجاوز 255 حرف.',

            'start_balance.numeric' => 'الرصيد الافتتاحي يجب أن يكون رقم.',
            'notes.string' => 'الملاحظات يجب أن تكون نص.',
            'status.boolean' => 'قيمة الحالة غير صحيحة.',
            'date.date' => 'صيغة التاريخ غير صحيحة.',

            'image.image' => 'الملف يجب أن يكون صورة.',
            'image.mimes' => 'الصورة يجب أن تكون بصيغة JPG أو PNG أو WEBP.',
            'image.max' => 'حجم الصورة لا يجب أن يتجاوز 2 ميجابايت.',
        ];
    }
}
