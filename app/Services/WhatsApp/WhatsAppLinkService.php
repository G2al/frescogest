<?php

namespace App\Services\WhatsApp;

use App\Models\Order;
use RuntimeException;

class WhatsAppLinkService
{
    public function create(Order $order): array
    {
        $number = preg_replace('/\D+/', '', (string) config('ilparadisodellafrutta.whatsapp_number'));

        if ($number === '') {
            throw new RuntimeException('Il numero WhatsApp non è configurato.');
        }

        $lines = [
            '🥬 *IL PARADISO DELLA FRUTTA*',
            "*Richiesta ordine {$order->order_number}*",
            '',
            "👤 *Cliente:* {$order->customer->display_name}",
            '',
            '📦 *PRODOTTI RICHIESTI*',
        ];

        foreach ($order->items as $item) {
            $unitPrice = $item->unit_price_net ?: $item->price_per_kg;
            $lineGross = $item->line_gross ?: $item->line_total;
            $lines[] = "• *{$item->product_name}*";
            $lines[] = "  {$this->quantity($item->quantity, $item->unit_of_measure_symbol)} × € {$this->money($unitPrice)}/{$item->unit_of_measure_symbol} = *€ {$this->money($lineGross)} IVA incl.*";
        }

        $lines[] = '';
        $lines[] = '💶 *TOTALE IVA INCLUSA: € '.$this->money($order->total_gross ?: $order->total_amount).'*';

        if (filled($order->customer_notes)) {
            $lines[] = '';
            $lines[] = '📝 *NOTE DEL CLIENTE*';
            $lines[] = $order->customer_notes;
        }

        $lines[] = '';
        $lines[] = '⏳ _Richiesta in trattativa WhatsApp._';
        $lines[] = 'Ti contatto per concordare disponibilità, dettagli e consegna.';
        $message = implode("\n", $lines);

        return ['url' => "https://wa.me/{$number}?text=".rawurlencode($message), 'message' => $message];
    }

    private function quantity(string|int|float $quantity, string $unit): string
    {
        $value = (float) $quantity;

        if ($unit === 'kg' && $value > 0 && $value < 1) {
            return $this->number($value * 1000).' g';
        }

        return $this->number($value).' '.$unit;
    }

    private function money(string|int|float $amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
    }
}
