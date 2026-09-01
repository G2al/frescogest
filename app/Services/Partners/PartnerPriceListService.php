<?php

namespace App\Services\Partners;

use App\Enums\SpecialPriceAudience;
use App\Models\Partner;
use App\Models\PartnerProductPrice;
use App\Models\Product;
use App\Services\Pricing\ProductListPriceCalculator;
use App\Services\Pricing\SpecialPriceRuleResolver;

class PartnerPriceListService
{
    public function __construct(
        private readonly SpecialPriceRuleResolver $specialPrices,
        private readonly ProductListPriceCalculator $calculator,
    ) {}

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

    public function syncDefaults(): void
    {
        Partner::query()
            ->where('active', true)
            ->each(fn (Partner $partner) => $this->syncPartner($partner));
    }

    private function sync(Partner $partner, Product $product): void
    {
        $defaultPrice = $this->specialPrices->details(
            $product,
            SpecialPriceAudience::Partners,
            $partner,
        )['price'];
        $price = PartnerProductPrice::query()->firstOrCreate(
            [
                'partner_id' => $partner->id,
                'product_id' => $product->id,
            ],
            [
                'purchase_price_net' => $defaultPrice,
                'purchase_price_is_custom' => false,
                'sale_price_net' => round((float) $defaultPrice * 2, 4),
                'markup_percentage' => 100,
            ],
        );

        if (! $price->wasRecentlyCreated && ! $price->purchase_price_is_custom) {
            PartnerProductPrice::query()->whereKey($price->getKey())->update([
                'purchase_price_net' => $defaultPrice,
                'purchase_price_is_custom' => false,
                'sale_price_net' => $this->calculator->priceFromMarkup($defaultPrice, $price->markup_percentage),
                'updated_at' => now(),
            ]);
        }
    }
}
