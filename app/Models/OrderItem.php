<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_sku',
        'variant_size',
        'variant_color',
        'quantity',
        'price_per_kg',
        'unit_price_net',
        'tax_percentage',
        'line_total',
        'original_line_net',
        'discount_percentage',
        'discount_amount_net',
        'line_net',
        'line_tax',
        'line_gross',
        'purchase_cost_per_unit_net',
        'purchase_cost_net',
        'purchase_cost_tax',
        'purchase_cost_gross',
        'margin_amount',
        'margin_percentage',
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
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'price_per_kg' => 'decimal:2',
            'line_total' => 'decimal:2',
            'original_line_net' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount_net' => 'decimal:2',
            'unit_price_net' => 'decimal:4',
            'tax_percentage' => 'decimal:2',
            'line_net' => 'decimal:2',
            'line_tax' => 'decimal:2',
            'line_gross' => 'decimal:2',
            'purchase_cost_per_unit_net' => 'decimal:4',
            'purchase_cost_net' => 'decimal:2',
            'purchase_cost_tax' => 'decimal:2',
            'purchase_cost_gross' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'margin_percentage' => 'decimal:2',
        ];
    }
}
