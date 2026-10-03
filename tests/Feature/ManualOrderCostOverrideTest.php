<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Filament\Resources\Orders\Pages\ListOrders;
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
 * Esempio del cliente: i limoni costano di listino 1€ al kg, ma per Antonio li ha
 * pagati 3€ (guadagno 2€ vendendoli a 5€) e per Luigi li ha pagati 2€ (guadagno 3€
 * vendendoli a 5€). Ogni riga deve tenere il proprio costo, indipendente dalle altre.
 */
class ManualOrderCostOverrideTest extends TestCase
{
    use RefreshDatabase;

    private Product $lemon;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $this->admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Frutta'], ['active' => true]);
        $tax = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        $this->lemon = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $tax->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Limoni',
            'purchase_cost_per_unit' => 1,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 1,
            'active' => true,
        ]);
    }

    public function test_each_order_keeps_its_own_custom_cost_independently(): void
    {
        $antonio = $this->customer();
        $luigi = $this->customer();

        $orderAntonio = app(CreateManualOrderService::class)->create([
            'customer_id' => $antonio->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 3]],
        ]);

        $orderLuigi = app(CreateManualOrderService::class)->create([
            'customer_id' => $luigi->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 2]],
        ]);

        $itemAntonio = $orderAntonio->fresh()->items->first();
        $itemLuigi = $orderLuigi->fresh()->items->first();

        $this->assertTrue($itemAntonio->purchase_cost_is_custom);
        $this->assertSame('30.00', $itemAntonio->purchase_cost_net); // 10 kg × 3€
        $this->assertSame('20.00', $itemAntonio->margin_amount); // 50€ - 30€ = 2€/kg × 10kg

        $this->assertTrue($itemLuigi->purchase_cost_is_custom);
        $this->assertSame('20.00', $itemLuigi->purchase_cost_net); // 10 kg × 2€
        $this->assertSame('30.00', $itemLuigi->margin_amount); // 50€ - 20€ = 3€/kg × 10kg

        // Listino prodotto invariato: non è stato toccato.
        $this->assertSame('1.0000', $this->lemon->fresh()->purchase_cost_per_unit);
    }

    public function test_without_a_value_the_cost_still_comes_from_the_product_as_before(): void
    {
        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer()->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5]],
        ]);

        $item = $order->fresh()->items->first();
        $this->assertFalse($item->purchase_cost_is_custom);
        $this->assertSame('10.00', $item->purchase_cost_net); // 10 kg × 1€ (costo del prodotto)
    }

    public function test_changing_the_product_cost_does_not_touch_a_custom_line_but_touches_a_normal_one(): void
    {
        // Solo le righe di bolle emesse oggi vengono ricalcolate: ne genero una per
        // ciascun ordine, come nel flusso reale.
        $customOrder = app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer()->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 3]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($customOrder, $this->admin, ['issued_at' => now()]);

        $normalOrder = app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer()->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($normalOrder, $this->admin, ['issued_at' => now()]);

        $this->lemon->update(['purchase_cost_per_unit_gross' => 2.08]); // 2€ netto, IVA 4%

        // La riga personalizzata resta com'era.
        $this->assertSame('30.00', $customOrder->fresh()->items->first()->purchase_cost_net);
        // Quella normale si aggiorna al nuovo costo, come già funziona oggi.
        $this->assertSame('20.00', $normalOrder->fresh()->items->first()->purchase_cost_net);
    }

    public function test_generating_todays_bolla_does_not_overwrite_a_custom_cost(): void
    {
        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer()->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 3]],
        ]);

        // Il costo del prodotto cambia prima di fare la bolla (come nello scenario del
        // pomodorino): la riga personalizzata deve restare quella scritta a mano.
        $this->lemon->update(['purchase_cost_per_unit_gross' => 2.08]);

        app(CreateDeliveryDocumentService::class)->create($order, $this->admin, ['issued_at' => now()]);

        $this->assertSame('30.00', $order->fresh()->items->first()->purchase_cost_net);
        $this->assertSame('20.00', $order->fresh()->gross_margin);
    }

    public function test_the_products_own_cost_and_margin_stay_independent(): void
    {
        $antonio = $this->customer();
        app(CreateManualOrderService::class)->create([
            'customer_id' => $antonio->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 3]],
        ]);
        $luigi = $this->customer();
        app(CreateManualOrderService::class)->create([
            'customer_id' => $luigi->id,
            'status' => 'confirmed',
            'requested_at' => now(),
            'items' => [['product_id' => $this->lemon->id, 'quantity' => 10, 'unit_price_net' => 5, 'purchase_cost_per_unit_net' => 2]],
        ]);

        // Il prodotto non è stato modificato da nessuna delle due righe personalizzate.
        $this->assertSame('1.0000', $this->lemon->fresh()->purchase_cost_per_unit);
    }

    public function test_the_new_order_form_mounts_without_errors(): void
    {
        $this->actingAs($this->admin, 'admin');

        Livewire::test(ListOrders::class)
            ->mountAction('createManualOrder')
            ->assertActionMounted('createManualOrder');
    }

    private function customer(): Customer
    {
        return Customer::factory()->create(['type' => CustomerType::Restaurant]);
    }
}
