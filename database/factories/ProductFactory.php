<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'price' => fake()->randomFloat(2, 10, 500),
            'tax_percentage' => fake()->randomElement([0, 5, 12, 18]),
            'stock_quantity' => fake()->numberBetween(20, 200),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(0, 5),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }
}
