<?php

namespace App\Enums;

enum StoreClosureType: string
{
    case Recurring = 'recurring';
    case SpecificDate = 'specific_date';

    public function label(): string
    {
        return match ($this) {
            self::Recurring => 'Ricorrente',
            self::SpecificDate => 'Data specifica',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
