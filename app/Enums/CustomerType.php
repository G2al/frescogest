<?php

namespace App\Enums;

enum CustomerType: string
{
    case Private = 'private';
    case Restaurant = 'restaurant';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privato',
            self::Restaurant => 'Ristoratore',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $type): array => [$type->value => $type->label()],
        )->all();
    }
}
