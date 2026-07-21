<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialRule extends Model
{
    protected $fillable = [
        'name',
        'customer_type',
        'province',
        'postal_code_pattern',
        'minimum_order_gross',
        'free_shipping_threshold_gross',
        'shipping_fee_net',
        'shipping_tax_rate_id',
        'priority',
        'active',
    ];

    public function shippingTaxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'shipping_tax_rate_id');
    }

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'minimum_order_gross' => 'decimal:2',
            'free_shipping_threshold_gross' => 'decimal:2',
            'shipping_fee_net' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
