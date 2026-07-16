<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\UpdateOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_saved_before_whatsapp_with_snapshots_and_pending_status(): void
    {
        config(['frescogest.whatsapp_number' => '+39 379 268 8229']);
        [$user, $product] = $this->customerAndProduct();
        $user->customer->productPrices()->where('product_id', $product->id)->update([
            'custom_price_per_kg' => 3.60,
        ]);

        $response = $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'customer_notes' => 'Consegna al mattino',
            'items' => [['product_id' => $product->id, 'quantity' => 2.5]],
        ])->assertCreated()
            ->assertJsonPath('data.order_number', 'FG-000001')
            ->assertJsonPath('data.order.status', OrderStatus::PendingContact->value);

        $order = Order::query()->firstOrFail();
        $this->assertSame('FG-000001', $order->order_number);
        $this->assertSame(OrderStatus::PendingContact, $order->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Pomodori',
            'unit_of_measure_symbol' => 'kg',
            'quantity' => 2.5,
            'price_per_kg' => 3.60,
            'line_total' => 9.00,
        ]);
        $this->assertSame('9.00', $order->total_amount);
        $this->assertStringStartsWith('https://wa.me/393792688229?text=', $response->json('data.whatsapp_url'));
        $this->assertSame(OrderStatus::PendingContact, $order->fresh()->status);
    }

    public function test_orders_are_private_and_unavailable_products_are_rejected(): void
    {
        [$owner, $product] = $this->customerAndProduct();
        $order = Order::create(['customer_id' => $owner->customer->id, 'order_number' => 'FG-000001', 'status' => OrderStatus::PendingContact, 'requested_at' => now()]);
        $other = User::factory()->create(['active' => true]);
        Customer::factory()->create(['user_id' => $other->id]);

        $this->actingAs($other, 'customer')->getJson("/api/v1/orders/{$order->order_number}")->assertNotFound();
        $product->update(['is_public' => false]);
        $this->actingAs($owner, 'customer')->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
    }

    public function test_order_status_updates_manage_confirmation_and_delivery_dates(): void
    {
        [$user] = $this->customerAndProduct();
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'order_number' => 'FG-000001',
            'status' => OrderStatus::PendingContact,
            'requested_at' => now(),
        ]);
        $service = app(UpdateOrderStatusService::class);

        $service->update($order, OrderStatus::Confirmed);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);

        $service->update($order, OrderStatus::Preparing);
        $service->update($order, OrderStatus::Delivered);
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->delivered_at);

        $service->update($order, OrderStatus::PendingContact);
        $this->assertSame(OrderStatus::PendingContact, $order->status);
        $this->assertNull($order->confirmed_at);
        $this->assertNull($order->delivered_at);
    }

    public function test_deleting_an_order_permanently_deletes_its_items(): void
    {
        [$user, $product] = $this->customerAndProduct();
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'order_number' => 'FG-000001',
            'status' => OrderStatus::PendingContact,
            'requested_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'price_per_kg' => 4.20,
            'line_total' => 8.40,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ]);

        $order->delete();

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    public function test_a_paid_order_can_generate_a_progressive_delivery_document_pdf(): void
    {
        [$user, $product] = $this->customerAndProduct();
        Company::create([
            'business_name' => 'Frescogest S.r.l.',
            'vat_number' => '01234567890',
            'address' => 'Via Roma 1',
            'city' => 'Napoli',
            'postal_code' => '80100',
            'province' => 'NA',
            'active' => true,
        ]);
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'order_number' => 'FG-000001',
            'status' => OrderStatus::Delivered,
            'requested_at' => now(),
            'delivered_at' => now(),
            'paid_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ]);
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);

        $document = app(CreateDeliveryDocumentService::class)->create($order, $admin, [
            'issued_at' => now(),
            'transport_reason' => 'Vendita',
            'transport_method' => 'Mittente',
            'goods_appearance' => 'Colli',
            'packages_count' => 1,
        ]);

        $this->assertSame('DDT-'.now()->year.'-000001', $document->document_number);
        $this->assertSame('Pomodori', $document->items_snapshot[0]['name']);
        $this->assertSame('Frescogest S.r.l.', $document->sender_snapshot['business_name']);
        $this->actingAs($admin)
            ->get(route('admin.orders.delivery-document', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function customerAndProduct(): array
    {
        $user = User::factory()->create(['active' => true]);
        Customer::factory()->create(['user_id' => $user->id]);
        $category = ProductCategory::create(['name' => 'Verdura', 'slug' => 'verdura', 'active' => true, 'is_public' => true]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $unit->id, 'name' => 'Pomodori', 'slug' => 'pomodori', 'price_per_kg' => 4.20, 'active' => true, 'is_public' => true]);

        return [$user->load('customer'), $product];
    }
}
