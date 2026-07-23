<?php

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_saved_with_clothing_variant_before_whatsapp(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['active' => true]);
        Customer::factory()->create([
            'user_id' => $user->id,
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
        $product = Product::query()->with('variants')->where('code', 'CS-TSH-001')->firstOrFail();
        $variant = $product->variants->first();

        $response = $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'customer_notes' => 'Spedizione a domicilio',
            'items' => [[
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ]],
        ])->assertCreated()
            ->assertJsonPath('data.order_number', 'CS-000001')
            ->assertJsonPath('data.order.status', OrderStatus::WhatsAppPending->value);

        $order = Order::query()->with('items')->firstOrFail();
        $item = $order->items->first();

        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($variant->size, $item->variant_size);
        $this->assertSame($variant->color, $item->variant_color);
        $this->assertSame('49.80', $order->total_gross);
        $this->assertStringStartsWith('https://wa.me/393240994144?text=', $response->json('data.whatsapp_url'));
        $this->assertStringContainsString('CERINO STORE', urldecode($response->json('data.whatsapp_url')));
    }

    public function test_variant_must_belong_to_selected_product(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create(['active' => true]);
        Customer::factory()->create(['user_id' => $user->id]);
        $products = Product::query()->with('variants')->take(2)->get();

        $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'items' => [[
                'product_id' => $products[0]->id,
                'product_variant_id' => $products[1]->variants->first()->id,
                'quantity' => 1,
            ]],
        ])->assertNotFound();
    }
}
