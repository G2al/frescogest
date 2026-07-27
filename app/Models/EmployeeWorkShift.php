<?php

namespace App\Models;

use App\Enums\EmployeeCompensationType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class EmployeeWorkShift extends Model
{
    protected $fillable = [
        'employee_id',
        'work_date',
        'status',
        'started_at',
        'ended_at',
        'break_minutes',
        'expected_minutes',
        'worked_minutes',
        'compensation_type',
        'compensation_rate',
        'pay_amount',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $shift): void {
            $employee = $shift->employee()->first();

            if (! $employee) {
                return;
            }

            $shift->expected_minutes = $shift->expected_minutes ?: (int) $employee->expected_daily_minutes;
            $shift->compensation_type = $shift->compensation_type ?: $employee->compensation_type;
            $shift->compensation_rate = $shift->compensation_rate ?? $employee->compensation_amount;

            if ($shift->status === 'absent') {
                $shift->started_at = null;
                $shift->ended_at = null;
                $shift->break_minutes = 0;
                $shift->worked_minutes = 0;
                $shift->pay_amount = 0;

                return;
            }

            if (! $shift->started_at || ! $shift->ended_at) {
                throw ValidationException::withMessages([
                    'started_at' => 'Indica sia l’orario di inizio sia quello di fine.',
                ]);
            }

            $startedAt = self::time($shift->started_at);
            $endedAt = self::time($shift->ended_at);

            if ($startedAt->equalTo($endedAt)) {
                throw ValidationException::withMessages([
                    'ended_at' => 'L’orario di fine deve essere diverso dall’orario di inizio.',
                ]);
            }

            if ($endedAt->lessThan($startedAt)) {
                $endedAt->addDay();
            }

            $duration = $startedAt->diffInMinutes($endedAt);

            if ((int) $shift->break_minutes >= $duration) {
                throw ValidationException::withMessages([
                    'break_minutes' => 'La pausa deve essere inferiore alla durata del turno.',
                ]);
            }

            $shift->worked_minutes = $duration - (int) $shift->break_minutes;
            $type = $shift->compensation_type instanceof EmployeeCompensationType
                ? $shift->compensation_type
                : EmployeeCompensationType::from((string) $shift->compensation_type);
            $rate = (float) $shift->compensation_rate;
            $shift->pay_amount = match ($type) {
                EmployeeCompensationType::Hourly => round($rate * ($shift->worked_minutes / 60), 2),
                EmployeeCompensationType::Daily => $rate,
                EmployeeCompensationType::Monthly => 0,
            };
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getWorkedDurationAttribute(): string
    {
        return self::duration($this->worked_minutes);
    }

    public function getExpectedDurationAttribute(): string
    {
        return self::duration($this->expected_minutes);
    }

    public function getVarianceMinutesAttribute(): int
    {
        return (int) $this->worked_minutes - (int) $this->expected_minutes;
    }

    public function getVarianceDurationAttribute(): string
    {
        $prefix = $this->variance_minutes > 0 ? '+' : ($this->variance_minutes < 0 ? '−' : '');

        return $prefix.self::duration(abs($this->variance_minutes));
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'break_minutes' => 'integer',
            'expected_minutes' => 'integer',
            'worked_minutes' => 'integer',
            'compensation_type' => EmployeeCompensationType::class,
            'compensation_rate' => 'decimal:2',
            'pay_amount' => 'decimal:2',
        ];
    }

    private static function time(string $value): Carbon
    {
        return Carbon::createFromFormat(strlen($value) === 5 ? 'H:i' : 'H:i:s', $value);
    }

    private static function duration(int $minutes): string
    {
        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }
}
