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
        config(['ilparadisodellafrutta.whatsapp_number' => '+39 379 268-8229']);
        $order = new Order(['order_number' => 'IPF-000042', 'customer_notes' => 'Mattina', 'total_amount' => 10.50]);
        $order->setRelation('customer', new Customer(['company_name' => 'Orto Verde']));
        $order->setRelation('items', new Collection([
            new OrderItem(['product_name' => 'Mele', 'quantity' => 2.5, 'unit_of_measure_symbol' => 'kg', 'price_per_kg' => 4.20, 'line_total' => 10.50]),
        ]));

        $result = app(WhatsAppLinkService::class)->create($order);

        $this->assertStringStartsWith('https://wa.me/393792688229?text=', $result['url']);
        $this->assertStringContainsString('IPF-000042', $result['message']);
        $this->assertStringContainsString('🥬 *IL PARADISO DELLA FRUTTA*', $result['message']);
        $this->assertStringContainsString('• *Mele*', $result['message']);
        $this->assertStringContainsString('2,5 kg × € 4,20/kg = *€ 10,50 IVA incl.*', $result['message']);
        $this->assertStringContainsString('TOTALE IVA INCLUSA: € 10,50', $result['message']);
        $this->assertStringContainsString('Richiesta in trattativa WhatsApp', $result['message']);
    }

    public function test_fractional_kilograms_are_displayed_as_grams_without_changing_the_unit_price(): void
    {
        config(['ilparadisodellafrutta.whatsapp_number' => '393792688229']);
        $order = new Order(['order_number' => 'IPF-000043', 'total_gross' => 10.25]);
        $order->setRelation('customer', new Customer(['first_name' => 'Luigi', 'last_name' => 'Iommelli']));
        $order->setRelation('items', new Collection([
            new OrderItem([
                'product_name' => 'Aglio confezione',
                'quantity' => 0.2,
                'unit_of_measure_symbol' => 'kg',
                'unit_price_net' => 49.30,
                'line_gross' => 10.25,
            ]),
        ]));

        $message = app(WhatsAppLinkService::class)->create($order)['message'];

        $this->assertStringContainsString('200 g × € 49,30/kg = *€ 10,25 IVA incl.*', $message);
    }
}
