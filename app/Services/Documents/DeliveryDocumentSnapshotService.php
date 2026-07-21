<?php

namespace App\Services\Documents;

use App\Models\Order;

class DeliveryDocumentSnapshotService
{
    public function items(Order $order): array
    {
        return $order->items->map(function ($item): array {
            $originalLineNet = (float) $item->original_line_net > 0
                ? $item->original_line_net
                : $item->line_net;

            return [
                'name' => $item->product_name,
                'quantity' => (string) $item->quantity,
                'unit_symbol' => $item->unit_of_measure_symbol,
                'unit_price_net' => (string) $item->unit_price_net,
                'tax_percentage' => (string) $item->tax_percentage,
                'original_line_net' => (string) $originalLineNet,
                'discount_percentage' => (string) ($item->discount_percentage ?? 0),
                'discount_amount_net' => (string) ($item->discount_amount_net ?? 0),
                'line_net' => (string) $item->line_net,
                'line_gross' => (string) $item->line_gross,
            ];
        })->values()->all();
    }
}
