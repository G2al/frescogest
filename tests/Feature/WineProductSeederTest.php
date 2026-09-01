<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\WineProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WineProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_wine_seeder_creates_only_the_requested_wines_and_is_idempotent(): void
    {
        $this->seed(WineProductSeeder::class);
        $this->seed(WineProductSeeder::class);

        $wines = Product::query()
            ->with(['productCategory', 'taxRate', 'defaultUnitOfMeasure'])
            ->where('code', 'like', 'IPF-WINE-%')
            ->get();

        $this->assertCount(28, $wines);
        $this->assertFalse($wines->contains(fn (Product $product): bool => str_contains($product->name, 'Aglianico Beneventano')));
        $this->assertFalse($wines->contains(fn (Product $product): bool => str_contains($product->name, 'Falanghina Beneventano')));

        foreach ($wines as $wine) {
            $this->assertSame('VINI & LIQUORI', $wine->productCategory->name);
            $this->assertSame('22.00', $wine->taxRate->percentage);
            $this->assertSame('pz', $wine->defaultUnitOfMeasure->symbol);
            $this->assertSame('1.1600', $wine->purchase_cost_per_unit);
            $this->assertSame('2.0500', $wine->base_price_per_unit);
            $this->assertSame('1.000', $wine->base_minimum_quantity);
            $this->assertSame('2.0500', $wine->restaurant_price_per_unit);
            $this->assertSame('5.000', $wine->restaurant_minimum_quantity);
            $this->assertTrue($wine->active);
            $this->assertSame($wine->description, $wine->public_description);
            $this->assertNotEmpty($wine->description);
        }
    }
}
