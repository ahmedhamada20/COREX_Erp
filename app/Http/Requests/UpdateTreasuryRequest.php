<?php

namespace App\Http\Requests;

use App\Models\Treasuries;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTreasuryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $noCode = ['not_regex:/<|>/', 'not_regex:/@/', 'not_regex:/\{\{|\}\}/'];

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

            $treasuryId = $this->route('treasury')?->id ?? $this->route('treasury');

            $hasOtherMaster = Treasuries::where('user_id', auth()->id())
                ->where('is_master', true)
                ->where('id', '!=', $treasuryId)
                ->exists();

            if ($hasOtherMaster) {
                $validator->errors()->add('is_master', 'يوجد خزنة رئيسية أخرى بالفعل، لا يمكن تعيين هذه الخزنة كرئيسية.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الخزنة مطلوب.',
            'name.max' => 'اسم الخزنة يجب ألا يزيد عن 255 حرفًا.',
            'name.not_regex' => 'غير مسموح بإدخال أكواد داخل اسم الخزنة.',
            'date.date' => 'صيغة التاريخ غير صحيحة.',
        ];
    }
}
