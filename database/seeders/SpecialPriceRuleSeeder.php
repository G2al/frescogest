<?php

namespace Database\Seeders;

use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Models\SpecialPriceRule;
use Illuminate\Database\Seeder;

/**
 * Crea le 3 regole di base (una per destinatario), ambito "Tutti i prodotti".
 * Non le applica: restano in elenco finché non premi "Applica ricarichi di base"
 * (o "Applica" sulla singola regola) nella resource "Prezzi speciali".
 */
class SpecialPriceRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Ricarico base privati',
                'audience' => SpecialPriceAudience::PrivateCustomers,
                'markup_percentage' => 80,
                'notes' => 'Regola di base: ricarico dell\'80% sul costo di acquisto per i clienti privati registrati.',
            ],
            [
                'name' => 'Ricarico base ristoratori',
                'audience' => SpecialPriceAudience::Restaurants,
                'markup_percentage' => 45,
                'notes' => 'Regola di base: ricarico del 45% sul costo di acquisto per i clienti ristoratori.',
            ],
            [
                'name' => 'Ricarico base partner',
                'audience' => SpecialPriceAudience::Partners,
                'markup_percentage' => 33,
                'notes' => 'Regola di base: ricarico del 33% sul costo di acquisto per tutti i partner (es. Angela).',
            ],
        ];

        foreach ($rules as $rule) {
            SpecialPriceRule::query()->firstOrCreate(
                [
                    'name' => $rule['name'],
                    'audience' => $rule['audience'],
                    'scope_type' => SpecialPriceScope::Global,
                ],
                [
                    'markup_percentage' => $rule['markup_percentage'],
                    'active' => true,
                    'notes' => $rule['notes'],
                ],
            );
        }
    }
}
