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
 * Segnalazione precisa del cliente: Ordini -> apre l'ordine -> nella tabella righe
 * in fondo clicca "Modifica" sul prodotto (es. Cipolle Bianche) -> cambia la
 * quantità (15 -> 20) -> Salva. La quantità resta invariata, anche annullando il
 * pagamento prima di riprovare. Questa è la pagina ItemsRelationManager dentro
 * l'ordine, diversa dal modale "Modifica e rigenera bolla" già verificato.
 */
class OrderItemsRelationManagerEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_an_order_items_quantity_from_the_relation_manager_actually_saves_it(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Verdura'], ['active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
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
            'items' => [['product_id' => $onion->id, 'quantity' => 15, 'unit_price_net' => 2]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => EditOrder::class,
        ])
            ->mountTableAction('edit', $item)
            ->setTableActionData(['product_id' => $onion->id, 'quantity' => 20])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame('20.000', $item->fresh()->quantity);
    }

    /**
     * Segnalazione reale: "Cipolle Bianche" ha una quantità minima di 25 kg per i
     * ristoratori. Questa regola vale solo per chi prenota dal sito pubblico: in
     * admin (creazione bolla o modifica riga) deve poter inserire qualunque
     * quantità, anche sotto il minimo.
     */
    public function test_editing_below_the_products_minimum_quantity_is_allowed_from_the_admin(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Verdura'], ['active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Cipolle Bianche',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'restaurant_minimum_quantity' => 25,
            'active' => true,
        ]);
        $customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'requested_at' => now(),
            'items' => [['product_id' => $onion->id, 'quantity' => 25, 'unit_price_net' => 2]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(ItemsRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => EditOrder::class,
        ])
            ->mountTableAction('edit', $item)
            ->setTableActionData(['product_id' => $onion->id, 'quantity' => 1])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertSame('1.000', $item->fresh()->quantity);
    }
}
