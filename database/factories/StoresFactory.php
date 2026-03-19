<?php

namespace Database\Factories;

use App\Models\Stores;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stores>
 */
class StoresFactory extends Factory
{
    protected $model = Stores::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word().' store',
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'status' => true,
        ];
    }
}
