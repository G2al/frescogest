<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Partners\PartnerPriceListService;
use App\Services\Pricing\CustomerPriceListService;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(CustomerPriceListService::class)->syncProduct($product);
        app(PartnerPriceListService::class)->syncProduct($product);
    }

    public function updated(Product $product): void
    {
        if ($product->wasChanged([
            'purchase_cost_per_unit',
            'partner_markup_percentage',
            'partner_price_per_unit',
            'product_category_id',
            'active',
        ])) {
            app(PartnerPriceListService::class)->syncProduct($product);
        }
    }
}
