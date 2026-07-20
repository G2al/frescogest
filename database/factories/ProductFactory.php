<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = ucfirst(fake()->unique()->words(fake()->numberBetween(1, 3), true));
        $purchaseCost = fake()->randomFloat(2, 0.5, 15);
        $basePrice = round($purchaseCost * 2, 2);

        return [
            'product_category_id' => fn (): int => ProductCategory::query()->inRandomOrder()->firstOrFail()->getKey(),
            'tax_rate_id' => fn (): int => TaxRate::query()->inRandomOrder()->firstOrFail()->getKey(),
            'default_unit_of_measure_id' => fn (): int => UnitOfMeasure::query()->inRandomOrder()->firstOrFail()->getKey(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('#####'),
            'code' => fake()->unique()->bothify('PRD-#####'),
            'description' => fake()->optional(0.7)->sentence(),
            'public_description' => fake()->sentence(),
            'price_per_kg' => fake()->randomFloat(2, 1, 30),
            'purchase_cost_per_unit' => $purchaseCost,
            'markup_percentage' => 100,
            'base_price_per_unit' => $basePrice,
            'restaurant_price_per_unit' => $basePrice,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'image_path' => null,
            'is_public' => true,
            'is_seasonal' => fake()->boolean(25),
            'sort_order' => fake()->numberBetween(0, 1000),
            'notes' => fake()->optional(0.2)->sentence(),
            'active' => fake()->boolean(90),
        ];
    }
}
