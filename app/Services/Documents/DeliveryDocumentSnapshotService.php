<?php

namespace App\Services\Documents;

use App\Models\Order;

class DeliveryDocumentSnapshotService
{
    public function items(Order $order): array
    {
        return $order->items->map(fn ($item): array => [
            'name' => $item->product_name,
            'quantity' => (string) $item->quantity,
            'unit_symbol' => $item->unit_of_measure_symbol,
            'unit_price_net' => (string) $item->unit_price_net,
            'tax_percentage' => (string) $item->tax_percentage,
            'line_net' => (string) $item->line_net,
            'line_gross' => (string) $item->line_gross,
        ])->values()->all();
    }
}
