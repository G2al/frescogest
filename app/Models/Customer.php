<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'user_id',
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

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
