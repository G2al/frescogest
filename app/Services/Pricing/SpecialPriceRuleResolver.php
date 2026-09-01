<?php

namespace App\Services\Pricing;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Models\Partner;
use App\Models\Product;
use App\Models\SpecialPriceRule;
use Illuminate\Support\Collection;

class SpecialPriceRuleResolver
{
    private array $rules = [];

    public function __construct(private readonly ProductListPriceCalculator $calculator) {}

    public function details(Product $product, SpecialPriceAudience $audience, ?Partner $partner = null): array
    {
        $rule = $this->matchingRule($product, $audience, $partner);

        if ($rule) {
            return [
                'price' => $this->calculator->priceFromMarkup($product->purchase_cost_per_unit, $rule->markup_percentage),
                'markup_percentage' => (float) $rule->markup_percentage,
                'source' => $rule->scope_type === SpecialPriceScope::Product ? 'special_product' : 'special_category',
                'rule' => $rule,
            ];
        }

        [$price, $markup] = match ($audience) {
            SpecialPriceAudience::PrivateCustomers => [$product->base_price_per_unit, $product->markup_percentage],
            SpecialPriceAudience::Restaurants => [$product->restaurant_price_per_unit, $product->restaurant_markup_percentage],
            SpecialPriceAudience::Partners => [$product->partner_price_per_unit, $product->partner_markup_percentage],
        };

        return [
            'price' => (float) $price,
            'markup_percentage' => (float) $markup,
            'source' => 'default',
            'rule' => null,
        ];
    }

    private function matchingRule(Product $product, SpecialPriceAudience $audience, ?Partner $partner): ?SpecialPriceRule
    {
        return $this->rules($audience, $partner)
            ->filter(fn (SpecialPriceRule $rule): bool => $rule->product_id === $product->getKey()
                || $rule->product_category_id === $product->product_category_id)
            ->sort(function (SpecialPriceRule $left, SpecialPriceRule $right): int {
                $leftPriority = [$left->product_id !== null ? 0 : 1, $left->partner_id !== null ? 0 : 1, -$left->getKey()];
                $rightPriority = [$right->product_id !== null ? 0 : 1, $right->partner_id !== null ? 0 : 1, -$right->getKey()];

                return $leftPriority <=> $rightPriority;
            })
            ->first();
    }

    private function rules(SpecialPriceAudience $audience, ?Partner $partner): Collection
    {
        $key = $audience->value.':'.($partner?->getKey() ?? 'all');

        return $this->rules[$key] ??= SpecialPriceRule::query()
            ->active()
            ->where('audience', $audience)
            ->when(
                $audience === SpecialPriceAudience::Partners,
                fn ($query) => $query->where(fn ($partners) => $partners
                    ->whereNull('partner_id')
                    ->when($partner, fn ($specific) => $specific->orWhere('partner_id', $partner->getKey()))),
            )
            ->get();
    }
}
