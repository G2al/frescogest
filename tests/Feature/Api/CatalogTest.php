<?php

namespace Tests\Feature\Api;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_exposes_only_active_products_in_public_categories(): void
    {
        $visible = ProductCategory::create(['name' => 'Frutta', 'slug' => 'frutta', 'catalog_color' => '#e7f3df', 'active' => true, 'is_public' => true]);
        $hidden = ProductCategory::create(['name' => 'Nascosta', 'slug' => 'nascosta', 'active' => true, 'is_public' => false]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $defaults = ['tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $unit->id, 'purchase_cost_per_unit' => 1.75, 'markup_percentage' => 100, 'active' => true];
        Product::create($defaults + ['product_category_id' => $visible->id, 'name' => 'Mele', 'slug' => 'mele', 'is_seasonal' => true]);
        Product::create($defaults + ['product_category_id' => $hidden->id, 'name' => 'Segreto', 'slug' => 'segreto']);
        Product::create(array_merge($defaults, ['product_category_id' => $visible->id, 'name' => 'Non attivo', 'slug' => 'non-attivo', 'active' => false]));

        $this->getJson('/api/v1/catalog/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'frutta')
            ->assertJsonPath('data.0.catalog_color', '#e7f3df')
            ->assertJsonPath('data.0.products_count', 1);
        $this->getJson('/api/v1/catalog/products')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'mele');
        $this->getJson('/api/v1/catalog/products?category=frutta')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/catalog/products?search=mele')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/catalog/products?seasonal=1')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/catalog/products/segreto')->assertNotFound();

        foreach (range(1, 12) as $index) {
            Product::create($defaults + [
                'product_category_id' => $visible->id,
                'name' => "Prodotto {$index}",
                'slug' => "prodotto-{$index}",
            ]);
        }

        $this->getJson('/api/v1/catalog/products')
            ->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 13)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/v1/catalog/products?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_authenticated_customer_receives_personalized_price(): void
    {
        $category = ProductCategory::create(['name' => 'Frutta', 'slug' => 'frutta', 'active' => true, 'is_public' => true]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $unit->id, 'name' => 'Mele', 'slug' => 'mele', 'purchase_cost_per_unit' => 1.75, 'markup_percentage' => 100, 'active' => true]);

        $this->getJson('/api/v1/catalog/products/mele')
            ->assertOk()
            ->assertJsonPath('data.price_per_kg', '3.50')
            ->assertJsonPath('data.has_personalized_price', false);

        $user = User::factory()->create(['active' => true]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);

        $customer->update(['global_discount_percentage' => 10]);

        $this->actingAs($user, 'customer')->getJson('/api/v1/catalog/products/mele')
            ->assertOk()
            ->assertJsonPath('data.price_per_kg', '3.15')
            ->assertJsonPath('data.pricing_source', 'global')
            ->assertJsonPath('data.discount_percentage', '10.00');

        $customer->categoryDiscounts()->create([
            'product_category_id' => $category->id,
            'discount_percentage' => 20,
        ]);

        $this->actingAs($user, 'customer')->getJson('/api/v1/catalog/products/mele')
            ->assertOk()
            ->assertJsonPath('data.price_per_kg', '2.80')
            ->assertJsonPath('data.pricing_source', 'category')
            ->assertJsonPath('data.discount_percentage', '20.00');

        $customer->productPrices()->where('product_id', $product->id)->update(['custom_price_per_unit' => 40]);

        $this->actingAs($user, 'customer')->getJson('/api/v1/catalog/products/mele')
            ->assertOk()
            ->assertJsonPath('data.price_per_kg', '40.00')
            ->assertJsonPath('data.has_personalized_price', true)
            ->assertJsonPath('data.pricing_source', 'product')
            ->assertJsonPath('data.discount_percentage', null);
    }

    public function test_restaurant_list_and_customer_overrides_have_the_expected_priority(): void
    {
        $category = ProductCategory::create(['name' => 'Verdura', 'slug' => 'verdura', 'active' => true, 'is_public' => true]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create([
            'product_category_id' => $category->id, 'tax_rate_id' => $tax->id,
            'default_unit_of_measure_id' => $unit->id, 'name' => 'Pomodori', 'slug' => 'pomodori',
            'purchase_cost_per_unit' => 1, 'markup_percentage' => 100,
            'base_minimum_quantity' => 1, 'restaurant_minimum_quantity' => 5,
            'active' => true, 'is_public' => true,
        ]);
        $user = User::factory()->create(['active' => true]);
        $customer = Customer::factory()->create(['user_id' => $user->id, 'type' => CustomerType::Restaurant]);

        $this->actingAs($user, 'customer')->getJson('/api/v1/catalog/products/pomodori')
            ->assertOk()->assertJsonPath('data.price_per_unit', '2.00')->assertJsonPath('data.minimum_quantity', '5.000');

        $customer->productPrices()->where('product_id', $product->id)->update([
            'custom_price_per_unit' => 4.50,
            'custom_minimum_quantity' => 0.5,
        ]);

        $this->actingAs($user, 'customer')->getJson('/api/v1/catalog/products/pomodori')
            ->assertOk()->assertJsonPath('data.price_per_unit', '4.50')->assertJsonPath('data.minimum_quantity', '0.500')->assertJsonPath('data.pricing_source', 'product');
    }

    public function test_catalog_filters_and_sorting_use_available_product_data(): void
    {
        $category = ProductCategory::create(['name' => 'Frutta', 'slug' => 'frutta', 'active' => true, 'is_public' => true]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $kilograms = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $pieces = UnitOfMeasure::create(['name' => 'Pezzi', 'symbol' => 'pz', 'active' => true]);

        Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $kilograms->id, 'name' => 'Mele', 'slug' => 'mele', 'purchase_cost_per_unit' => 1, 'markup_percentage' => 100, 'active' => true]);
        Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $kilograms->id, 'name' => 'Pere', 'slug' => 'pere', 'purchase_cost_per_unit' => 2, 'markup_percentage' => 100, 'active' => true, 'is_seasonal' => true]);
        Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $pieces->id, 'name' => 'Ananas', 'slug' => 'ananas', 'purchase_cost_per_unit' => 3, 'markup_percentage' => 100, 'active' => true]);

        $this->getJson('/api/v1/catalog/filters')
            ->assertOk()
            ->assertJsonPath('data.price.min', 2)
            ->assertJsonPath('data.price.max', 6)
            ->assertJsonPath('data.seasonal_count', 1)
            ->assertJsonCount(2, 'data.units');

        $this->getJson("/api/v1/catalog/products?unit={$kilograms->id}&min_price=3&max_price=5&sort=price_desc")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'pere');

        $this->getJson('/api/v1/catalog/products?min_price=5&max_price=2')
            ->assertUnprocessable()
            ->assertJsonPath('errors.max_price.0', 'Il prezzo massimo deve essere maggiore o uguale al prezzo minimo.');
    }
}
