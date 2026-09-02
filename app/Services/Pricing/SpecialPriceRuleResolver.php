<?php

namespace App\Services\Pricing;

use App\Enums\SpecialPriceAudience;
use App\Models\Partner;
use App\Models\Product;

/**
 * Restituisce il prezzo/ricarico "di listino" di un prodotto per un dato destinatario.
 *
 * Le regole in "Prezzi speciali" non vengono più risolte qui in tempo reale: agiscono
 * solo quando un amministratore preme "Applica" (vedi SpecialPriceRuleApplier), che
 * scrive il ricarico direttamente sul prodotto (o, per un partner specifico, sul suo
 * listino). Questo metodo legge quindi sempre e solo il valore già salvato.
 */
class SpecialPriceRuleResolver
{
    public function details(Product $product, SpecialPriceAudience $audience, ?Partner $partner = null): array
    {
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
}
