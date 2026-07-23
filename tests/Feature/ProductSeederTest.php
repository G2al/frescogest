<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_clothing_catalog_is_seeded_with_prices_and_variants(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('products', 12);
        $this->assertDatabaseCount('product_categories', 8);
        $this->assertGreaterThan(40, ProductVariant::query()->count());

        $shirt = Product::query()
            ->with(['defaultUnitOfMeasure', 'taxRate', 'variants'])
            ->where('code', 'CS-TSH-001')
            ->firstOrFail();

        $this->assertSame('T-shirt basic girocollo', $shirt->name);
        $this->assertSame('pz', $shirt->defaultUnitOfMeasure->symbol);
        $this->assertSame('0.00', $shirt->taxRate->percentage);
        $this->assertSame('9.5000', $shirt->purchase_cost_per_unit_gross);
        $this->assertSame('24.9000', $shirt->base_price_per_unit);
        $this->assertCount(8, $shirt->variants);
    }

    public function test_product_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('products', 12);
        $this->assertDatabaseCount('product_categories', 8);
    }
}
