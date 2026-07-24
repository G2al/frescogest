<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PartnerDailyWaste extends Model
{
    protected $fillable = ['partner_id', 'waste_date', 'amount', 'notes'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    protected function casts(): array
    {
        return ['waste_date' => 'date', 'amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (PartnerDailyWaste $waste): void {
            $duplicate = static::query()
                ->where('partner_id', $waste->partner_id)
                ->whereDate('waste_date', $waste->waste_date)
                ->when($waste->exists, fn ($query) => $query->whereKeyNot($waste->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'waste_date' => 'Per questa data è già stato registrato uno scarto.',
                ]);
            }
        });
    }
}
