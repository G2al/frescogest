<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PartnerDailyReceipt extends Model
{
    protected $fillable = ['partner_id', 'receipt_date', 'gross_amount', 'notes'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    protected function casts(): array
    {
        return ['receipt_date' => 'date', 'gross_amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (PartnerDailyReceipt $receipt): void {
            $duplicate = static::query()
                ->where('partner_id', $receipt->partner_id)
                ->whereDate('receipt_date', $receipt->receipt_date)
                ->when($receipt->exists, fn ($query) => $query->whereKeyNot($receipt->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'receipt_date' => 'Per questa data è già stato registrato un incasso.',
                ]);
            }
        });
    }
}
