<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $noHtml = ['not_regex:/<|>/', 'not_regex:/@/', 'not_regex:/\{\{|\}\}/'];

        return [
            'name' => array_merge(['required', 'string', 'max:255'], $noHtml),
            'phone' => array_merge(['nullable', 'string', 'max:50'], $noHtml),
            'address' => array_merge(['nullable', 'string', 'max:255'], $noHtml),
            'vat_number' => ['nullable', 'string', 'max:50'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fiscal_year_start' => ['nullable', 'date'],
            'base_currency' => ['nullable', 'string', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:20'],
            'decimal_places' => ['nullable', 'integer', 'min:0', 'max:4'],
            'enable_inventory_tracking' => ['nullable', 'boolean'],
            'general_alert' => array_merge(['nullable', 'string', 'max:255'], $noHtml),
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,jpg,jpeg,webp', 'max:1024'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم النظام مطلوب',
            'name.string' => 'اسم النظام يجب أن يكون نصًا',
            'name.max' => 'اسم النظام يجب ألا يزيد عن 255 حرفًا',

            'phone.string' => 'رقم الهاتف يجب أن يكون نصًا',
            'phone.max' => 'رقم الهاتف يجب ألا يزيد عن 50 رقمًا',

            'address.string' => 'العنوان يجب أن يكون نصًا',
            'address.max' => 'العنوان يجب ألا يزيد عن 255 حرفًا',

            'logo.image' => 'اللوجو يجب أن يكون صورة',
            'logo.mimes' => 'اللوجو يجب أن يكون بصيغة png أو jpg أو jpeg أو webp',
            'logo.max' => 'حجم اللوجو يجب ألا يزيد عن 2 ميجا',

            'favicon.image' => 'الأيقونة يجب أن تكون صورة',
            'favicon.mimes' => 'الأيقونة يجب أن تكون بصيغة png أو jpg أو jpeg أو webp أو ico',
            'favicon.max' => 'حجم الأيقونة يجب ألا يزيد عن 1 ميجا',

            'status.boolean' => 'حالة النظام غير صحيحة',

            'general_alert.string' => 'التنبيه العام يجب أن يكون نصًا',
            'general_alert.max' => 'التنبيه العام يجب ألا يزيد عن 255 حرفًا',

            'name.not_regex' => 'غير مسموح بإدخال أكواد أو علامات خاصة داخل اسم النظام.',
            'phone.not_regex' => 'غير مسموح بإدخال أكواد داخل رقم الهاتف.',
            'address.not_regex' => 'غير مسموح بإدخال أكواد داخل العنوان.',
            'general_alert.not_regex' => 'غير مسموح بإدخال أكواد داخل التنبيه العام.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم النظام',
            'phone' => 'رقم الهاتف',
            'address' => 'العنوان',
            'logo' => 'اللوجو',
            'favicon' => 'الأيقونة',
            'status' => 'حالة النظام',
            'general_alert' => 'التنبيه العام',
        ];
    }
}
