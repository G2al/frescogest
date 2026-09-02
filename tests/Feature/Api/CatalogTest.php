<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductVariant;
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

    public function test_product_and_variant_codes_are_optional(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->firstOrFail();
        $product->update(['code' => null]);

        $variant = ProductVariant::query()->firstOrFail();
        $variant->update(['sku' => null]);

        $this->assertNull($product->fresh()->code);
        $this->assertNull($variant->fresh()->sku);
    }

    public function test_product_api_exposes_up_to_three_images_and_keeps_the_cover_image(): void
    {
        $this->seed(DatabaseSeeder::class);

        $product = Product::query()->firstOrFail();
        $product->update([
            'image_path' => 'catalog/products/cover.jpg',
            'image_path_2' => 'catalog/products/detail.jpg',
            'image_path_3' => 'catalog/products/back.jpg',
        ]);

        $this->getJson("/api/v1/catalog/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.image_url', '/storage/catalog/products/cover.jpg')
            ->assertJsonCount(3, 'data.image_urls');
    }
}
