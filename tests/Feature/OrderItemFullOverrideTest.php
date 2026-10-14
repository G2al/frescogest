<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\CreateManualOrderService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Richiesta del cliente: per un singolo ordine deve poter correggere quantità,
 * unità, prezzo netto e IVA di una riga, senza toccare il prodotto in anagrafica
 * né poter cambiare il prodotto stesso dalla modifica (quello si sceglie solo
 * aggiungendo una riga nuova).
 */
class OrderItemFullOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_line_can_override_price_tax_and_unit_without_touching_the_product(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Verdura'], ['active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $kg = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);
        $box = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'box'], ['name' => 'Box', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $kg->id,
            'name' => 'Cipolle Bianche',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);
        $customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'requested_at' => now(),
            'items' => [['product_id' => $onion->id, 'quantity' => 10, 'unit_price_net' => 2]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => EditOrder::class,
        ])
            ->mountTableAction('edit', $item)
            ->setTableActionData([
                'product_id' => $onion->id,
                'quantity' => 10,
                'unit_of_measure_id' => $box->id,
                'unit_price_net' => 3.5,
                'tax_percentage' => 10,
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $item->refresh();
        $this->assertSame('3.5000', $item->unit_price_net);
        $this->assertSame('10.00', $item->tax_percentage);
        $this->assertSame('Box', $item->unit_of_measure_name);
        $this->assertSame('box', $item->unit_of_measure_symbol);

        // Il prodotto in anagrafica non deve essere stato toccato.
        $onion->refresh();
        $this->assertSame($kg->id, $onion->default_unit_of_measure_id);
        $this->assertSame($taxRate->id, $onion->tax_rate_id);
    }

    public function test_the_product_select_is_disabled_when_editing_an_existing_line(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Verdura'], ['active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $kg = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $kg->id,
            'name' => 'Cipolle Bianche',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);
        $customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'requested_at' => now(),
            'items' => [['product_id' => $onion->id, 'quantity' => 10, 'unit_price_net' => 2]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => EditOrder::class,
        ])
            ->mountTableAction('edit', $item)
            ->assertSchemaComponentExists('product_id', null, fn ($component) => $component->isDisabled());
    }
}
