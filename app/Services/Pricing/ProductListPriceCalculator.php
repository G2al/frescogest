<?php

namespace App\Services\Pricing;

class ProductListPriceCalculator
{
    public function calculate(string|int|float|null $purchaseCost, string|int|float|null $markupPercentage): array
    {
        $basePrice = $this->priceFromMarkup($purchaseCost, $markupPercentage);

        return [
            'base_price' => $basePrice,
            'restaurant_price' => $basePrice,
        ];
    }

    public function priceFromMarkup(string|int|float|null $purchaseCost, string|int|float|null $markupPercentage): float
    {
        $cost = max(0, (float) $purchaseCost);
        $markup = max(0, (float) $markupPercentage);

        return round($cost * (1 + ($markup / 100)), 4);
    }

    public function markupFromPrice(string|int|float|null $purchaseCost, string|int|float|null $price): float
    {
        $cost = max(0, (float) $purchaseCost);

        if ($cost <= 0) {
            return 0;
        }

        return round(max(0, (((float) $price / $cost) - 1) * 100), 2);
    }
}
