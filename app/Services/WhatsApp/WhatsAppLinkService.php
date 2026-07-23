<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use RuntimeException;

class WhatsAppLinkService
{
    public function create(Order $order): array
    {
        $number = preg_replace('/\D+/', '', (string) config('cerino.whatsapp_number'));

        if ($number === '') {
            throw new RuntimeException('Il numero WhatsApp non è configurato.');
        }

        $lines = [
            '🛍️ *CERINO STORE*',
            "*Richiesta ordine {$order->order_number}*",
            '',
            "👤 *Cliente:* {$order->customer->display_name}",
            '',
            '📦 *ARTICOLI RICHIESTI*',
        ];

        foreach ($order->items as $item) {
            $unitPrice = $item->unit_price_net ?: $item->price_per_kg;
            $lineTotal = $item->line_gross ?: $item->line_total;
            $variant = collect([
                filled($item->variant_size) ? "Taglia {$item->variant_size}" : null,
                filled($item->variant_color) ? "Colore {$item->variant_color}" : null,
            ])->filter()->implode(' · ');

            $lines[] = "• *{$item->product_name}*".($variant !== '' ? " ({$variant})" : '');
            $lines[] = "  {$this->quantity($item->quantity)} × € {$this->money($unitPrice)} = *€ {$this->money($lineTotal)}*";
        }

        $lines[] = '';
        $lines[] = '💶 *TOTALE: € '.$this->money($order->total_gross ?: $order->total_amount).'*';

        if (filled($order->customer_notes)) {
            $lines[] = '';
            $lines[] = '📝 *NOTE DEL CLIENTE*';
            $lines[] = $order->customer_notes;
        }

        $lines[] = '';
        $lines[] = '⏳ _Richiesta in attesa di conferma su WhatsApp._';
        $lines[] = 'Vorrei concordare disponibilità, spedizione e dettagli dell’ordine.';
        $message = implode("\n", $lines);

        return [
            'url' => "https://wa.me/{$number}?text=".rawurlencode($message),
            'message' => $message,
        ];
    }

    private function quantity(string|int|float $quantity): string
    {
        return number_format((float) $quantity, 0, ',', '').' pz';
    }

    private function money(string|int|float $amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }
}
