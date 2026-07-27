<?php

namespace App\Models;

use App\Enums\EmployeeCompensationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'photo_path',
        'compensation_type',
        'compensation_amount',
        'expected_daily_minutes',
        'hired_on',
        'terminated_on',
        'active',
        'notes',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $employee): void {
            $employee->user?->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workShifts(): HasMany
    {
        return $this->hasMany(EmployeeWorkShift::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getExpectedDailyHoursAttribute(): float
    {
        return round($this->expected_daily_minutes / 60, 2);
    }

    protected function casts(): array
    {
        return [
            'compensation_type' => EmployeeCompensationType::class,
            'compensation_amount' => 'decimal:2',
            'expected_daily_minutes' => 'integer',
            'hired_on' => 'date',
            'terminated_on' => 'date',
            'active' => 'boolean',
        ];
    }
}
