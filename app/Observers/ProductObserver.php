<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Pricing\CustomerPriceListService;

class ProductObserver
{
    public function created(Product $product): void
    {
        app(CustomerPriceListService::class)->syncProduct($product);
    }
}
