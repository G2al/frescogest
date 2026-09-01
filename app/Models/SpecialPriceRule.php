<?php

namespace App\Models;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Services\Partners\PartnerPriceListService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPriceRule extends Model
{
    protected $fillable = [
        'name',
        'audience',
        'scope_type',
        'partner_id',
        'product_category_id',
        'product_id',
        'markup_percentage',
        'active',
        'notes',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function getTargetLabelAttribute(): string
    {
        return $this->scope_type === SpecialPriceScope::Product
            ? ($this->product?->name ?? 'Prodotto non disponibile')
            : ($this->productCategory?->name ?? 'Categoria non disponibile');
    }

    protected function casts(): array
    {
        return [
            'audience' => SpecialPriceAudience::class,
            'scope_type' => SpecialPriceScope::class,
            'markup_percentage' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            if ($rule->scope_type === SpecialPriceScope::Product) {
                $rule->product_category_id = null;
            } else {
                $rule->product_id = null;
            }

            if ($rule->audience !== SpecialPriceAudience::Partners) {
                $rule->partner_id = null;
            }
        });

        static::saved(fn (self $rule) => $rule->syncPartnerPrices());
        static::deleted(fn (self $rule) => $rule->syncPartnerPrices());
    }

    private function syncPartnerPrices(): void
    {
        if ($this->audience === SpecialPriceAudience::Partners
            || $this->getOriginal('audience') === SpecialPriceAudience::Partners->value) {
            app(PartnerPriceListService::class)->syncDefaults();
        }
    }
}
