<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerGoodsEntry extends Model
{
    protected $fillable = [
        'partner_id',
        'delivery_document_id',
        'product_id',
        'delivered_on',
        'quantity',
        'unit_purchase_price_net',
        'unit_cost_net',
        'tax_percentage',
        'total_net',
        'total_tax',
        'total_gross',
        'total_cost_net',
        'total_cost_tax',
        'total_cost_gross',
        'notes',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryDocument(): BelongsTo
    {
        return $this->belongsTo(DeliveryDocument::class);
    }

    protected function casts(): array
    {
        return [
            'delivered_on' => 'date',
            'quantity' => 'decimal:3',
            'unit_purchase_price_net' => 'decimal:4',
            'unit_cost_net' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'total_gross' => 'decimal:2',
            'total_cost_net' => 'decimal:2',
            'total_cost_tax' => 'decimal:2',
            'total_cost_gross' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PartnerGoodsEntry $entry): void {
            $product = null;

            if (($entry->exists && $entry->isDirty(['partner_id', 'product_id']))
                || blank($entry->unit_purchase_price_net)
                || blank($entry->tax_percentage)) {
                $price = PartnerProductPrice::query()
                    ->where('partner_id', $entry->partner_id)
                    ->where('product_id', $entry->product_id)
                    ->first();

                $product = Product::query()
                    ->whereKey($entry->product_id)
                    ->with('taxRate')
                    ->first();

                $entry->unit_purchase_price_net = $price?->purchase_price_net
                    ?? $product?->base_price_per_unit
                    ?? 0;
                $entry->tax_percentage = $product?->taxRate?->percentage ?? 0;
            }

            if (! $entry->exists || $entry->isDirty('product_id') || ! array_key_exists('unit_cost_net', $entry->getAttributes())) {
                $product ??= Product::query()->find($entry->product_id);
                $entry->unit_cost_net = $product?->purchase_cost_per_unit ?? 0;
            }

            $entry->total_net = round((float) $entry->quantity * (float) $entry->unit_purchase_price_net, 2);
            $entry->total_tax = round((float) $entry->total_net * ((float) $entry->tax_percentage / 100), 2);
            $entry->total_gross = round((float) $entry->total_net + (float) $entry->total_tax, 2);
            $entry->total_cost_net = round((float) $entry->quantity * (float) $entry->unit_cost_net, 2);
            $entry->total_cost_tax = round((float) $entry->total_cost_net * ((float) $entry->tax_percentage / 100), 2);
            $entry->total_cost_gross = round((float) $entry->total_cost_net + (float) $entry->total_cost_tax, 2);
        });
    }
}
