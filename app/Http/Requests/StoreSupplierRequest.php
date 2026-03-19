<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
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

        return [
            'supplier_category_id' => [
                'required',
                'integer',
                Rule::exists('supplier_categories', 'id')
                    ->where(fn ($q) => $q->where('user_id', $tenantId)),
            ],

            'name' => ['required', 'string', 'max:255'],

            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('suppliers', 'code')
                    ->where(fn ($q) => $q->where('user_id', $tenantId)),
            ],

            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('suppliers', 'phone')
                    ->where(fn ($q) => $q->where('user_id', $tenantId)),
            ],

            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],

            'start_balance' => ['nullable', 'numeric'],
            // لا تخلّي current_balance جاي من الفورم
            // 'current_balance' => ['nullable','numeric'],

            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_category_id.required' => 'تصنيف المورد مطلوب.',
            'supplier_category_id.integer' => 'تصنيف المورد غير صحيح.',
            'supplier_category_id.exists' => 'تصنيف المورد غير موجود.',

            'name.required' => 'اسم المورد مطلوب.',
            'name.string' => 'اسم المورد يجب أن يكون نص.',
            'name.max' => 'اسم المورد لا يجب أن يتجاوز 255 حرف.',

            'code.string' => 'كود المورد يجب أن يكون نص.',
            'code.max' => 'كود المورد لا يجب أن يتجاوز 50 حرف.',
            'code.unique' => 'كود المورد مستخدم من قبل.',

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
