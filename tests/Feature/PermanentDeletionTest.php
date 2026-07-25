<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Customers\DeleteCustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermanentDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_customer_removes_the_customer_account_orders_and_items(): void
    {
        $user = User::factory()->create([
            'active' => true,
            'can_access_panel' => false,
        ]);
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'IPF-000001',
            'status' => OrderStatus::WhatsAppPending,
            'requested_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_name' => 'Mele',
            'quantity' => 2,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ]);

        app(DeleteCustomerService::class)->delete($customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    public function test_deleting_a_product_keeps_the_order_snapshot_without_the_product_reference(): void
    {
        [$product, $customer] = $this->productAndCustomer();
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'IPF-000001',
            'status' => OrderStatus::WhatsAppPending,
            'requested_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ]);
        $price = $customer->productPrices()
            ->where('product_id', $product->id)
            ->firstOrFail();
        $price->update([
            'custom_price_per_unit' => 3.50,
        ]);

        $product->delete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('customer_product_prices', ['id' => $price->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'product_id' => null,
            'product_name' => $product->name,
        ]);
    }

    public function test_deleting_a_category_permanently_removes_its_products(): void
    {
        [$product] = $this->productAndCustomer();
        $category = $product->productCategory;

        $category->delete();

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    private function productAndCustomer(): array
    {
        $category = ProductCategory::create([
            'name' => 'Frutta',
            'slug' => 'frutta',
            'active' => true,
            'is_public' => true,
        ]);
        $taxRate = TaxRate::create([
            'name' => 'IVA 4%',
            'percentage' => 4,
            'active' => true,
        ]);
        $unit = UnitOfMeasure::create([
            'name' => 'Chilogrammi',
            'symbol' => 'kg',
            'active' => true,
        ]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mele',
            'slug' => 'mele',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 100,
            'active' => true,
        ]);

        return [$product, Customer::factory()->create()];
    }
}
