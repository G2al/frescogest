<?php

namespace App\Enums;

enum PromotionAudience: string
{
    case Everyone = 'all';
    case PrivateCustomers = 'private';
    case Restaurants = 'restaurant';

    public function label(): string
    {
        return match ($this) {
            self::Everyone => 'Tutti',
            self::PrivateCustomers => 'Privati',
            self::Restaurants => 'Ristoratori',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $audience): array => [$audience->value => $audience->label()])
            ->all();
    }
}
