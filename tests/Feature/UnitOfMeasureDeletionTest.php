<?php

namespace Tests\Feature;

use App\Filament\Resources\UnitOfMeasures\Pages\EditUnitOfMeasure;
use App\Filament\Resources\UnitOfMeasures\Pages\ListUnitOfMeasures;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnitOfMeasureDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_unit_from_the_edit_page_detaches_its_products_and_notifies_which_ones(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Cestini', 'symbol' => 'cestino', 'active' => true]);
        $product = $this->product($unit, 'Fragole');

        $this->actingAs($admin, 'admin');

        Livewire::test(EditUnitOfMeasure::class, ['record' => $unit->getRouteKey()])
            ->callAction('delete')
            ->assertNotified('1 prodotti hanno perso l\'unità di misura');

        $this->assertDatabaseMissing('unit_of_measures', ['id' => $unit->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'default_unit_of_measure_id' => null]);
    }

    public function test_bulk_deleting_units_from_the_list_page_detaches_their_products_and_notifies_which_ones(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Cestini', 'symbol' => 'cestino', 'active' => true]);
        $product = $this->product($unit, 'Fragole');

        $this->actingAs($admin, 'admin');

        Livewire::test(ListUnitOfMeasures::class)
            ->callTableBulkAction('delete', [$unit])
            ->assertNotified('1 prodotti hanno perso l\'unità di misura');

        $this->assertDatabaseMissing('unit_of_measures', ['id' => $unit->id]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'default_unit_of_measure_id' => null]);
    }

    public function test_a_product_without_a_unit_is_excluded_from_the_public_catalog(): void
    {
        $unit = UnitOfMeasure::create(['name' => 'Cestini', 'symbol' => 'cestino', 'active' => true]);
        $product = $this->product($unit, 'Fragole', publicCategory: true);
        $product->update(['slug' => 'fragole', 'base_price_per_unit' => 5]);

        $this->assertTrue(Product::publicCatalog()->whereKey($product->id)->exists());

        $unit->delete();

        $this->assertFalse(Product::publicCatalog()->whereKey($product->id)->exists());
    }

    private function product(UnitOfMeasure $unit, string $name, bool $publicCategory = false): Product
    {
        $category = ProductCategory::create([
            'name' => 'Frutta',
            'active' => true,
            'is_public' => $publicCategory,
            'slug' => $publicCategory ? 'frutta' : null,
        ]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);

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
