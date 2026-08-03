<?php

namespace App\Models;

use App\Enums\StoreClosureType;
use Illuminate\Database\Eloquent\Model;

class StoreClosureSchedule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'weekdays',
        'closure_date',
        'closure_end_date',
        'starts_at',
        'ends_at',
        'message',
        'active',
    ];

    public static function weekdayOptions(): array
    {
        return [
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica',
        ];
    }

    public function getScheduleDescriptionAttribute(): string
    {
        if ($this->type === StoreClosureType::FullDayRange) {
            if (! $this->closure_date || ! $this->closure_end_date) {
                return 'Periodo non impostato';
            }

            return $this->closure_date->isSameDay($this->closure_end_date)
                ? $this->closure_date->format('d/m/Y').' · tutto il giorno'
                : sprintf(
                    'Dal %s al %s · giorni interi',
                    $this->closure_date->format('d/m/Y'),
                    $this->closure_end_date->format('d/m/Y'),
                );
        }

        if ($this->type === StoreClosureType::SpecificDate) {
            return $this->closure_date?->format('d/m/Y') ?? 'Data non impostata';
        }

        $labels = collect($this->weekdays ?? [])
            ->map(fn (int|string $day): ?string => self::weekdayOptions()[(int) $day] ?? null)
            ->filter()
            ->values();

        return $labels->count() === 7 ? 'Tutti i giorni' : $labels->implode(', ');
    }

    protected function casts(): array
    {
        return [
            'type' => StoreClosureType::class,
            'weekdays' => 'array',
            'closure_date' => 'date',
            'closure_end_date' => 'date',
            'active' => 'boolean',
        ];
    }
}
