<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Orders\CreateManualOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManualOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_create_a_complete_manual_order_in_one_transaction(): void
    {
        $customer = Customer::factory()->create([
            'delivery_address' => 'Via Roma 10',
            'city' => 'Lusciano',
            'postal_code' => '81030',
            'province' => 'CE',
        ]);
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mele',
            'slug' => 'mele',
            'purchase_cost_per_unit' => 2,
            'markup_percentage' => 100,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);

        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Confirmed->value,
            'requested_at' => now(),
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price_net' => 5,
            ]],
        ]);

        $this->assertSame('IPF-000001', $order->order_number);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertSame('Via Roma 10', $order->delivery_address);
        $this->assertSame('10.00', $order->total_net);
        $this->assertSame('0.40', $order->total_tax);
        $this->assertSame('10.40', $order->total_gross);
        $this->assertSame('6.00', $order->gross_margin);
        $this->assertCount(1, $order->items);
        $this->assertSame('5.0000', $order->items->first()->unit_price_net);
        $this->assertSame('2.000', $order->items->first()->quantity);
    }

    public function test_orders_page_exposes_the_manual_order_action(): void
    {
        $admin = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('Nuovo ordine manuale');

        Livewire::test(ListOrders::class)
            ->assertActionExists('createManualOrder')
            ->mountAction('createManualOrder')
            ->assertActionMounted('createManualOrder');
    }
}
