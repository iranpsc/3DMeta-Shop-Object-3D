<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => $this->faker->numberBetween(1, 10),
            'sku' => $this->faker->unique()->numberBetween(100000, 999999),
            'name' => $this->faker->word,
            'slug' => $this->faker->slug,
            'short_description' => $this->faker->paragraph(),
            'long_description' => $this->faker->paragraph(),
            'stock_status' => true,
            'quantity' => $this->faker->numberBetween(1, 100),
            'delivery_time' => $this->faker->numberBetween(1, 10),
            'customer_can_add_review' => true,
            'price' => $this->faker->numberBetween(10000, 1000000),
            'sale_price' => $this->faker->numberBetween(10000, 999999),
            'published' => $this->faker->boolean,
        ];
    }
}
