<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'payment_method_id',
        'status',
        'requested_at',
        'customer_notes',
        'total_amount',
        'internal_notes',
        'delivery_address',
        'delivery_city',
        'delivery_postal_code',
        'delivery_province',
        'delivery_notes',
        'confirmed_at',
        'expected_delivery_at',
        'delivered_at',
        'paid_at',
        'payment_reference',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('sort_order');
    }

    public function deliveryDocument(): HasOne
    {
        return $this->hasOne(DeliveryDocument::class);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'requested_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'expected_delivery_at' => 'datetime',
            'delivered_at' => 'datetime',
            'paid_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }
}
