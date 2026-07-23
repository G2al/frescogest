<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_clothing_products_with_active_variants(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->where('code', 'CS-TSH-001')->firstOrFail();

        $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->assertJsonCount(8, 'data');

        $this->getJson("/api/v1/catalog/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'T-shirt basic girocollo')
            ->assertJsonPath('data.brand', 'Cerino Store')
            ->assertJsonPath('data.price_per_unit', '24.90')
            ->assertJsonCount(8, 'data.variants');
    }

    public function test_inactive_products_are_not_visible(): void
    {
        $this->seed(DatabaseSeeder::class);
        $product = Product::query()->firstOrFail();
        $product->update(['active' => false]);

        $this->getJson("/api/v1/catalog/products/{$product->slug}")->assertNotFound();
    }
}
