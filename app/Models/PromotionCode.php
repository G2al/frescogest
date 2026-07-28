<?php

namespace App\Models;

use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionCode extends Model
{
    protected $fillable = [
        'name',
        'code',
        'discount_percentage',
        'audience',
        'rule',
        'starts_at',
        'ends_at',
        'single_use_per_customer',
        'active',
        'featured_on_sticker',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionCodeUsage::class);
    }

    protected function casts(): array
    {
        return [
            'discount_percentage' => 'decimal:2',
            'audience' => PromotionAudience::class,
            'rule' => PromotionRule::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'single_use_per_customer' => 'boolean',
            'active' => 'boolean',
            'featured_on_sticker' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PromotionCode $promotion): void {
            $promotion->code = strtoupper(preg_replace('/\s+/', '', trim($promotion->code)));
        });

        static::saved(function (PromotionCode $promotion): void {
            if (! $promotion->featured_on_sticker) {
                return;
            }

            static::query()
                ->where('id', '!=', $promotion->getKey())
                ->where('featured_on_sticker', true)
                ->update(['featured_on_sticker' => false]);
        });
    }
}
