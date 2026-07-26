<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class EmployeeWorkShift extends Model
{
    protected $fillable = [
        'employee_id',
        'work_date',
        'started_at',
        'ended_at',
        'break_minutes',
        'expected_minutes',
        'worked_minutes',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $shift): void {
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

            if (! $shift->expected_minutes) {
                $shift->expected_minutes = (int) $shift->employee()->value('expected_daily_minutes');
            }
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
        return $this->worked_minutes - $this->expected_minutes;
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
