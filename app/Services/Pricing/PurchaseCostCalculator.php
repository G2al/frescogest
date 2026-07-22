<?php

namespace App\Services\Pricing;

class PurchaseCostCalculator
{
    public function netFromGross(string|int|float|null $grossAmount, string|int|float|null $percentage): float
    {
        $gross = max(0, (float) $grossAmount);
        $taxRate = max(0, (float) $percentage);

        return round($gross / (1 + ($taxRate / 100)), 4);
    }

    public function grossFromNet(string|int|float|null $netAmount, string|int|float|null $percentage): float
    {
        $net = max(0, (float) $netAmount);
        $taxRate = max(0, (float) $percentage);

        return round($net * (1 + ($taxRate / 100)), 4);
    }
}
