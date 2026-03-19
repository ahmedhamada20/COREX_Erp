<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FixedAsset>
 */
class FixedAssetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'asset_code' => 'FA-'.str_pad((string) fake()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => fake()->words(2, true),
            'purchase_date' => now()->subMonths(2)->toDateString(),
            'cost' => fake()->randomFloat(2, 1000, 50000),
            'salvage_value' => 0,
            'useful_life_months' => 60,
            'depreciation_start_date' => now()->subMonths(1)->startOfMonth()->toDateString(),
            'is_group' => false,
            'status' => true,
        ];
    }
}
