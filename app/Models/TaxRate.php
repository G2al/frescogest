<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRate extends Model
{
    protected $fillable = ['name', 'percentage', 'active'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
