<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'invoice_code' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'payment_type' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'global_discount_type' => ['nullable', 'in:amount,percent'],
            'global_discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:amount,percent'],
            'items.*.vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],

            'payment' => ['nullable', 'array'],
            'payment.mode' => ['nullable', 'in:split'],
            'payment.cash' => ['nullable', 'numeric', 'min:0'],
            'payment.card' => ['nullable', 'numeric', 'min:0'],
            'payment.wallet' => ['nullable', 'numeric', 'min:0'],
            'payment.treasury_id' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'يجب اختيار العميل',
            'customer_id.exists' => 'العميل المختار غير موجود',
            'invoice_date.required' => 'تاريخ الفاتورة مطلوب',
            'payment_type.required' => 'نوع الدفع مطلوب',
            'payment_type.in' => 'نوع الدفع يجب أن يكون نقدي أو آجل',
            'items.required' => 'يجب إضافة صنف واحد على الأقل',
            'items.min' => 'يجب إضافة صنف واحد على الأقل',
            'items.*.item_id.required' => 'يجب اختيار الصنف',
            'items.*.item_id.exists' => 'الصنف المختار غير موجود',
            'items.*.qty.required' => 'الكمية مطلوبة',
            'items.*.qty.min' => 'الكمية يجب أن تكون أكبر من صفر',
            'items.*.price.required' => 'السعر مطلوب',
            'items.*.price.min' => 'السعر يجب أن يكون أكبر من أو يساوي صفر',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'العميل',
            'invoice_date' => 'تاريخ الفاتورة',
            'payment_type' => 'نوع الدفع',
            'due_date' => 'تاريخ الاستحقاق',
            'items' => 'الأصناف',
        ];
    }
}
