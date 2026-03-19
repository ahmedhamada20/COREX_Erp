<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'tax_included' => ['nullable', 'boolean'],
            'discount_type' => ['nullable', 'in:none,fixed,percent'],
            'discount_rate' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:none,fixed,percent'],
            'items.*.discount_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'يجب اختيار المورد',
            'supplier_id.exists' => 'المورد المختار غير موجود',
            'invoice_number.required' => 'رقم الفاتورة مطلوب',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب',
            'payment_type.required' => 'نوع الدفع مطلوب',
            'payment_type.in' => 'نوع الدفع يجب أن يكون نقدي أو آجل',
            'items.required' => 'يجب إضافة صنف واحد على الأقل',
            'items.min' => 'يجب إضافة صنف واحد على الأقل',
            'items.*.item_id.required' => 'يجب اختيار الصنف',
            'items.*.item_id.exists' => 'الصنف المختار غير موجود',
            'items.*.qty.required' => 'الكمية مطلوبة',
            'items.*.qty.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.unit_price.required' => 'سعر الوحدة مطلوب',
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'المورد',
            'invoice_number' => 'رقم الفاتورة',
            'invoice_date' => 'تاريخ الفاتورة',
            'payment_type' => 'نوع الدفع',
            'items' => 'الأصناف',
        ];
    }
}
