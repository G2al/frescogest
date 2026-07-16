<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\WhatsApp\WhatsAppLinkService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class WhatsAppLinkServiceTest extends TestCase
{
    public function test_number_is_sanitized_and_message_contains_order_data(): void
    {
        config(['frescogest.whatsapp_number' => '+39 379 268-8229']);
        $order = new Order(['order_number' => 'FG-000042', 'customer_notes' => 'Mattina', 'total_amount' => 10.50]);
        $order->setRelation('customer', new Customer(['company_name' => 'Orto Verde']));
        $order->setRelation('items', new Collection([
            new OrderItem(['product_name' => 'Mele', 'quantity' => 2.5, 'unit_of_measure_symbol' => 'kg', 'price_per_kg' => 4.20, 'line_total' => 10.50]),
        ]));

        $result = app(WhatsAppLinkService::class)->create($order);

        $this->assertStringStartsWith('https://wa.me/393792688229?text=', $result['url']);
        $this->assertStringContainsString('FG-000042', $result['message']);
        $this->assertStringContainsString('🥬 *FRESCOGEST*', $result['message']);
        $this->assertStringContainsString('• *Mele*', $result['message']);
        $this->assertStringContainsString('2.5 kg × € 4.20/kg = *€ 10.50*', $result['message']);
        $this->assertStringContainsString('TOTALE INDICATIVO: € 10.50', $result['message']);
        $this->assertStringContainsString('Richiesta in attesa di conferma', $result['message']);
    }
}
