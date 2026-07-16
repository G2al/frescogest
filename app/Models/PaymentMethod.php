<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'active'];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
