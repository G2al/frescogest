<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'user_id',
        'type',
        'first_name',
        'last_name',
        'vat_number',
        'tax_code',
        'email',
        'phone',
        'phone_normalized',
        'billing_address',
        'delivery_address',
        'city',
        'postal_code',
        'province',
        'notes',
        'active',
        'global_discount_percentage',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name ?: trim("{$this->first_name} {$this->last_name}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function productPrices(): HasMany
    {
        return $this->hasMany(CustomerProductPrice::class);
    }

    public function categoryDiscounts(): HasMany
    {
        return $this->hasMany(CustomerCategoryDiscount::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'type' => CustomerType::class,
            'global_discount_percentage' => 'decimal:2',
        ];
    }
}
