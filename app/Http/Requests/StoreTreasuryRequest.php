<?php

namespace App\Http\Requests;

use App\Models\Treasuries;
use Illuminate\Foundation\Http\FormRequest;

class StoreTreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $noCode = [
            'not_regex:/<|>/',       // HTML tags
            'not_regex:/@/',         // Blade directives @if @php ...
            'not_regex:/\{\{|\}\}/', // {{ }}
        ];

        return [
            'name' => array_merge(['required', 'string', 'max:255'], $noCode),
            'is_master' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $wantsMaster = $this->boolean('is_master');

            if (! $wantsMaster) {
                return;
            }

            $hasMaster = Treasuries::where('user_id', auth()->id())
                ->where('is_master', true)
                ->exists();

            if ($hasMaster) {
                $validator->errors()->add('is_master', 'يوجد خزنة رئيسية بالفعل، لا يمكن إضافة خزنة رئيسية جديدة.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الخزنة',
            'is_master' => 'نوع الخزنة',
            'status' => 'حالة الخزنة',
            'date' => 'التاريخ',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الخزنة مطلوب.',
            'name.string' => 'اسم الخزنة يجب أن يكون نصًا.',
            'name.max' => 'اسم الخزنة يجب ألا يزيد عن 255 حرفًا.',
            'name.not_regex' => 'غير مسموح بإدخال أكواد أو رموز برمجية داخل اسم الخزنة.',

            'is_master.boolean' => 'نوع الخزنة غير صحيح.',
            'status.boolean' => 'حالة الخزنة غير صحيحة.',
            'date.date' => 'صيغة التاريخ غير صحيحة.',
        ];
    }
}
