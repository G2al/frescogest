<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerCategoryDiscount extends Model
{
    protected $fillable = [
        'customer_id',
        'product_category_id',
        'discount_percentage',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    protected function casts(): array
    {
        return ['discount_percentage' => 'decimal:2'];
    }
}
