<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'catalog_color',
        'is_public',
        'sort_order',
        'active',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customerDiscounts(): HasMany
    {
        return $this->hasMany(CustomerCategoryDiscount::class);
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
        ];
    }
}
