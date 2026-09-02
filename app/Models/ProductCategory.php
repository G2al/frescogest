<?php

namespace App\Models;

use App\Models\Concerns\HasGeneratedSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasGeneratedSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'catalog_color',
        'is_public',
        'sort_order',
        'sort_alphabetically',
        'active',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function producers(): HasMany
    {
        return $this->hasMany(Producer::class);
    }

    public function customerDiscounts(): HasMany
    {
        return $this->hasMany(CustomerCategoryDiscount::class);
    }

    public function specialPriceRules(): HasMany
    {
        return $this->hasMany(SpecialPriceRule::class);
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where('is_public', true)
            ->whereNotNull('slug');
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'is_public' => 'boolean',
            'sort_alphabetically' => 'boolean',
        ];
    }
}
