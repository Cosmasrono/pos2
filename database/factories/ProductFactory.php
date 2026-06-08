<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
        static $barcode_counter = 1000000000000;
        
        return [
            'name' => $this->faker->word() . ' ' . $this->faker->word(),
            'description' => $this->faker->sentence(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'barcode' => (string)($barcode_counter++),
            'category_id' => \App\Models\Category::factory(),
            'cost_price' => $this->faker->numberBetween(100, 1000),
            'selling_price' => $this->faker->numberBetween(1000, 5000),
            'quantity_in_stock' => $this->faker->numberBetween(10, 100),
            'reorder_level' => $this->faker->numberBetween(5, 20),
            'is_active' => true,
        ];
    }
}
