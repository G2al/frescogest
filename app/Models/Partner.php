<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'notes',
        'active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(PartnerProductPrice::class);
    }

    public function goodsEntries(): HasMany
    {
        return $this->hasMany(PartnerGoodsEntry::class);
    }

    public function dailyReceipts(): HasMany
    {
        return $this->hasMany(PartnerDailyReceipt::class);
    }

    public function dailyWastes(): HasMany
    {
        return $this->hasMany(PartnerDailyWaste::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PartnerExpense::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
