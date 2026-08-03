<?php

namespace App\Enums;

enum StoreClosureType: string
{
    case Recurring = 'recurring';
    case SpecificDate = 'specific_date';
    case FullDayRange = 'full_day_range';

    public function label(): string
    {
        return match ($this) {
            self::Recurring => 'Ricorrente',
            self::SpecificDate => 'Data specifica',
            self::FullDayRange => 'Giorni interi',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
