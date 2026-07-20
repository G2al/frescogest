<?php

namespace App\Models;

use App\Services\Pricing\ProductPricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductPrice extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'custom_price_per_kg',
        'custom_price_per_unit',
        'custom_minimum_quantity',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePricePerUnitAttribute(): string
    {
        return app(ProductPricingService::class)->detailsForRow($this)['price'];
    }

    public function getPricingRuleAttribute(): array
    {
        return app(ProductPricingService::class)->detailsForRow($this);
    }

    protected function casts(): array
    {
        return [
            'custom_price_per_kg' => 'decimal:2',
            'custom_price_per_unit' => 'decimal:4',
            'custom_minimum_quantity' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CustomerProductPrice $price): void {
            if ($price->custom_price_per_unit === null && $price->custom_price_per_kg !== null) {
                $price->custom_price_per_unit = $price->custom_price_per_kg;
            }

            $price->custom_price_per_kg = $price->custom_price_per_unit;
        });
    }
}
