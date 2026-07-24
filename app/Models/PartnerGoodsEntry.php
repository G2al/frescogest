<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerGoodsEntry extends Model
{
    protected $fillable = [
        'partner_id',
        'product_id',
        'delivered_on',
        'quantity',
        'unit_purchase_price_net',
        'tax_percentage',
        'total_net',
        'total_tax',
        'total_gross',
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

    protected function casts(): array
    {
        return [
            'delivered_on' => 'date',
            'quantity' => 'decimal:3',
            'unit_purchase_price_net' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'total_gross' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PartnerGoodsEntry $entry): void {
            if (! $entry->exists || $entry->isDirty(['partner_id', 'product_id'])) {
                $price = PartnerProductPrice::query()
                    ->where('partner_id', $entry->partner_id)
                    ->where('product_id', $entry->product_id)
                    ->first();

                $entry->unit_purchase_price_net = $price?->purchase_price_net
                    ?? Product::query()->whereKey($entry->product_id)->value('base_price_per_unit')
                    ?? 0;
                $entry->tax_percentage = Product::query()
                    ->whereKey($entry->product_id)
                    ->with('taxRate')
                    ->first()
                    ?->taxRate
                    ?->percentage ?? 0;
            }

            $entry->total_net = round((float) $entry->quantity * (float) $entry->unit_purchase_price_net, 2);
            $entry->total_tax = round((float) $entry->total_net * ((float) $entry->tax_percentage / 100), 2);
            $entry->total_gross = round((float) $entry->total_net + (float) $entry->total_tax, 2);
        });
    }
}
