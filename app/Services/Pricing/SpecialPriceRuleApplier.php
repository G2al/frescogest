<?php

namespace App\Services\Pricing;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Models\PartnerProductPrice;
use App\Models\Product;
use App\Models\SpecialPriceRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scrive per davvero il ricarico di una regola sui prodotti nel suo ambito.
 *
 * A differenza della vecchia risoluzione "live", questa è un'azione esplicita e
 * puntuale: agisce solo quando viene chiamata (bottone "Applica" in admin), scrive
 * il valore sul prodotto (o sul listino del partner, se la regola ne indica uno
 * specifico) e da quel momento il prodotto resta liberamente modificabile a mano,
 * senza che la regola continui a "vincerlo" in futuro.
 */
class SpecialPriceRuleApplier
{
    public function __construct(private readonly ProductListPriceCalculator $calculator) {}

    public function apply(SpecialPriceRule $rule): int
    {
        $products = $this->targetProducts($rule);

        return $rule->audience === SpecialPriceAudience::Partners && $rule->partner_id !== null
            ? $this->applyToPartner($rule, $products)
            : $this->applyToProducts($rule, $products);
    }

    private function targetProducts(SpecialPriceRule $rule): Builder
    {
        return Product::query()
            ->where('active', true)
            ->when(
                $rule->scope_type === SpecialPriceScope::Category,
                fn (Builder $query): Builder => $query->where('product_category_id', $rule->product_category_id),
            )
            ->when(
                $rule->scope_type === SpecialPriceScope::Product,
                fn (Builder $query): Builder => $query->where('id', $rule->product_id),
            );
    }

    private function applyToProducts(SpecialPriceRule $rule, Builder $query): int
    {
        $field = match ($rule->audience) {
            SpecialPriceAudience::PrivateCustomers => 'markup_percentage',
            SpecialPriceAudience::Restaurants => 'restaurant_markup_percentage',
            SpecialPriceAudience::Partners => 'partner_markup_percentage',
        };

        $count = 0;

        $query->chunkById(100, function ($products) use ($field, $rule, &$count): void {
            foreach ($products as $product) {
                $product->{$field} = $rule->markup_percentage;
                $product->save();
                $count++;
            }
        });

        return $count;
    }

    private function applyToPartner(SpecialPriceRule $rule, Builder $query): int
    {
        $count = 0;

        $query->chunkById(100, function ($products) use ($rule, &$count): void {
            foreach ($products as $product) {
                $price = PartnerProductPrice::query()->firstOrNew([
                    'partner_id' => $rule->partner_id,
                    'product_id' => $product->id,
                ]);

                if (! $price->exists) {
                    $price->markup_percentage = 100;
                }

                $price->purchase_price_net = $this->calculator->priceFromMarkup(
                    $product->purchase_cost_per_unit,
                    $rule->markup_percentage,
                );
                $price->purchase_price_is_custom = true;
                $price->save();
                $count++;
            }
        });

        return $count;
    }
}
