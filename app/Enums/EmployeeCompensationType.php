<?php

namespace App\Enums;

enum EmployeeCompensationType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
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
