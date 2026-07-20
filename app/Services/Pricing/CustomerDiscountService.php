<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerDiscountService
{
    public function setGlobal(Customer $customer, string|int|float|null $percentage): void
    {
        $customer->update([
            'global_discount_percentage' => (float) $percentage > 0 ? $percentage : null,
        ]);
    }

    public function setCategory(
        Customer $customer,
        int $categoryId,
        string|int|float|null $percentage,
    ): void {
        if ((float) $percentage <= 0) {
            $customer->categoryDiscounts()
                ->where('product_category_id', $categoryId)
                ->delete();

            return;
        }

        $customer->categoryDiscounts()->updateOrCreate(
            ['product_category_id' => $categoryId],
            ['discount_percentage' => $percentage],
        );
    }

    public function resetManualPrices(Customer $customer): void
    {
        $customer->productPrices()->update(['custom_price_per_unit' => null, 'custom_minimum_quantity' => null]);
    }

    public function resetAll(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $customer->update(['global_discount_percentage' => null]);
            $customer->categoryDiscounts()->delete();
            $customer->productPrices()->update(['custom_price_per_unit' => null, 'custom_minimum_quantity' => null]);
        });
    }
}
