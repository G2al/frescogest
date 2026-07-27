<?php

namespace App\Enums;

enum EmployeeCompensationType: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Hourly => 'Oraria',
            self::Daily => 'Giornaliera',
            self::Monthly => 'Mensile',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
