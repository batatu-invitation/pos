<?php

namespace Database\Factories;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    protected $model = Tax::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word . ' Tax',
            'rate' => $this->faker->randomFloat(2, 0, 20),
            'is_active' => true,
        ];
    }
}
