<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'reference' => 'JE-' . fake()->unique()->numerify('#####'),
            'description' => fake()->sentence(),
            'type' => 'general',
            'status' => 'posted',
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
        ];
    }
}
