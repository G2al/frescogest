<?php

namespace App\Services\Pricing;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use Illuminate\Support\Collection;

class ProductPricingService
{
    public function __construct(private readonly PriceCalculator $calculator) {}

    public function apply(Collection $products, ?Customer $customer): Collection
    {
        if ($customer === null) {
            return $products->each(fn (Product $product) => $this->applyDetails(
                $product,
                $this->calculate($product, null),
            ));
        }

        $customer->unsetRelation('productPrices')->unsetRelation('categoryDiscounts');
        $customer->load(['productPrices', 'categoryDiscounts']);

        return $products->each(fn (Product $product) => $this->applyDetails(
            $product,
            $this->calculate($product, $customer),
        ));
    }

    public function resolve(Product $product, Customer $customer): string
    {
        return $this->details($product, $customer)['price'];
    }

    public function details(Product $product, Customer $customer): array
    {
        $customer->unsetRelation('productPrices')->unsetRelation('categoryDiscounts');
        $customer->load(['productPrices', 'categoryDiscounts']);

        return $this->calculate($product, $customer);
    }

    public function detailsForRow(CustomerProductPrice $price): array
    {
        $price->loadMissing(['customer.productPrices', 'customer.categoryDiscounts', 'product']);

        return $this->calculate($price->product, $price->customer);
    }

    private function calculate(Product $product, ?Customer $customer): array
    {
        $restaurant = $customer?->type === CustomerType::Restaurant;
        $listPrice = $restaurant
            ? $product->restaurant_price_per_unit
            : $product->base_price_per_unit;
        $minimum = $restaurant
            ? $product->restaurant_minimum_quantity
            : $product->base_minimum_quantity;
        $listSource = $restaurant ? 'restaurant' : 'base';

        if ($customer === null) {
            return $this->result($listPrice, $minimum, $listSource);
        }

        $custom = $customer->productPrices->firstWhere('product_id', $product->id);

        if ($custom?->custom_price_per_unit !== null) {
            return $this->result(
                $custom->custom_price_per_unit,
                $custom->custom_minimum_quantity ?? $minimum,
                'product',
            );
        }

        $discount = $customer->categoryDiscounts
            ->firstWhere('product_category_id', $product->product_category_id)
            ?->discount_percentage;
        $source = 'category';

        if ($discount === null || (float) $discount <= 0) {
            $discount = $customer->global_discount_percentage;
            $source = 'global';
        }

        if ($discount !== null && (float) $discount > 0) {
            return $this->result(
                $this->calculator->discountedPrice($listPrice, $discount),
                $custom?->custom_minimum_quantity ?? $minimum,
                $source,
                $discount,
            );
        }

        return $this->result(
            $listPrice,
            $custom?->custom_minimum_quantity ?? $minimum,
            $custom?->custom_minimum_quantity !== null ? 'minimum' : $listSource,
        );
    }

    private function result(
        string|int|float $price,
        string|int|float $minimum,
        string $source,
        string|int|float|null $discount = null,
    ): array {
        return [
            'price' => number_format((float) $price, 2, '.', ''),
            'minimum_quantity' => number_format((float) $minimum, 3, '.', ''),
            'source' => $source,
            'discount_percentage' => $discount === null ? null : number_format((float) $discount, 2, '.', ''),
        ];
    }

    private function applyDetails(Product $product, array $details): void
    {
        $product->setAttribute('effective_price_per_unit', $details['price']);
        $product->setAttribute('effective_price_per_kg', $details['price']);
        $product->setAttribute('minimum_quantity', $details['minimum_quantity']);
        $product->setAttribute('has_personalized_price', in_array($details['source'], ['product', 'category', 'global', 'minimum'], true));
        $product->setAttribute('pricing_source', $details['source']);
        $product->setAttribute('discount_percentage', $details['discount_percentage']);
    }
}
