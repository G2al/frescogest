<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryAutoMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_changing_purchase_cost_updates_the_sale_price_when_category_has_auto_markup_enabled(): void
    {
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true, 'auto_markup_enabled' => true]);
        $product = $this->product($category);

        $product->purchase_cost_per_unit = 2;
        $product->save();
        $product->refresh();

        $this->assertSame('3.50', $product->base_price_per_unit);
        $this->assertSame('2.90', $product->restaurant_price_per_unit);
        $this->assertSame('2.70', $product->partner_price_per_unit);
    }

    public function test_changing_purchase_cost_leaves_the_sale_price_untouched_when_category_has_auto_markup_disabled(): void
    {
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true, 'auto_markup_enabled' => false]);
        $product = $this->product($category);

        $product->purchase_cost_per_unit = 2;
        $product->save();
        $product->refresh();

        $this->assertSame('1.75', $product->base_price_per_unit);
        $this->assertSame('1.45', $product->restaurant_price_per_unit);
        $this->assertSame('1.35', $product->partner_price_per_unit);
    }

    public function test_explicitly_editing_the_markup_percentage_still_updates_the_price_even_with_auto_markup_disabled(): void
    {
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true, 'auto_markup_enabled' => false]);
        $product = $this->product($category);

        $product->markup_percentage = 90;
        $product->save();
        $product->refresh();

        $this->assertSame('1.90', $product->base_price_per_unit);
    }

    public function test_explicitly_editing_the_sale_price_still_recalculates_the_markup_with_auto_markup_disabled(): void
    {
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true, 'auto_markup_enabled' => false]);
        $product = $this->product($category);

        $product->base_price_per_unit = 3;
        $product->save();
        $product->refresh();

        $this->assertSame('200.00', $product->markup_percentage);
    }

    public function test_a_new_product_in_a_disabled_category_still_gets_an_initial_price_computed(): void
    {
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true, 'auto_markup_enabled' => false]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        $product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mozzarella',
            'purchase_cost_per_unit' => 2,
            'markup_percentage' => 50,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);

        $this->assertSame('3.00', $product->base_price_per_unit);
    }

    public function test_editing_the_cost_in_the_admin_form_does_not_live_update_the_price_when_category_has_auto_markup_disabled(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $category = ProductCategory::create(['name' => 'Latticini', 'active' => true, 'auto_markup_enabled' => false]);
        $product = $this->product($category);

        $this->actingAs($admin, 'admin');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.purchase_cost_per_unit', 2)
            ->assertSet('data.base_price_per_unit', '1.75');
    }

    public function test_editing_the_cost_in_the_admin_form_live_updates_the_price_when_category_has_auto_markup_enabled(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true, 'auto_markup_enabled' => true]);
        $product = $this->product($category);

        $this->actingAs($admin, 'admin');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->set('data.purchase_cost_per_unit', 2)
            ->assertSet('data.base_price_per_unit', '3.50');
    }

    private function product(ProductCategory $category): Product
    {
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        return Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Ciliegino',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'restaurant_markup_percentage' => 45,
            'partner_markup_percentage' => 35,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);
    }
}
