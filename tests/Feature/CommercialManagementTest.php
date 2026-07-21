<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Filament\Resources\DeliveryDocuments\Pages\ListDeliveryDocuments;
use App\Models\CommercialRule;
use App\Models\Customer;
use App\Models\DeliveryDocument;
use App\Models\Order;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\Orders\ApplyOrderDiscountService;
use App\Services\Orders\CommercialRuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CommercialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_rule_enforces_minimum_and_calculates_delivery_by_threshold(): void
    {
        $taxRate = TaxRate::create(['name' => 'IVA 22%', 'percentage' => 22, 'active' => true]);
        $customer = Customer::factory()->create(['type' => CustomerType::Private, 'province' => 'PV']);
        CommercialRule::create([
            'name' => 'Privati Lombardia',
            'customer_type' => CustomerType::Private,
            'province' => 'PV',
            'minimum_order_gross' => 50,
            'free_shipping_threshold_gross' => 100,
            'shipping_fee_net' => 5,
            'shipping_tax_rate_id' => $taxRate->id,
            'active' => true,
        ]);
        $order = $this->orderFor($customer);
        $order->items()->create($this->item(['line_gross' => 49]));

        $this->expectException(ValidationException::class);
        app(CommercialRuleService::class)->apply($order);
    }

    public function test_private_delivery_is_charged_below_free_threshold(): void
    {
        $taxRate = TaxRate::create(['name' => 'IVA 22%', 'percentage' => 22, 'active' => true]);
        $customer = Customer::factory()->create(['type' => CustomerType::Private]);
        CommercialRule::create([
            'name' => 'Privati Italia',
            'customer_type' => CustomerType::Private,
            'minimum_order_gross' => 50,
            'free_shipping_threshold_gross' => 100,
            'shipping_fee_net' => 5,
            'shipping_tax_rate_id' => $taxRate->id,
            'active' => true,
        ]);
        $order = $this->orderFor($customer);
        $order->items()->create($this->item(['line_gross' => 75]));

        app(CommercialRuleService::class)->apply($order);

        $this->assertSame('5.00', $order->fresh()->shipping_amount_net);
        $this->assertSame('1.10', $order->fresh()->shipping_tax);
    }

    public function test_order_discount_updates_tax_margin_and_totals(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderFor($customer);
        $order->items()->create($this->item());

        $discounts = app(ApplyOrderDiscountService::class);
        $this->assertSame(93.6, $discounts->estimateGross($order, 10));
        $discounts->apply($order, 10);

        $item = $order->items()->firstOrFail();
        $this->assertSame('10.00', $item->discount_amount_net);
        $this->assertSame('90.00', $item->line_net);
        $this->assertSame('3.60', $item->line_tax);
        $this->assertSame('30.00', $item->margin_amount);
        $this->assertSame('93.60', $order->fresh()->total_gross);
    }

    public function test_selected_delivery_documents_can_be_exported_without_errors(): void
    {
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);
        $order = $this->orderFor(Customer::factory()->create());
        $document = DeliveryDocument::create([
            'order_id' => $order->id,
            'created_by' => $admin->id,
            'document_number' => 'BC-2026-000001',
            'issued_at' => now(),
            'transport_reason' => 'Vendita',
            'transport_method' => 'Mittente',
            'sender_snapshot' => ['business_name' => 'Il Paradiso della Frutta'],
            'recipient_snapshot' => ['display_name' => 'Cliente prova'],
            'destination_snapshot' => [],
            'items_snapshot' => [],
            'subtotal_net' => 10,
            'total_net' => 10,
            'total_tax' => 0.4,
            'total_gross' => 10.4,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(ListDeliveryDocuments::class)
            ->callTableBulkAction('downloadBook', [$document])
            ->callTableAction('downloadFiltered')
            ->assertHasNoActionErrors();
    }

    private function orderFor(Customer $customer): Order
    {
        return Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'IPF-'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
            'status' => OrderStatus::Confirmed,
            'requested_at' => now(),
        ]);
    }

    private function item(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Mele',
            'quantity' => 1,
            'price_per_kg' => 100,
            'unit_price_net' => 100,
            'tax_percentage' => 4,
            'line_total' => 104,
            'original_line_net' => 100,
            'line_net' => 100,
            'line_tax' => 4,
            'line_gross' => 104,
            'purchase_cost_per_unit_net' => 60,
            'purchase_cost_net' => 60,
            'purchase_cost_tax' => 2.4,
            'purchase_cost_gross' => 62.4,
            'margin_amount' => 40,
            'margin_percentage' => 40,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ], $overrides);
    }
}
