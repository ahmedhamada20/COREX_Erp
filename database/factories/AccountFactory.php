<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_type_id' => AccountType::factory(),
            'name' => $this->faker->word(),
            'account_number' => $this->faker->unique()->numerify('ACC-####'),
            'start_balance' => $this->faker->randomFloat(2, 0, 10000),
            'current_balance' => $this->faker->randomFloat(2, 0, 10000),
            'is_subsidiary' => false,
            'status' => true,
        ];
    }
}
