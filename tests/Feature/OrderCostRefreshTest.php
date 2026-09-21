<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Order;
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
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Scenario del cliente: il pomodorino costa 1 € giovedì e 4 € venerdì. La bolla di
 * venerdì viene fatta alle 7:00 (prezzo di vendita scritto a mano, 5 €) quando il
 * prodotto ha ancora il costo di giovedì; il costo viene aggiornato alle 10:00.
 */
class OrderCostRefreshTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $this->admin = User::query()->where('panel_role', 'admin')->firstOrFail();
        $this->customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);
    }

    public function test_changing_the_cost_realigns_todays_bolle_and_leaves_previous_days_untouched(): void
    {
        $tomato = $this->product('Pomodorino');

        // Giovedì: costo 1 €, venduto a 2 €.
        $this->travelTo(Carbon::parse('2026-09-17 07:00'));
        $thursday = $this->orderWithBolla($tomato, quantity: 100, price: 2);

        // Venerdì 07:00: costo ancora 1 € (non ancora aggiornato), venduto a 5 €.
        $this->travelTo(Carbon::parse('2026-09-18 07:00'));
        $friday = $this->orderWithBolla($tomato, quantity: 100, price: 5);
        $documentBefore = $friday->deliveryDocument->fresh();

        $this->assertSame('100.00', $friday->fresh()->items->first()->purchase_cost_net);
        $this->assertSame('400.00', $friday->fresh()->items->first()->margin_amount); // guadagno falso

        // Venerdì 10:00: il costo di acquisto diventa 4 € (4,16 € IVA inclusa, come lo inserisce lui).
        $this->travelTo(Carbon::parse('2026-09-18 10:00'));
        $tomato->update(['purchase_cost_per_unit_gross' => 4.16]);

        $fridayItem = $friday->fresh()->items->first();
        $this->assertSame('400.00', $fridayItem->purchase_cost_net);
        $this->assertSame('16.00', $fridayItem->purchase_cost_tax);
        $this->assertSame('416.00', $fridayItem->purchase_cost_gross);
        $this->assertSame('100.00', $fridayItem->margin_amount); // guadagno vero
        $this->assertSame('20.00', $fridayItem->margin_percentage);

        // Totali dell'ordine, quelli letti dall'Analisi economica.
        $this->assertSame('400.00', $friday->fresh()->total_purchase_cost_net);
        $this->assertSame('100.00', $friday->fresh()->gross_margin);

        // Il prezzo di vendita e la bolla non cambiano di una virgola, né la revisione.
        $this->assertSame('5.0000', $fridayItem->unit_price_net);
        $this->assertSame('500.00', $fridayItem->line_net);
        $documentAfter = $friday->deliveryDocument->fresh();
        $this->assertSame($documentBefore->revision, $documentAfter->revision);
        $this->assertEquals($documentBefore->items_snapshot, $documentAfter->items_snapshot);
        $this->assertSame($documentBefore->total_gross, $documentAfter->total_gross);

        // Giovedì resta com'era: costo 1 €, guadagno 100 €.
        $thursdayItem = $thursday->fresh()->items->first();
        $this->assertSame('100.00', $thursdayItem->purchase_cost_net);
        $this->assertSame('100.00', $thursdayItem->margin_amount);
        $this->assertSame('100.00', $thursday->fresh()->gross_margin);
    }

    public function test_paid_orders_of_today_are_realigned_too(): void
    {
        $tomato = $this->product('Pomodorino');
        $this->travelTo(Carbon::parse('2026-09-18 07:00'));
        $order = $this->orderWithBolla($tomato, quantity: 100, price: 5, status: 'paid');
        $order->update(['paid_at' => now()]);

        $this->travelTo(Carbon::parse('2026-09-18 10:00'));
        $tomato->update(['purchase_cost_per_unit_gross' => 4.16]);

        $this->assertSame('100.00', $order->fresh()->gross_margin);
    }

    public function test_saving_a_product_without_a_cost_change_does_not_touch_the_orders(): void
    {
        $tomato = $this->product('Pomodorino');
        $this->travelTo(Carbon::parse('2026-09-18 07:00'));
        $order = $this->orderWithBolla($tomato, quantity: 100, price: 5);

        $tomato->update(['name' => 'Pomodorino ciliegino', 'markup_percentage' => 120]);

        $this->assertSame('100.00', $order->fresh()->items->first()->purchase_cost_net);
        $this->assertSame('400.00', $order->fresh()->gross_margin);
    }

    public function test_only_lines_of_the_changed_product_are_touched(): void
    {
        $tomato = $this->product('Pomodorino');
        $lemon = $this->product('Limone');
        $this->travelTo(Carbon::parse('2026-09-18 07:00'));
        $order = $this->orderWithBolla($tomato, quantity: 100, price: 5, extra: [$lemon, 10, 3]);

        $tomato->update(['purchase_cost_per_unit_gross' => 4.16]);

        $lines = $order->fresh()->items->keyBy('product_id');
        $this->assertSame('400.00', $lines[$tomato->id]->purchase_cost_net);
        $this->assertSame('10.00', $lines[$lemon->id]->purchase_cost_net); // 10 kg × 1 € invariato
        // Ricavi 500 + 30, costi 400 + 10.
        $this->assertSame('410.00', $order->fresh()->total_purchase_cost_net);
        $this->assertSame('120.00', $order->fresh()->gross_margin);
    }

    public function test_generating_todays_bolla_uses_the_current_cost_but_a_past_dated_one_keeps_history(): void
    {
        $tomato = $this->product('Pomodorino');
        $this->travelTo(Carbon::parse('2026-09-18 07:00'));

        $orderToday = $this->order($tomato, quantity: 100, price: 5);
        $orderPast = $this->order($tomato, quantity: 100, price: 5);

        // Il costo viene aggiornato PRIMA di fare le bolle (nessuna bolla esiste ancora).
        $tomato->update(['purchase_cost_per_unit_gross' => 4.16]);
        $this->assertSame('100.00', $orderToday->fresh()->items->first()->purchase_cost_net);

        app(CreateDeliveryDocumentService::class)->create($orderToday, $this->admin, ['issued_at' => now()]);
        app(CreateDeliveryDocumentService::class)->create($orderPast, $this->admin, ['issued_at' => now()->subDay()]);

        $this->assertSame('400.00', $orderToday->fresh()->items->first()->purchase_cost_net);
        $this->assertSame('100.00', $orderToday->fresh()->gross_margin);
        // Bolla con data passata: costo storico invariato.
        $this->assertSame('100.00', $orderPast->fresh()->items->first()->purchase_cost_net);
    }

    private function product(string $name): Product
    {
        $category = ProductCategory::query()->firstOrCreate(['name' => 'Frutta'], ['active' => true]);
        $tax = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        return Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $tax->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => $name,
            'purchase_cost_per_unit' => 1,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 1,
            'active' => true,
        ]);
    }

    private function order(Product $product, float $quantity, float $price, string $status = 'confirmed', ?array $extra = null): Order
    {
        $items = [['product_id' => $product->id, 'quantity' => $quantity, 'unit_price_net' => $price]];

        if ($extra !== null) {
            [$other, $otherQuantity, $otherPrice] = $extra;
            $items[] = ['product_id' => $other->id, 'quantity' => $otherQuantity, 'unit_price_net' => $otherPrice];
        }

        return app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer->id,
            'status' => $status,
            'requested_at' => now(),
            'items' => $items,
        ]);
    }

    private function orderWithBolla(Product $product, float $quantity, float $price, string $status = 'confirmed', ?array $extra = null): Order
    {
        $order = $this->order($product, $quantity, $price, $status, $extra);

        app(CreateDeliveryDocumentService::class)->create($order, $this->admin, ['issued_at' => now()]);

        return $order->refresh()->load('deliveryDocument');
    }
}
