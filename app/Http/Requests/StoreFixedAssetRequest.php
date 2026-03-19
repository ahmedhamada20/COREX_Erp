<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFixedAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'parent_id' => ['nullable', 'integer', 'exists:fixed_assets,id'],
            'asset_code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'asset_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'accumulated_depreciation_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'depreciation_expense_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'purchase_date' => ['nullable', 'date'],
            'cost' => ['required', 'numeric', 'min:0'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'depreciation_start_date' => ['nullable', 'date'],
            'is_group' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
