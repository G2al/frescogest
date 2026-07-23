<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Services\WhatsApp\WhatsAppLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_contains_cerino_order_and_variant(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'order_number' => 'CS-000042',
            'status' => 'whatsapp_pending',
            'requested_at' => now(),
            'total_gross' => 79.80,
        ]);
        $order->items()->create([
            'product_name' => 'Camicia Oxford',
            'variant_size' => 'L',
            'variant_color' => 'Azzurro',
            'quantity' => 2,
            'unit_price_net' => 39.90,
            'line_gross' => 79.80,
            'unit_of_measure_name' => 'Pezzi',
            'unit_of_measure_symbol' => 'pz',
        ]);

        $result = app(WhatsAppLinkService::class)->create($order->load(['customer', 'items']));

        $this->assertStringStartsWith('https://wa.me/393240994144?text=', $result['url']);
        $this->assertStringContainsString('CERINO STORE', $result['message']);
        $this->assertStringContainsString('Taglia L · Colore Azzurro', $result['message']);
    }
}
