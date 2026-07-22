<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductSeeder;
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

    public function test_pdf_products_use_gross_purchase_cost_net_website_price_tax_and_package_size(): void
    {
        $this->seed(DatabaseSeeder::class);

        $olives = Product::query()->with(['taxRate', 'defaultUnitOfMeasure'])->where('name', 'Olive verdi')->firstOrFail();
        $this->assertSame('6.6000', $olives->purchase_cost_per_unit_gross);
        $this->assertSame('6.0000', $olives->purchase_cost_per_unit);
        $this->assertSame('9.0000', $olives->base_price_per_unit);
        $this->assertSame('10.00', $olives->taxRate->percentage);
        $this->assertSame('kg', $olives->defaultUnitOfMeasure->symbol);

        $mozzarella = Product::query()->with('defaultUnitOfMeasure')->where('name', 'Mozzarella da 125 g')->firstOrFail();
        $this->assertSame('9.1700', $mozzarella->purchase_cost_per_unit_gross);
        $this->assertSame('11.5000', $mozzarella->base_price_per_unit);
        $this->assertSame('0.125', $mozzarella->base_minimum_quantity);
        $this->assertSame('kg', $mozzarella->defaultUnitOfMeasure->symbol);

        $baba = Product::query()->with('defaultUnitOfMeasure')->where('name', 'Babà')->firstOrFail();
        $this->assertSame('2.4000', $baba->purchase_cost_per_unit_gross);
        $this->assertSame('6.0000', $baba->base_price_per_unit);
        $this->assertSame('conf', $baba->defaultUnitOfMeasure->symbol);
        $this->assertSame('Confezione da 3 pezzi', $baba->notes);

        $this->assertDatabaseHas('products', [
            'name' => 'Cartoni per le pizze',
            'active' => false,
            'base_price_per_unit' => 0,
        ]);
    }

    public function test_product_seeder_does_not_overwrite_products_not_listed_in_the_pdf(): void
    {
        $this->seed(DatabaseSeeder::class);
        $apple = Product::query()->where('name', 'Mele Fuji')->firstOrFail();
        $apple->update(['base_price_per_unit' => 99]);

        $this->seed(ProductSeeder::class);

        $this->assertSame('99.0000', $apple->fresh()->base_price_per_unit);
    }
}
