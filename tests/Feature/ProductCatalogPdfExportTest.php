<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_product_catalog_pdf(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);

        Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mele Fuji',
            'code' => 'IPF-TEST',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'restaurant_markup_percentage' => 45,
            'partner_markup_percentage' => 35,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.catalog-pdf'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guests_cannot_download_the_product_catalog_pdf(): void
    {
        $response = $this->get(route('admin.products.catalog-pdf'));

        $response->assertRedirect();
    }
}
