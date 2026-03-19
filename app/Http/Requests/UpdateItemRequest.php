<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        // Normalize checkboxes (when unchecked they won't be sent)
        $doesHasRetail = $this->has('does_has_retail_unit') ? (bool) $this->input('does_has_retail_unit') : false;
        $status = $this->has('status') ? (bool) $this->input('status') : false;

        $merge = [
            'does_has_retail_unit' => $doesHasRetail,
            'status' => $status,
        ];

        // If retail is disabled, clear its fields
        if (! $doesHasRetail) {
            $merge['retail_unit'] = null;
            $merge['retail_uom_quintToParent'] = null;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $item = $this->route('item'); // Items model
        $itemId = $item?->id;

        return [
            'items_code' => [
                'required', 'string', 'max:255',
                Rule::unique('items', 'items_code')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($itemId),
            ],

            'barcode' => [
                'required', 'string', 'max:255',
                Rule::unique('items', 'barcode')
                    ->where(fn ($q) => $q->where('user_id', auth()->id()))
                    ->ignore($itemId),
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
                Rule::notIn([$itemId]), // prevent parent = self
            ],

            'unit_id' => ['required', 'string', 'max:255'],

            // ✅ Prices (required in your schema)
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
        return (new StoreItemRequest)->messages();
    }
}
