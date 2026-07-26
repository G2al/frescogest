<?php

namespace App\Services\Partners;

use App\Models\Partner;
use App\Models\PartnerProductPrice;
use App\Models\Product;
use App\Services\Pricing\PriceCalculator;

class PartnerDeliveryDocumentPricingService
{
    public function __construct(private readonly PriceCalculator $calculator) {}

    public function product(Partner $partner, int|string|null $productId): array
    {
        if (blank($productId)) {
            return [];
        }

        $product = Product::query()
            ->with(['taxRate', 'defaultUnitOfMeasure'])
            ->where('active', true)
            ->find($productId);

        if (! $product) {
            return [];
        }

        $price = PartnerProductPrice::query()
            ->whereBelongsTo($partner)
            ->whereBelongsTo($product)
            ->value('purchase_price_net');

        return [
            'price' => number_format((float) ($price ?? $product->base_price_per_unit), 4, '.', ''),
            'unit_symbol' => $product->defaultUnitOfMeasure->symbol,
        ];
    }

    public function totals(array $items): array
    {
        $netAmounts = [];
        $taxAmounts = [];

        foreach ($items as $item) {
            if (blank($item['product_id'] ?? null) || (float) ($item['quantity'] ?? 0) <= 0) {
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
            $netAmounts[] = $net;
            $taxAmounts[] = $this->calculator->tax($net, $product->taxRate->percentage);
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
