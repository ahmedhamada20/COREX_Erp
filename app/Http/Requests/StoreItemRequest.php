<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        $doesHasRetail = $this->has('does_has_retail_unit') ? (bool) $this->input('does_has_retail_unit') : false;
        $status = $this->has('status') ? (bool) $this->input('status') : false;

        $merge = [
            'does_has_retail_unit' => $doesHasRetail,
            'status' => $status,
        ];

        if (! $doesHasRetail) {
            $merge['retail_unit'] = null;
            $merge['retail_uom_quintToParent'] = null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [

            'barcode' => [
                'required', 'string', 'max:255',
                Rule::unique('items', 'barcode')
                    ->where(fn ($q) => $q->where('user_id', auth()->id())),
            ],

            'name' => ['required', 'string', 'max:255'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'type' => ['required', Rule::in(['store', 'consumption', 'custody'])],

            'item_category_id' => [
                'required',
                Rule::exists('item_categories', 'id')
                    ->where(fn ($q) => $q->where('user_id', auth()->id())),
            ],

            'item_id' => [
                'nullable',
                Rule::exists('items', 'id')
                    ->where(fn ($q) => $q->where('user_id', auth()->id())),
            ],

            'unit_id' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'nos_egomania_price' => ['required', 'numeric', 'min:0'],
            'egomania_price' => ['required', 'numeric', 'min:0'],
            'price_retail' => ['required', 'numeric', 'min:0'],
            'nos_gomla_price_retail' => ['required', 'numeric', 'min:0'],
            'gomla_price_retail' => ['required', 'numeric', 'min:0'],
            'does_has_retail_unit' => ['required', 'boolean'],

            'retail_unit' => [
                Rule::requiredIf(fn () => $this->boolean('does_has_retail_unit')),
                'nullable', 'string', 'max:255',
            ],

            'retail_uom_quintToParent' => [
                Rule::requiredIf(fn () => $this->boolean('does_has_retail_unit')),
                'nullable', 'numeric', 'min:1',
            ],

            'status' => ['required', 'boolean'],
            'date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'items_code.required' => 'كود الصنف مطلوب.',
            'items_code.unique' => 'كود الصنف مستخدم من قبل.',

            'barcode.required' => 'الباركود مطلوب.',
            'barcode.unique' => 'الباركود مستخدم من قبل.',

            'name.required' => 'اسم الصنف مطلوب.',

            'image.image' => 'الملف المرفوع يجب أن يكون صورة.',
            'image.mimes' => 'الصورة يجب أن تكون بصيغة: jpg, jpeg, png, webp.',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2MB.',

            'type.required' => 'نوع الصنف مطلوب.',
            'type.in' => 'نوع الصنف غير صحيح.',

            'item_category_id.required' => 'تصنيف الصنف مطلوب.',
            'item_category_id.exists' => 'تصنيف الصنف غير موجود.',

            'item_id.exists' => 'الصنف الأب غير موجود.',

            'unit_id.required' => 'وحدة القياس الأساسية مطلوبة.',

            // Prices messages
            'price.required' => 'السعر القطاعي (وحدة الأب) مطلوب.',
            'price.numeric' => 'السعر القطاعي (وحدة الأب) يجب أن يكون رقمًا.',
            'nos_egomania_price.required' => 'سعر نص الجملة (وحدة الأب) مطلوب.',
            'nos_egomania_price.numeric' => 'سعر نص الجملة (وحدة الأب) يجب أن يكون رقمًا.',
            'egomania_price.required' => 'سعر الجملة (وحدة الأب) مطلوب.',
            'egomania_price.numeric' => 'سعر الجملة (وحدة الأب) يجب أن يكون رقمًا.',

            'price_retail.required' => 'السعر القطاعي (التجزئة) مطلوب.',
            'price_retail.numeric' => 'السعر القطاعي (التجزئة) يجب أن يكون رقمًا.',
            'nos_gomla_price_retail.required' => 'سعر نص الجملة (التجزئة) مطلوب.',
            'nos_gomla_price_retail.numeric' => 'سعر نص الجملة (التجزئة) يجب أن يكون رقمًا.',
            'gomla_price_retail.required' => 'سعر الجملة (التجزئة) مطلوب.',
            'gomla_price_retail.numeric' => 'سعر الجملة (التجزئة) يجب أن يكون رقمًا.',

            'does_has_retail_unit.required' => 'حقل (هل للصنف وحدة تجزئة) مطلوب.',
            'does_has_retail_unit.boolean' => 'قيمة (هل للصنف وحدة تجزئة) غير صحيحة.',

            'retail_unit.required' => 'وحدة التجزئة مطلوبة عند تفعيل التجزئة.',

            'retail_uom_quintToParent.required' => 'معامل التحويل مطلوب عند تفعيل التجزئة.',
            'retail_uom_quintToParent.numeric' => 'معامل التحويل يجب أن يكون رقمًا.',
            'retail_uom_quintToParent.min' => 'معامل التحويل يجب أن يكون أكبر من صفر.',

            'status.required' => 'حالة الصنف مطلوبة.',
            'status.boolean' => 'قيمة حالة الصنف غير صحيحة.',

            'date.date' => 'صيغة التاريخ غير صحيحة.',
        ];
    }
}
