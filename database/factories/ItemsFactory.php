<?php

namespace Database\Factories;

use App\Models\ItemCategory;
use App\Models\Items;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemsFactory extends Factory
{
    protected $model = Items::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'name' => $this->faker->word(),

            // ✅ عندك NOT NULL
            'barcode' => $this->faker->unique()->ean13(),

            'items_code' => 'ITM-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),

            // ✅ متوافق مع جدولك
            'price' => $this->faker->randomFloat(2, 20, 1000),

            // ✅ اعتبرها تكلفة
            'nos_egomania_price' => $this->faker->randomFloat(2, 10, 500),

            'egomania_price' => $this->faker->randomFloat(2, 10, 500),

            'price_retail' => $this->faker->randomFloat(2, 5, 200),

            'nos_gomla_price_retail' => $this->faker->randomFloat(2, 5, 200),

            'gomla_price_retail' => $this->faker->randomFloat(2, 5, 200),

            'item_category_id' => ItemCategory::factory(),

            'status' => true,

            'type' => 'store',
        ];
    }
}
