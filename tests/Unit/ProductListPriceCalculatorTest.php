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
}
