<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPricingService
{
    public function __construct(
        private readonly PriceCalculator $calculator,
    ) {}

    public function apply(Collection $products, ?Customer $customer): Collection
    {
        if ($customer === null) {
            return $products->each(fn (Product $product) => $this->applyDetails($product, [
                'price' => $product->price_per_kg,
                'source' => 'base',
                'discount_percentage' => null,
            ]));
        }

        $customPrices = CustomerProductPrice::query()
            ->where('customer_id', $customer->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->whereNotNull('custom_price_per_kg')
            ->pluck('custom_price_per_kg', 'product_id');
        $categoryDiscounts = $customer->categoryDiscounts()
            ->pluck('discount_percentage', 'product_category_id');

        return $products->each(function (Product $product) use ($categoryDiscounts, $customer, $customPrices): void {
            $this->applyDetails($product, $this->calculate(
                $product,
                $customPrices->get($product->id),
                $categoryDiscounts->get($product->product_category_id),
                $customer->global_discount_percentage,
            ));
        });
    }

    public function resolve(Product $product, Customer $customer): string
    {
        return $this->details($product, $customer)['price'];
    }

    public function details(Product $product, Customer $customer): array
    {
        $customPrice = CustomerProductPrice::query()
            ->where('customer_id', $customer->id)
            ->where('product_id', $product->id)
            ->value('custom_price_per_kg');
        $categoryDiscount = $customer->categoryDiscounts()
            ->where('product_category_id', $product->product_category_id)
            ->value('discount_percentage');

        return $this->calculate(
            $product,
            $customPrice,
            $categoryDiscount,
            $customer->global_discount_percentage,
        );
    }

    public function detailsForRow(CustomerProductPrice $price): array
    {
        $price->loadMissing(['customer.categoryDiscounts', 'product']);
        $categoryDiscount = $price->customer->categoryDiscounts
            ->firstWhere('product_category_id', $price->product->product_category_id)
            ?->discount_percentage;

        return $this->calculate(
            $price->product,
            $price->custom_price_per_kg,
            $categoryDiscount,
            $price->customer->global_discount_percentage,
        );
    }

    private function calculate(
        Product $product,
        string|int|float|null $customPrice,
        string|int|float|null $categoryDiscount,
        string|int|float|null $globalDiscount,
    ): array {
        if ($customPrice !== null) {
            return [
                'price' => number_format((float) $customPrice, 2, '.', ''),
                'source' => 'product',
                'discount_percentage' => null,
            ];
        }

        if ($categoryDiscount !== null && (float) $categoryDiscount > 0) {
            return [
                'price' => $this->calculator->discountedPrice($product->price_per_kg, $categoryDiscount),
                'source' => 'category',
                'discount_percentage' => number_format((float) $categoryDiscount, 2, '.', ''),
            ];
        }

        if ($globalDiscount !== null && (float) $globalDiscount > 0) {
            return [
                'price' => $this->calculator->discountedPrice($product->price_per_kg, $globalDiscount),
                'source' => 'global',
                'discount_percentage' => number_format((float) $globalDiscount, 2, '.', ''),
            ];
        }

        return [
            'price' => $product->price_per_kg,
            'source' => 'base',
            'discount_percentage' => null,
        ];
    }

    private function applyDetails(Product $product, array $details): void
    {
        $product->setAttribute('effective_price_per_kg', $details['price']);
        $product->setAttribute('has_personalized_price', $details['source'] !== 'base');
        $product->setAttribute('pricing_source', $details['source']);
        $product->setAttribute('discount_percentage', $details['discount_percentage']);
    }
}
