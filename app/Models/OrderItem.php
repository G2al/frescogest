<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'quantity',
        'price_per_kg',
        'line_total',
        'unit_of_measure_name',
        'unit_of_measure_symbol',
        'sort_order',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price_per_kg' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }
}
