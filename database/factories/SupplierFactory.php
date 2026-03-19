<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            'supplier_category_id' => 1,

            'name' => $this->faker->company(),
            'name_ar' => 'مورد',

            // ✅ NOT NULL
            'code' => 'S'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),

            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),

            'status' => true,
        ];
    }
}
