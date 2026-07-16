<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProductPrice extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'custom_price_per_kg',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePricePerKgAttribute(): string
    {
        return $this->custom_price_per_kg ?? $this->product->price_per_kg;
    }

    protected function casts(): array
    {
        return ['custom_price_per_kg' => 'decimal:2'];
    }
}
