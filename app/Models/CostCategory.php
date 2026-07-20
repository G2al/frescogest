<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCategory extends Model
{
    protected $fillable = ['name', 'is_monthly', 'active'];

    public function movements(): HasMany
    {
        return $this->hasMany(CostMovement::class);
    }

    protected function casts(): array
    {
        return ['is_monthly' => 'boolean', 'active' => 'boolean'];
    }
}
