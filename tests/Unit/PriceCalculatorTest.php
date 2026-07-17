<?php

namespace Tests\Unit;

use App\Services\Pricing\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function test_weighted_prices_are_calculated_and_rounded_to_cents(): void
    {
        $calculator = new PriceCalculator;

        $this->assertSame('5.96', $calculator->lineTotal('14.90', '0.400'));
        $this->assertSame('10.43', $calculator->lineTotal('4.17', '2.500'));
        $this->assertSame('16.39', $calculator->sum(['5.96', '10.43']));
        $this->assertSame('27.00', $calculator->discountedPrice('30.00', '10'));
        $this->assertSame('26.99', $calculator->discountedPrice('29.99', '10'));
    }
}
