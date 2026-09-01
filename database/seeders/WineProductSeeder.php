<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WineProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = ProductCategory::query()->firstOrCreate(
            ['name' => 'VINI & LIQUORI'],
            [
                'slug' => 'vini-liquori',
                'description' => 'Vini, spumanti e liquori.',
                'catalog_color' => '#eee6f7',
                'is_public' => true,
                'sort_order' => ((int) ProductCategory::query()->max('sort_order')) + 1,
                'active' => true,
            ],
        );
        $taxRate = TaxRate::query()->firstOrCreate(
            ['percentage' => 22],
            ['name' => 'IVA 22%', 'active' => true],
        );
        $unit = UnitOfMeasure::query()->firstOrCreate(
            ['symbol' => 'pz'],
            ['name' => 'Pezzi', 'active' => true],
        );
        $sortOrder = ((int) Product::query()
            ->whereBelongsTo($category, 'productCategory')
            ->max('sort_order')) + 1;

        foreach ($this->wines() as $index => $wine) {
            $code = 'IPF-WINE-'.strtoupper(substr(sha1(($index + 1).'|'.$wine['name']), 0, 8));

            Product::query()->firstOrCreate(
                ['code' => $code],
                [
                    'product_category_id' => $category->id,
                    'tax_rate_id' => $taxRate->id,
                    'default_unit_of_measure_id' => $unit->id,
                    'name' => $wine['name'],
                    'slug' => Str::slug($wine['name'].'-'.$code),
                    'description' => $wine['description'],
                    'public_description' => $wine['description'],
                    'purchase_cost_per_unit' => 1.16,
                    'base_price_per_unit' => 2.05,
                    'restaurant_price_per_unit' => 2.05,
                    'base_minimum_quantity' => 1,
                    'restaurant_minimum_quantity' => 5,
                    'is_public' => true,
                    'is_seasonal' => false,
                    'sort_order' => $sortOrder + $index,
                    'active' => true,
                ],
            );
        }
    }

    private function wines(): array
    {
        return [
            $this->wine('Nero d’Avola DOP', <<<'TEXT'
UVE: Nero d’Avola in purezza
VINIFICAZIONE: a temperatura controllata in acciaio inox
MATURAZIONE: in acciaio in grotti tufacee
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 18° - 19° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Montepulciano d’Abruzzo DOP', <<<'TEXT'
UVE: Montepulciano d’Abrutto in purezza
VINIFICAZIONE: a temperatura controllata in acciaio inox
MATURAZIONE: in acciaio in grotti tufacee
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 18°
FORMATO: 75 cl.
TEXT),
            $this->wine('Merlot IGP', <<<'TEXT'
UVE: 100% Merlot
VINIFICAZIONE: a temperatura controllata in acciaio inox
MATURAZIONE: in acciaio in grotti tufacee
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 19°
FORMATO: 75 cl.
TEXT),
            $this->wine('Chardonnay IGP', <<<'TEXT'
UVE: Chardonnay Terre Siciliane
VINIFICAZIONE: a temperatura controllata in acciaio inox
MATURAZIONE: in acciaio in grotti tufacee, seguito da un breve periodo in bottiglia
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 12°
FORMATO: 75 cl.
TEXT),
            $this->wine('Falanghina IGP', <<<'TEXT'
UVE: 100% Falanghina
VINIFICAZIONE: a temperatura controllata in acciaio inox
MATURAZIONE: in acciaio in grotti tufacee
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 10° - 12° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Nero d’Avola DOP', <<<'TEXT'
UVE: 100% Nero d’Avola
VINIFICAZIONE: a temperatura controllata 14° - 16° C
MATURAZIONE: almeno 8 mesi in legno minimo un mese in bottiglia
GRADAZIONE ALCOLICA: 14,5%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Privimitivo IGP', <<<'TEXT'
UVE: 100% Primitivo Salento IGP
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 6 mesi in legno e un mese in bottiglia
GRADAZIONE ALCOLICA: 14,5%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Montepulciano d’Abruzzo DOP', <<<'TEXT'
UVE: 100% Montepulciano d’Abrutto
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 6 mesi in legno e un mese di bottiglia
GRADAZIONE ALCOLICA: 14,5%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Syrah IGP', <<<'TEXT'
UVE: 100% Syrah terre siciliane IGP
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 3 mesi in legno e un mese di bottiglia
GRADAZIONE ALCOLICA: 14,5%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Aglianico IGP', <<<'TEXT'
UVE: 100% Aglianico campania
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 3 mesi in legno e un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Passerina IGP', <<<'TEXT'
UVE: 100% Passerina
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: 3 mesi in acciaio e un mese in bottiglia
GRADAZIONE ALCOLICA: 12%
TEMPERATURA DI SERVIZIO: 10° - 12° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Falanghina IGP', <<<'TEXT'
UVE: 100% Falanghina
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: 3 mesi in acciaio e un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Fiano IGP', <<<'TEXT'
UVE: 100% Fiano
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: 3 mesi in acciaio e un mese di bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Greco IGP', <<<'TEXT'
UVE: 100% Greco
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: 3 mesi in acciaio e un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Allerì IGP', <<<'TEXT'
UVE: 100% Alleri
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: rifermentazione in autoclave con metodo charmat per una durata non inferiore ai 50 giorni
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Montepulciano d’Abruzzo DOP', <<<'TEXT'
UVE: 100% Montepulciano d’Abruzzo
VINIFICAZIONE: a temperatura controllata
MATURAZIONE: in legno, per almeno 3 mesi in grotte tufacee
GRADAZIONE ALCOLICA: 14,5%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Piedirosso IGP', <<<'TEXT'
UVE: 100% Piedirosso
VINIFICAZIONE: in rosso con termocondizionamento del processo fermentativo, inizio della vendemmia nella 4a decade di settembre
MATURAZIONE: in botte grande per almeno 6 mesi
GRADAZIONE ALCOLICA: 13,5%
TEMPERATURA DI SERVIZIO: 12° - 14° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Aglianico IGP', <<<'TEXT'
UVE: 100% Aglianico
VINIFICAZIONE: a temperatura controllata tra i 14° - 16° C
MATURAZIONE: almeno 8 mesi in legno, minimo un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Fiano di Avellino DOCG', <<<'TEXT'
UVE: 100% Fiano di Avellino
VINIFICAZIONE: fermentazione a temperatura controllata tra 10° - 14° C
MATURAZIONE: minimo 5 mesi in acciaio, un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Greco di Tufo DOCG', <<<'TEXT'
UVE: 100% Greco di Tufo
VINIFICAZIONE: fermentazione a temperatura controllata tra 10° - 14° C
MATURAZIONE: minimo 5 mesi in acciaio e un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Coda di Volpe IGP', <<<'TEXT'
UVE: 100% Coda di volpe in purezza
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: 3 mesi in acciaio, un mese in bottiglia
GRADAZIONE ALCOLICA: 13,5%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Pecorino IGP', <<<'TEXT'
UVE: 100% Pecorino autoctono in purezza
VINIFICAZIONE: in acciaio con controllo della temperatura di fermentazione
MATURAZIONE: in acciaio almeno 3 mesi e un mese in bottiglia
GRADAZIONE ALCOLICA: 13,5%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Falanghina IGP', <<<'TEXT'
UVE: 100% Falanghina
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 3 mesi in acciaio e un mese in bottiglia
GRADAZIONE ALCOLICA: 13%
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Primitivo di Manduria DOP', <<<'TEXT'
UVE: 100% Primitivo
VINIFICAZIONE: in rosso con termocondizionamento del processo fermentativo, inizio vendemmia nella 2a decade di settembre
MATURAZIONE: in botte grande per almeno 6 mesi
GRADAZIONE ALCOLICA: 14,5% + 5% vol.
TEMPERATURA DI SERVIZIO: 12° - 14° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Spumante Extra Dry DOP', <<<'TEXT'
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: spumantizzato con metodo martinotti
GRADAZIONE ALCOLICA: 11,5%
COLORE: giallo paglierino
PROFUMO: bouquet armonico, offre sentori di fiori di campo
TEMPERATURA DI SERVIZIO: 8° - 10° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Pimpinella DOC – prima bottiglia', <<<'TEXT'
UVE: 100% Pimpinella
VINIFICAZIONE: a temperatura controllata
MATURAZIONE: rifermentazione in autoclave con metodo charmat o martinotti per una durata non inferiore a 30 giorni
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 8° - 10°
FORMATO: 75 cl.
TEXT),
            $this->wine('Pimpinella DOC – seconda bottiglia', <<<'TEXT'
UVE: 100% Pimpinella
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: rifermentazione con metodo charmat o martinotti per una durata non inferiore i 20 giorni
GRADAZIONE ALCOLICA: 12,5%
TEMPERATURA DI SERVIZIO: 6° - 8° C
FORMATO: 75 cl.
TEXT),
            $this->wine('Magnum Primitivo Salento IGP', <<<'TEXT'
UVE: 100% Primitivo
VINIFICAZIONE: a temperatura controllata tra 10° - 14° C
MATURAZIONE: almeno 6 mesi in legno e un mese in bottiglia
GRADAZIONE ALCOLICA: 14%
TEMPERATURA DI SERVIZIO: 16° - 18° C
FORMATO: 1,5 litri
CONFEZIONE: La bottiglia di vino è venduta in una cassetta di legno di abete.
TEXT),
        ];
    }

    private function wine(string $name, string $description): array
    {
        return [
            'name' => $name,
            'description' => trim($description),
        ];
    }
}
