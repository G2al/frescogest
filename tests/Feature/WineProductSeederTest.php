<?php

namespace Tests\Feature;

use App\Models\Producer;
use App\Models\Product;
use Database\Seeders\WineProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WineProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_wine_seeder_creates_the_real_catalog_and_is_idempotent(): void
    {
        $this->seed(WineProductSeeder::class);
        $this->seed(WineProductSeeder::class);

        $wines = Product::query()
            ->with(['productCategory', 'producer', 'taxRate', 'defaultUnitOfMeasure'])
            ->where('code', 'like', 'IPF-WINE-%')
            ->get();

        $this->assertCount(33, $wines);

        foreach ($wines as $wine) {
            $this->assertSame('VINI & LIQUORI', $wine->productCategory->name);
            $this->assertSame('22.00', $wine->taxRate->percentage);
            $this->assertSame('pz', $wine->defaultUnitOfMeasure->symbol);
            $this->assertSame('1.1600', $wine->purchase_cost_per_unit);
            $this->assertSame('2.05', $wine->base_price_per_unit);
            $this->assertSame('1.000', $wine->base_minimum_quantity);
            $this->assertSame('2.05', $wine->restaurant_price_per_unit);
            $this->assertSame('5.000', $wine->restaurant_minimum_quantity);
            $this->assertTrue($wine->active);
            $this->assertSame($wine->description, $wine->public_description);
            $this->assertNotEmpty($wine->description);
        }
    }

    public function test_wine_seeder_creates_the_five_producers_with_correct_counts(): void
    {
        $this->seed(WineProductSeeder::class);

        $producers = Producer::withCount('products')->orderBy('sort_order')->get()->keyBy('name');

        $this->assertCount(5, $producers);
        $this->assertSame(6, $producers['Le Terre del Normanno']->products_count);
        $this->assertSame(2, $producers['Cala del Sole']->products_count);
        $this->assertSame(2, $producers['Borgo di Michelle']->products_count);
        $this->assertSame(10, $producers['Podere Mancini']->products_count);
        // Tenuta Milina include il Magnum: è la stessa casa produttrice.
        $this->assertSame(11, $producers['Tenuta Milina']->products_count);
        $this->assertArrayNotHasKey('Cantine Silvestro', $producers->all());
    }

    public function test_pimpinella_wines_have_no_producer(): void
    {
        $this->seed(WineProductSeeder::class);

        $pimpinella = Product::query()->where('name', 'like', 'Pimpinella DOC%')->get();

        $this->assertCount(2, $pimpinella);
        $this->assertTrue($pimpinella->every(fn (Product $product): bool => $product->producer_id === null));
    }

    public function test_wine_category_defaults_to_alphabetical_sorting(): void
    {
        $this->seed(WineProductSeeder::class);

        $category = Product::query()->where('code', 'like', 'IPF-WINE-%')->first()->productCategory;

        $this->assertTrue((bool) $category->sort_alphabetically);
    }
}
