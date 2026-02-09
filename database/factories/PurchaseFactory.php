<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 100, 5000);
        
        return [
            'supplier_id' => Supplier::factory(),
            'invoice_number' => 'PUR-' . fake()->unique()->numerify('##########'),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'total_amount' => $total,
            'paid_amount' => $total, // Assume paid for simplicity or randomize
            'status' => 'paid',
            'notes' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
