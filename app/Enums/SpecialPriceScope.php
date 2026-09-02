<?php

namespace App\Enums;

enum SpecialPriceScope: string
{
    case Global = 'global';
    case Category = 'category';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Tutti i prodotti',
            self::Category => 'Intera categoria',
            self::Product => 'Singolo prodotto',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $scope): array => [$scope->value => $scope->label()])
            ->all();
    }
}
