<?php

namespace App\Models;

use App\Services\Pricing\ProductListPriceCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerProductPrice extends Model
{
    protected $fillable = [
        'partner_id',
        'product_id',
        'purchase_price_net',
        'sale_price_net',
        'markup_percentage',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getPurchasePriceGrossAttribute(): float
    {
        return $this->gross((float) $this->purchase_price_net);
    }

    public function getSalePriceGrossAttribute(): float
    {
        return $this->gross((float) $this->sale_price_net);
    }

    public function getMarginNetAttribute(): float
    {
        return round((float) $this->sale_price_net - (float) $this->purchase_price_net, 4);
    }

    protected function casts(): array
    {
        return [
            'purchase_price_net' => 'decimal:4',
            'sale_price_net' => 'decimal:4',
            'markup_percentage' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PartnerProductPrice $price): void {
            $calculator = app(ProductListPriceCalculator::class);

            if ($price->isDirty('sale_price_net') && ! $price->isDirty('markup_percentage')) {
                $price->markup_percentage = $calculator->markupFromPrice(
                    $price->purchase_price_net,
                    $price->sale_price_net,
                );

                return;
            }

            if ($price->isDirty('purchase_price_net') || $price->isDirty('markup_percentage') || ! $price->exists) {
                $price->sale_price_net = $calculator->priceFromMarkup(
                    $price->purchase_price_net,
                    $price->markup_percentage,
                );
            }
        });
    }

    private function gross(float $net): float
    {
        $tax = (float) ($this->product?->taxRate?->percentage ?? 0);

        return round($net * (1 + ($tax / 100)), 4);
    }
}
