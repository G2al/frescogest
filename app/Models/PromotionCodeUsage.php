<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionCodeUsage extends Model
{
    protected $fillable = [
        'promotion_code_id',
        'customer_id',
        'order_id',
        'used_at',
    ];

    public function promotionCode(): BelongsTo
    {
        return $this->belongsTo(PromotionCode::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
        ];
    }
}
