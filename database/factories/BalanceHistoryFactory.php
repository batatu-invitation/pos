<?php

namespace Database\Factories;

use App\Models\BalanceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BalanceHistoryFactory extends Factory
{
    protected $model = BalanceHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'type' => $this->faker->randomElement(['credit', 'debit']),
            'description' => $this->faker->sentence,
        ];
    }
}
