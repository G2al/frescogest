<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Product;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\ProductPricingService;

class OrderItemSnapshotService
{
    public function __construct(
        private readonly ProductPricingService $pricing,
        private readonly PriceCalculator $calculator,
    ) {}

    public function enrich(array $data, Order $order): array
    {
        $product = Product::query()->findOrFail($data['product_id']);
        $pricePerKg = $this->pricing->resolve($product, $order->customer);

        return [
            ...$data,
            'product_name' => $product->name,
            'price_per_kg' => $pricePerKg,
            'line_total' => $this->calculator->lineTotal($pricePerKg, $data['quantity']),
            'unit_of_measure_name' => 'Chilogrammi',
            'unit_of_measure_symbol' => 'kg',
        ];
    }

    public function recalculate(Order $order): void
    {
        $order->update([
            'total_amount' => $this->calculator->sum($order->items()->pluck('line_total')->all()),
        ]);
    }
}
