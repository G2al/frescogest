<?php

namespace App\Enums;

enum SpecialPriceAudience: string
{
    case PrivateCustomers = 'private';
    case Restaurants = 'restaurant';
    case Partners = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::PrivateCustomers => 'Privati',
            self::Restaurants => 'Ristoratori',
            self::Partners => 'Partner / Angela',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PrivateCustomers => 'success',
            self::Restaurants => 'info',
            self::Partners => 'warning',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $audience): array => [$audience->value => $audience->label()])
            ->all();
    }
}
