<?php

namespace App\Services\Pricing;

class PriceCalculator
{
    public function lineTotal(string|int|float $unitPrice, string|int|float $quantity): string
    {
        $priceCents = $this->scaledInteger($unitPrice, 2);
        $quantityThousandths = $this->scaledInteger($quantity, 3);
        $totalCents = intdiv(($priceCents * $quantityThousandths) + 500, 1000);

        return $this->formatCents($totalCents);
    }

    public function tax(string|int|float $netAmount, string|int|float $percentage): string
    {
        $netCents = $this->scaledInteger($netAmount, 2);
        $percentageHundredths = $this->scaledInteger($percentage, 2);
        $taxCents = intdiv(($netCents * $percentageHundredths) + 5000, 10000);

        return $this->formatCents($taxCents);
    }

    public function percentage(string|int|float $amount, string|int|float $base): string
    {
        if ((float) $base <= 0) {
            return '0.00';
        }

        return number_format(((float) $amount / (float) $base) * 100, 2, '.', '');
    }

    public function sum(array $amounts): string
    {
        $totalCents = array_sum(array_map(fn ($amount): int => $this->scaledInteger($amount, 2), $amounts));

        return $this->formatCents($totalCents);
    }

    public function difference(string|int|float $minuend, string|int|float $subtrahend): string
    {
        return $this->formatCents(
            $this->scaledInteger($minuend, 2) - $this->scaledInteger($subtrahend, 2),
        );
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
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return $sign.intdiv($absolute, 100).'.'.str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }
}
