<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPricingService
{
    public function apply(Collection $products, ?Customer $customer): Collection
    {
        return $products->each(fn (Product $product) => $this->applyDetails($product));
    }

    public function resolve(Product $product, Customer $customer): string
    {
        return $this->details($product, $customer)['price'];
    }

    public function details(Product $product, Customer $customer): array
    {
        return [
            'price' => number_format((float) $product->base_price_per_unit, 2, '.', ''),
            'minimum_quantity' => '1.000',
            'source' => 'base',
            'discount_percentage' => null,
        ];
    }

    private function applyDetails(Product $product): void
    {
        $product->setAttribute('effective_price_per_unit', number_format((float) $product->base_price_per_unit, 2, '.', ''));
        $product->setAttribute('effective_price_per_kg', number_format((float) $product->base_price_per_unit, 2, '.', ''));
        $product->setAttribute('minimum_quantity', '1.000');
        $product->setAttribute('has_personalized_price', false);
        $product->setAttribute('pricing_source', 'base');
        $product->setAttribute('discount_percentage', null);
    }
}
