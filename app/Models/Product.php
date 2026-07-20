<?php

namespace App\Models;

use App\Services\Pricing\ProductListPriceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'tax_rate_id',
        'default_unit_of_measure_id',
        'name',
        'slug',
        'code',
        'description',
        'image_path',
        'public_description',
        'price_per_kg',
        'purchase_cost_per_unit',
        'markup_percentage',
        'base_price_per_unit',
        'restaurant_price_per_unit',
        'base_minimum_quantity',
        'restaurant_minimum_quantity',
        'is_public',
        'is_seasonal',
        'sort_order',
        'notes',
        'active',
    ];

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function defaultUnitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'default_unit_of_measure_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerProductPrice::class);
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where('base_price_per_unit', '>', 0)
            ->whereNotNull('slug')
            ->whereHas('productCategory', fn (Builder $category): Builder => $category->publicCatalog());
    }

    public function getPurchaseCostGrossAttribute(): string
    {
        $percentage = (float) ($this->taxRate?->percentage ?? 0);

        return number_format((float) $this->purchase_cost_per_unit * (1 + ($percentage / 100)), 2, '.', '');
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_public' => 'boolean',
            'is_seasonal' => 'boolean',
            'price_per_kg' => 'decimal:2',
            'purchase_cost_per_unit' => 'decimal:4',
            'markup_percentage' => 'decimal:2',
            'base_price_per_unit' => 'decimal:4',
            'restaurant_price_per_unit' => 'decimal:4',
            'base_minimum_quantity' => 'decimal:3',
            'restaurant_minimum_quantity' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $prices = app(ProductListPriceCalculator::class)->calculate(
                $product->purchase_cost_per_unit,
                $product->markup_percentage,
            );

            $product->base_price_per_unit = $prices['base_price'];
            $product->restaurant_price_per_unit = $prices['restaurant_price'];
            $product->price_per_kg = $prices['base_price'];
            $product->is_public = $product->active;
        });
    }
}
