<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use RuntimeException;

class WhatsAppLinkService
{
    public function create(Order $order): array
    {
        $number = preg_replace('/\D+/', '', (string) config('frescogest.whatsapp_number'));

        if ($number === '') {
            throw new RuntimeException('Il numero WhatsApp non è configurato.');
        }

        $lines = [
            '🥬 *FRESCOGEST*',
            "*Richiesta ordine {$order->order_number}*",
            '',
            "👤 *Cliente:* {$order->customer->display_name}",
            '',
            '📦 *PRODOTTI RICHIESTI*',
        ];

        foreach ($order->items as $item) {
            $quantity = rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.');
            $lines[] = "• *{$item->product_name}*";
            $lines[] = "  {$quantity} kg × € {$item->price_per_kg}/kg = *€ {$item->line_total}*";
        }

        $lines[] = '';
        $lines[] = "💶 *TOTALE INDICATIVO: € {$order->total_amount}*";

        if (filled($order->customer_notes)) {
            $lines[] = '';
            $lines[] = '📝 *NOTE DEL CLIENTE*';
            $lines[] = $order->customer_notes;
        }

        $lines[] = '';
        $lines[] = '⏳ _Richiesta in attesa di conferma._';
        $lines[] = 'Ti contatto per concordare disponibilità, dettagli e consegna.';

        $message = implode("\n", $lines);

        return [
            'url' => "https://wa.me/{$number}?text=".rawurlencode($message),
            'message' => $message,
        ];
    }
}
