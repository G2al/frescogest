<?php

namespace App\Services\Partners;

use App\Models\Partner;
use App\Models\PartnerProductPrice;
use App\Models\Product;

class PartnerPriceListService
{
    public function syncPartner(Partner $partner): void
    {
        Product::query()
            ->where('active', true)
            ->each(fn (Product $product) => $this->sync($partner, $product));
    }

    public function syncProduct(Product $product): void
    {
        Partner::query()
            ->where('active', true)
            ->each(fn (Partner $partner) => $this->sync($partner, $product));
    }

    private function sync(Partner $partner, Product $product): void
    {
        PartnerProductPrice::query()->firstOrCreate(
            [
                'partner_id' => $partner->id,
                'product_id' => $product->id,
            ],
            [
                'purchase_price_net' => $product->base_price_per_unit,
                'sale_price_net' => round((float) $product->base_price_per_unit * 2, 4),
                'markup_percentage' => 100,
            ],
        );
    }
}
