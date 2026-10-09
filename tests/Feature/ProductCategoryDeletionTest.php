<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductCategories\Pages\EditProductCategory;
use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_category_from_the_edit_page_detaches_its_products_and_notifies_which_ones(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true]);
        $product = $this->product($category, 'Mozzarella');

        $this->actingAs($admin, 'admin');

        Livewire::test(EditProductCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete')
            ->assertNotified('1 prodotti hanno perso la categoria');

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_category_id' => null]);
    }

    public function test_bulk_deleting_categories_from_the_list_page_detaches_their_products_and_notifies_which_ones(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true]);
        $product = $this->product($category, 'Mozzarella');

        $this->actingAs($admin, 'admin');

        Livewire::test(ListProductCategories::class)
            ->callTableBulkAction('delete', [$category])
            ->assertNotified('1 prodotti hanno perso la categoria');

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'product_category_id' => null]);
    }

    private function product(ProductCategory $category, string $name): Product
    {
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        return Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => $name,
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);
    }
}
