<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement(['income', 'expense']),
            'amount' => $this->faker->randomFloat(2, 10000, 5000000),
            'category' => $this->faker->word,
            'description' => $this->faker->sentence,
            'date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'reference_number' => $this->faker->uuid,
            'payment_method' => $this->faker->randomElement(['Cash', 'Bank Transfer', 'Credit Card']),
            'status' => 'completed',
        ];
    }
}
