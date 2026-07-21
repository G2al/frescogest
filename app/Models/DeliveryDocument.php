<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDocument extends Model
{
    protected $fillable = [
        'order_id',
        'created_by',
        'document_number',
        'issued_at',
        'transport_reason',
        'transport_method',
        'goods_appearance',
        'packages_count',
        'total_weight',
        'transport_started_at',
        'carrier_name',
        'carrier_vat_number',
        'carrier_tax_code',
        'vehicle_registration',
        'notes',
        'sender_snapshot',
        'recipient_snapshot',
        'destination_snapshot',
        'items_snapshot',
        'subtotal_net',
        'discount_percentage',
        'discount_amount_net',
        'shipping_amount_net',
        'payment_method_snapshot',
        'total_net',
        'total_tax',
        'total_gross',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'transport_started_at' => 'datetime',
            'total_weight' => 'decimal:3',
            'sender_snapshot' => 'array',
            'recipient_snapshot' => 'array',
            'destination_snapshot' => 'array',
            'items_snapshot' => 'array',
            'subtotal_net' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount_net' => 'decimal:2',
            'shipping_amount_net' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'total_gross' => 'decimal:2',
        ];
    }
}
