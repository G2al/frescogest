<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasure extends Model
{
    protected $fillable = ['name', 'symbol', 'type', 'active'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'default_unit_of_measure_id');
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
