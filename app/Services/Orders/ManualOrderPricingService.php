<?php

namespace App\Services\Orders;

use App\Models\Customer;
use App\Models\Product;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\ProductPricingService;

class ManualOrderPricingService
{
    public function __construct(
        private readonly ProductPricingService $pricing,
        private readonly PriceCalculator $calculator,
    ) {}

    public function product(int|string|null $productId, int|string|null $customerId): array
    {
        if (blank($productId) || blank($customerId)) {
            return [];
        }

        $product = Product::query()
            ->with(['taxRate', 'defaultUnitOfMeasure'])
            ->find($productId);
        $customer = Customer::query()->find($customerId);

        if (! $product || ! $customer) {
            return [];
        }

        $details = $this->pricing->details($product, $customer);

        return [
            'price' => $details['price'],
            'minimum_quantity' => $details['minimum_quantity'],
            'tax_percentage' => number_format((float) $product->taxRate->percentage, 2, '.', ''),
            'unit_symbol' => $product->defaultUnitOfMeasure->symbol,
        ];
    }

    public function totals(array $items): array
    {
        $netAmounts = [];
        $taxAmounts = [];

        foreach ($items as $item) {
            if (blank($item['product_id'] ?? null) || blank($item['quantity'] ?? null)) {
                continue;
            }

            $product = Product::query()->with('taxRate')->find($item['product_id']);

            if (! $product) {
                continue;
            }

            $net = $this->calculator->lineTotal(
                $item['unit_price_net'] ?? 0,
                $item['quantity'],
            );
            $tax = $this->calculator->tax($net, $product->taxRate->percentage);
            $netAmounts[] = $net;
            $taxAmounts[] = $tax;
        }

        $net = $this->calculator->sum($netAmounts);
        $tax = $this->calculator->sum($taxAmounts);

        return [
            'net' => $net,
            'tax' => $tax,
            'gross' => $this->calculator->sum([$net, $tax]),
        ];
    }
}
