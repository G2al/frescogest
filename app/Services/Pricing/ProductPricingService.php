<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPricingService
{
    public function apply(Collection $products, ?Customer $customer): Collection
    {
        $customPrices = $customer === null
            ? collect()
            : CustomerProductPrice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->whereNotNull('custom_price_per_kg')
                ->pluck('custom_price_per_kg', 'product_id');

        return $products->each(function (Product $product) use ($customPrices): void {
            $hasCustomPrice = $customPrices->has($product->id);
            $product->setAttribute(
                'effective_price_per_kg',
                $customPrices->get($product->id, $product->price_per_kg),
            );
            $product->setAttribute('has_personalized_price', $hasCustomPrice);
        });
    }

    public function resolve(Product $product, Customer $customer): string
    {
        return CustomerProductPrice::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->value('custom_price_per_kg') ?? $product->price_per_kg;
    }
}
