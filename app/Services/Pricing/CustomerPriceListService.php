<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;

class CustomerPriceListService
{
    public function syncCustomer(Customer $customer): void
    {
        $now = now();
        $rows = Product::query()->pluck('id')->map(fn (int $productId): array => [
            'customer_id' => $customer->id,
            'product_id' => $productId,
            'custom_price_per_kg' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            CustomerProductPrice::query()->insertOrIgnore($chunk);
        }
    }

    public function syncProduct(Product $product): void
    {
        $now = now();
        $rows = Customer::query()->pluck('id')->map(fn (int $customerId): array => [
            'customer_id' => $customerId,
            'product_id' => $product->id,
            'custom_price_per_kg' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        foreach (array_chunk($rows, 500) as $chunk) {
            CustomerProductPrice::query()->insertOrIgnore($chunk);
        }
    }

    public function resetCustomer(Customer $customer): void
    {
        $customer->productPrices()->update(['custom_price_per_kg' => null]);
    }
}
