<?php

namespace App\Services\Documents;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Partner;

class DeliveryDocumentSnapshotService
{
    public function customer(Customer $customer): array
    {
        return [
            'display_name' => $customer->display_name,
            'type' => $customer->type?->label(),
            'company_name' => $customer->company_name,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'vat_number' => $customer->vat_number,
            'tax_code' => $customer->tax_code,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->delivery_address ?: $customer->billing_address,
            'city' => $customer->city,
            'postal_code' => $customer->postal_code,
            'province' => $customer->province,
        ];
    }

    public function partner(Partner $partner): array
    {
        return [
            'display_name' => $partner->name,
            'type' => 'Partner',
            'email' => $partner->email,
            'phone' => $partner->phone,
            'address' => $partner->address ?? null,
            'city' => $partner->city ?? null,
            'postal_code' => $partner->postal_code ?? null,
            'province' => $partner->province ?? null,
            'vat_number' => $partner->vat_number ?? null,
            'tax_code' => $partner->tax_code ?? null,
        ];
    }

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
