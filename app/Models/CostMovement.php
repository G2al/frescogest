<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostMovement extends Model
{
    protected $fillable = ['cost_category_id', 'movement_date', 'amount', 'description', 'notes'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    protected function casts(): array
    {
        return ['movement_date' => 'date', 'amount' => 'decimal:2'];
    }
}
