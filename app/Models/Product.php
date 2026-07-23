<?php

namespace App\Models;

use App\Models\Concerns\HasGeneratedSlug;
use App\Services\Pricing\ProductListPriceCalculator;
use App\Services\Pricing\PurchaseCostCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasGeneratedSlug, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'tax_rate_id',
        'default_unit_of_measure_id',
        'name',
        'slug',
        'code',
        'brand',
        'description',
        'image_path',
        'public_description',
        'price_per_kg',
        'purchase_cost_per_unit',
        'purchase_cost_per_unit_gross',
        'markup_percentage',
        'restaurant_markup_percentage',
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

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
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
        return number_format((float) $this->purchase_cost_per_unit_gross, 2, '.', '');
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_public' => 'boolean',
            'is_seasonal' => 'boolean',
            'price_per_kg' => 'decimal:2',
            'purchase_cost_per_unit' => 'decimal:4',
            'purchase_cost_per_unit_gross' => 'decimal:4',
            'markup_percentage' => 'decimal:2',
            'restaurant_markup_percentage' => 'decimal:2',
            'base_price_per_unit' => 'decimal:4',
            'restaurant_price_per_unit' => 'decimal:4',
            'base_minimum_quantity' => 'decimal:3',
            'restaurant_minimum_quantity' => 'decimal:3',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $calculator = app(ProductListPriceCalculator::class);
            $purchaseCosts = app(PurchaseCostCalculator::class);
            $taxPercentage = (float) (TaxRate::query()->whereKey($product->tax_rate_id)->value('percentage') ?? 0);
            $hasGrossCost = array_key_exists('purchase_cost_per_unit_gross', $product->getAttributes());

            if ($product->isDirty('purchase_cost_per_unit_gross') || ($product->isDirty('tax_rate_id') && $hasGrossCost)) {
                $product->purchase_cost_per_unit = $purchaseCosts->netFromGross(
                    $product->purchase_cost_per_unit_gross,
                    $taxPercentage,
                );
            } elseif ($product->isDirty('purchase_cost_per_unit') || (! $product->exists && ! $hasGrossCost)) {
                $product->purchase_cost_per_unit_gross = $purchaseCosts->grossFromNet(
                    $product->purchase_cost_per_unit,
                    $taxPercentage,
                );
            }

            if (! $product->exists && ! array_key_exists('restaurant_markup_percentage', $product->getAttributes())) {
                $product->restaurant_markup_percentage = $product->markup_percentage ?? 0;
            }

            if ($product->isDirty('base_price_per_unit')) {
                $product->markup_percentage = $calculator->markupFromPrice($product->purchase_cost_per_unit, $product->base_price_per_unit);
            } elseif ($product->isDirty('purchase_cost_per_unit') || $product->isDirty('markup_percentage') || ! $product->exists) {
                $product->base_price_per_unit = $calculator->priceFromMarkup($product->purchase_cost_per_unit, $product->markup_percentage);
            }

            if ($product->isDirty('restaurant_price_per_unit')) {
                $product->restaurant_markup_percentage = $calculator->markupFromPrice($product->purchase_cost_per_unit, $product->restaurant_price_per_unit);
            } elseif ($product->isDirty('purchase_cost_per_unit') || $product->isDirty('restaurant_markup_percentage') || ! $product->exists) {
                $product->restaurant_price_per_unit = $calculator->priceFromMarkup($product->purchase_cost_per_unit, $product->restaurant_markup_percentage ?? $product->markup_percentage);
            }

            $product->price_per_kg = $product->base_price_per_unit;
            $product->is_public = $product->active;
        });
    }
}
