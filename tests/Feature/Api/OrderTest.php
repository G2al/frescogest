<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Filament\Pages\BusinessReports;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\DeleteOrderService;
use App\Services\Orders\UpdateOrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_saved_before_whatsapp_with_snapshots_and_pending_status(): void
    {
        config(['ilparadisodellafrutta.whatsapp_number' => '+39 379 268 8229']);
        [$user, $product] = $this->customerAndProduct();
        $product->update(['image_path' => 'catalog/products/pomodori.png']);
        $user->customer->productPrices()->where('product_id', $product->id)->update([
            'custom_price_per_unit' => 3.60,
        ]);

        $response = $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'customer_notes' => 'Consegna al mattino',
            'items' => [['product_id' => $product->id, 'quantity' => 2.5]],
        ])->assertCreated()
            ->assertJsonPath('data.order_number', 'IPF-000001')
            ->assertJsonPath('data.order.status', OrderStatus::PendingContact->value);

        $order = Order::query()->firstOrFail();
        $this->assertSame('IPF-000001', $order->order_number);
        $this->assertSame(OrderStatus::PendingContact, $order->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Pomodori',
            'unit_of_measure_symbol' => 'kg',
            'quantity' => 2.5,
            'price_per_kg' => 3.60,
            'line_net' => 9.00,
            'line_gross' => 9.36,
        ]);
        $this->assertSame('9.36', $order->total_amount);
        $this->assertSame('9.00', $order->total_net);
        $this->assertSame('0.36', $order->total_tax);
        $this->assertSame('9.36', $order->total_gross);
        $this->assertStringStartsWith('https://wa.me/393792688229?text=', $response->json('data.whatsapp_url'));
        $this->assertSame(OrderStatus::PendingContact, $order->fresh()->status);
        $this->actingAs($user, 'customer')
            ->getJson('/api/v1/orders')
            ->assertOk()
            ->assertJsonPath('data.0.items.0.image_url', Storage::disk('public')->url($product->image_path));
    }

    public function test_orders_are_private_and_unavailable_products_are_rejected(): void
    {
        [$owner, $product] = $this->customerAndProduct();
        $order = Order::create(['customer_id' => $owner->customer->id, 'order_number' => 'IPF-000001', 'status' => OrderStatus::PendingContact, 'requested_at' => now()]);
        $other = User::factory()->create(['active' => true]);
        Customer::factory()->create(['user_id' => $other->id]);

        $this->actingAs($other, 'customer')->getJson("/api/v1/orders/{$order->order_number}")->assertNotFound();
        $product->update(['active' => false]);
        $this->actingAs($owner, 'customer')->postJson('/api/v1/orders', ['items' => [['product_id' => $product->id, 'quantity' => 1]]])->assertUnprocessable();
    }

    public function test_a_two_hundred_gram_package_uses_a_kilogram_unit_price(): void
    {
        [$user, $product] = $this->customerAndProduct();
        $product->update([
            'purchase_cost_per_unit' => 24.65,
            'markup_percentage' => 100,
            'base_minimum_quantity' => 0.2,
            'restaurant_minimum_quantity' => 1,
        ]);

        $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 0.2]],
        ])->assertCreated();

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 0.2,
            'unit_of_measure_symbol' => 'kg',
            'unit_price_net' => 49.30,
            'line_net' => 9.86,
            'line_tax' => 0.39,
            'line_gross' => 10.25,
        ]);
        $this->assertDatabaseHas('orders', [
            'total_net' => 9.86,
            'total_tax' => 0.39,
            'total_gross' => 10.25,
        ]);
    }

    public function test_purchase_cost_is_entered_gross_while_customer_price_remains_net(): void
    {
        [$user, $product] = $this->customerAndProduct();
        $taxRate = TaxRate::query()->whereKey($product->tax_rate_id)->firstOrFail();
        $taxRate->update(['name' => 'IVA 10%', 'percentage' => 10]);
        $product->update([
            'purchase_cost_per_unit_gross' => 6.60,
            'base_price_per_unit' => 9,
            'restaurant_price_per_unit' => 9,
        ]);

        $this->assertSame('6.6000', $product->fresh()->purchase_cost_per_unit_gross);
        $this->assertSame('6.0000', $product->fresh()->purchase_cost_per_unit);

        $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $order = Order::query()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $this->assertSame('9.0000', $item->unit_price_net);
        $this->assertSame('0.90', $item->line_tax);
        $this->assertSame('9.90', $item->line_gross);
        $this->assertSame('6.00', $item->purchase_cost_net);
        $this->assertSame('0.60', $item->purchase_cost_tax);
        $this->assertSame('6.60', $item->purchase_cost_gross);
        $this->assertSame('3.00', $item->margin_amount);

        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);
        $reports = app(BusinessReports::class);
        $reports->mount();
        $summary = $reports->summary();

        $this->assertSame(0.9, $summary['tax']);
        $this->assertSame(0.6, $summary['purchaseTax']);
        $this->assertSame(0.3, round($summary['vatBalance'], 2));
        $this->assertSame(3.0, $summary['grossMargin']);
    }

    public function test_order_status_updates_manage_the_confirmed_and_whatsapp_states(): void
    {
        [$user] = $this->customerAndProduct();
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'order_number' => 'IPF-000001',
            'status' => OrderStatus::PendingContact,
            'requested_at' => now(),
        ]);
        $service = app(UpdateOrderStatusService::class);

        $service->update($order, OrderStatus::Confirmed);
        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);

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
            'order_number' => 'IPF-000001',
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
        $company = Company::create([
            'business_name' => 'Il Paradiso della Frutta di Castaldo Mariarosaria',
            'vat_number' => '02396610186',
            'address' => 'Via dei Caduti Genovesi, 8',
            'city' => 'Bornasco',
            'province' => 'PV',
            'active' => true,
        ]);
        $order = Order::create([
            'customer_id' => $user->customer->id,
            'order_number' => 'IPF-000001',
            'status' => OrderStatus::Confirmed,
            'requested_at' => now(),
            'total_net' => 8.40,
            'total_tax' => 0.34,
            'total_gross' => 8.74,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 2,
            'unit_price_net' => 4.20,
            'tax_percentage' => 4,
            'line_net' => 8.40,
            'line_gross' => 8.74,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ]);
        $admin = User::factory()->create(['active' => true, 'can_access_panel' => true]);

        $document = app(CreateDeliveryDocumentService::class)->create($order, $admin, [
            'issued_at' => now(),
            'mark_as_paid' => false,
        ]);

        $this->assertSame('BC-'.now()->year.'-000001', $document->document_number);
        $this->assertSame('Pomodori', $document->items_snapshot[0]['name']);
        $this->assertSame('Il Paradiso della Frutta di Castaldo Mariarosaria', $document->sender_snapshot['business_name']);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.delivery-document', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        app(DeleteOrderService::class)->delete($order);

        $this->assertDatabaseMissing('delivery_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    private function customerAndProduct(): array
    {
        $user = User::factory()->create(['active' => true]);
        Customer::factory()->create(['user_id' => $user->id]);
        $category = ProductCategory::create(['name' => 'Verdura', 'slug' => 'verdura', 'active' => true, 'is_public' => true]);
        $tax = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create(['product_category_id' => $category->id, 'tax_rate_id' => $tax->id, 'default_unit_of_measure_id' => $unit->id, 'name' => 'Pomodori', 'slug' => 'pomodori', 'purchase_cost_per_unit' => 2.10, 'markup_percentage' => 100, 'active' => true]);

        return [$user->load('customer'), $product];
    }
}
