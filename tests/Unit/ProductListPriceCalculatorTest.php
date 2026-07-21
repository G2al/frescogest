<?php

namespace Tests\Unit;

use App\Services\Pricing\ProductListPriceCalculator;
use Tests\TestCase;

class ProductListPriceCalculatorTest extends TestCase
{
    public function test_prices_are_calculated_from_cost_and_product_markup(): void
    {
        $calculator = app(ProductListPriceCalculator::class);

        $this->assertSame([
            'base_price' => 10.0,
            'restaurant_price' => 10.0,
        ], $calculator->calculate(5, 100));

        $this->assertSame([
            'base_price' => 7.5,
            'restaurant_price' => 7.5,
        ], $calculator->calculate(5, 50));
    }

    public function test_markup_is_calculated_from_a_manually_entered_price(): void
    {
        $calculator = app(ProductListPriceCalculator::class);

        $this->assertSame(80.0, $calculator->markupFromPrice(25, 45));
        $this->assertSame(40.0, $calculator->markupFromPrice(25, 35));
        $this->assertSame(0.0, $calculator->markupFromPrice(0, 45));
    }
}
