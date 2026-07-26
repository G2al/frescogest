<?php

namespace App\Enums;

enum PromotionRule: string
{
    case AnyOrder = 'any';
    case FirstOrder = 'first_order';

    public function label(): string
    {
        return match ($this) {
            self::AnyOrder => 'Qualsiasi ordine',
            self::FirstOrder => 'Solo primo ordine',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $rule): array => [$rule->value => $rule->label()])
            ->all();
    }
}
