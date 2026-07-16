<?php

namespace App\Models;

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
            ->where('is_public', true)
            ->where('price_per_kg', '>', 0)
            ->whereNotNull('slug')
            ->whereHas('productCategory', fn (Builder $category): Builder => $category->publicCatalog());
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_public' => 'boolean',
            'is_seasonal' => 'boolean',
            'price_per_kg' => 'decimal:2',
        ];
    }
}
