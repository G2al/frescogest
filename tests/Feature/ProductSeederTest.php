<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_gram_packages_are_seeded_with_canonical_kilogram_prices(): void
    {
        $this->seed(DatabaseSeeder::class);

        $garlic = Product::query()
            ->with('defaultUnitOfMeasure')
            ->where('name', 'Aglio confezione')
            ->firstOrFail();

        $this->assertSame('kg', $garlic->defaultUnitOfMeasure->symbol);
        $this->assertSame('0.200', $garlic->base_minimum_quantity);
        $this->assertSame('1.000', $garlic->restaurant_minimum_quantity);
        $this->assertSame(
            round((float) $garlic->purchase_cost_per_unit * 2, 4),
            (float) $garlic->base_price_per_unit,
        );
    }
}
