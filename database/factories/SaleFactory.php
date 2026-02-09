<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $tax = $subtotal * 0.1; // 10% tax
        $discount = 0;
        $total = $subtotal + $tax - $discount;
        $cashReceived = $total;
        $change = 0;

        return [
            'invoice_number' => 'INV-' . fake()->unique()->numerify('##########'),
            'user_id' => User::factory(),
            'customer_id' => Customer::factory(),
            'subtotal' => $subtotal,
            'tax_id' => null, // Or create a Tax factory
            'tax' => $tax,
            'discount' => $discount,
            'total_amount' => $total,
            'cash_received' => $cashReceived,
            'change_amount' => $change,
            'payment_method' => fake()->randomElement(['cash', 'card', 'ewallet', 'qr']),
            'status' => 'completed',
            'payment_status' => 'paid',
            'due_date' => now(),
            'notes' => fake()->sentence(),
        ];
    }
}
