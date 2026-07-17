<?php

namespace App\Services\Pricing;

class PriceCalculator
{
    public function lineTotal(string|int|float $pricePerKg, string|int|float $quantity): string
    {
        $priceCents = $this->scaledInteger($pricePerKg, 2);
        $quantityThousandths = $this->scaledInteger($quantity, 3);
        $totalCents = intdiv(($priceCents * $quantityThousandths) + 500, 1000);

        return $this->formatCents($totalCents);
    }

    public function sum(array $amounts): string
    {
        $totalCents = array_sum(array_map(fn ($amount): int => $this->scaledInteger($amount, 2), $amounts));

        return $this->formatCents($totalCents);
    }

    public function discountedPrice(
        string|int|float $price,
        string|int|float $discountPercentage,
    ): string {
        $priceCents = $this->scaledInteger($price, 2);
        $discountHundredths = $this->scaledInteger($discountPercentage, 2);
        $discountedCents = intdiv(
            ($priceCents * (10000 - $discountHundredths)) + 5000,
            10000,
        );

        return $this->formatCents($discountedCents);
    }

    private function scaledInteger(string|int|float $value, int $decimals): int
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $scale = 10 ** $decimals;
        $scaledFraction = (int) str_pad(substr($fraction, 0, $decimals), $decimals, '0');
        $result = ((int) $whole * $scale) + $scaledFraction;

        if ((int) ($fraction[$decimals] ?? 0) >= 5) {
            $result++;
        }

        return $result;
    }

    private function formatCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
