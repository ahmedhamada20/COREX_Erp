<?php

namespace Database\Factories;

use App\Models\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountTypeFactory extends Factory
{
    protected $model = AccountType::class;

    public function definition(): array
    {
        $types = ['Assets', 'Liabilities', 'Equity', 'Revenue', 'Expenses'];
        $type = $this->faker->randomElement($types);

        return [
            'user_id' => User::factory(),
            'name' => $type,
            'normal_side' => in_array($type, ['Liabilities', 'Equity', 'Revenue']) ? 'credit' : 'debit',
            'allow_posting' => true,
            'status' => true,
        ];
    }
}
