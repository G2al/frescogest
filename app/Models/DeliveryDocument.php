<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDocument extends Model
{
    protected $fillable = [
        'order_id',
        'partner_id',
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

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRecipientNameAttribute(): string
    {
        if (filled($this->recipient_snapshot['display_name'] ?? null)) {
            return (string) $this->recipient_snapshot['display_name'];
        }

        if ($this->relationLoaded('partner') && $this->partner) {
            return $this->partner->name;
        }

        if ($this->relationLoaded('order')
            && $this->order
            && $this->order->relationLoaded('customer')
            && $this->order->customer) {
            return $this->order->customer->display_name;
        }

        return 'Destinatario';
    }

    public function getRecipientTypeAttribute(): string
    {
        return $this->partner_id ? 'Partner' : 'Cliente';
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
