<?php

namespace App\Services\Pricing;

class ProductListPriceCalculator
{
    public function calculate(string|int|float|null $purchaseCost, string|int|float|null $markupPercentage): array
    {
        $cost = max(0, (float) $purchaseCost);
        $markup = max(0, (float) $markupPercentage);
        $basePrice = round($cost * (1 + ($markup / 100)), 4);

        return [
            'base_price' => $basePrice,
            'restaurant_price' => $basePrice,
        ];
    }
}
