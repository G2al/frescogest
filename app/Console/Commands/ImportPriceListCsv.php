<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportPriceListCsv extends Command
{
    protected $signature = 'products:import-price-list
        {path : Percorso del file CSV (name,unit_symbol,costo_netto,prezzo_privati,prezzo_ristoratori,prezzo_partner)}
        {--apply : Applica davvero le modifiche. Senza questo flag viene solo mostrato il report (dry-run).}';

    protected $description = 'Importa i prezzi dal listino PDF trascritto in CSV, aggiornando i prodotti esistenti per nome';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("File non trovato: {$path}");

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));

        $updated = [];
        $skippedNoChange = [];
        $notFoundInDb = [];
        $ambiguous = [];
        $flagged = [];
        $touchedProductIds = [];

        $allProducts = Product::query()->get(['id', 'name']);

        $run = function () use ($rows, $header, $allProducts, &$updated, &$skippedNoChange, &$notFoundInDb, &$ambiguous, &$flagged, &$touchedProductIds, $apply): void {
            foreach ($rows as $row) {
                if (count($row) < 2 || trim($row[0]) === '') {
                    continue;
                }

                $data = array_combine($header, array_pad($row, count($header), null));
                $name = trim($data['name']);

                $product = Product::query()
                    ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($name)])
                    ->first();

                if (! $product && isset($this->manualAliases()[$name])) {
                    $product = Product::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [Str::lower($this->manualAliases()[$name])])
                        ->first();
                }

                if (! $product) {
                    $normalizedTarget = $this->normalize($name);
                    $candidates = $allProducts->filter(
                        fn (Product $p): bool => $this->normalize($p->name) === $normalizedTarget
                    );

                    if ($candidates->count() === 1) {
                        $product = Product::find($candidates->first()->id);
                    } elseif ($candidates->count() > 1) {
                        $ambiguous[] = $name.' -> possibili: '.$candidates->pluck('name')->implode(' | ');

                        continue;
                    }
                }

                if (! $product) {
                    $notFoundInDb[] = $name;

                    continue;
                }

                if (in_array($product->id, $touchedProductIds, true)) {
                    $ambiguous[] = "{$name} -> conflitto: '{$product->name}' gia' abbinato da un'altra riga del PDF in questa stessa importazione";

                    continue;
                }

                $touchedProductIds[] = $product->id;

                $costoNetto = $this->parsePrice($data['costo_netto'] ?? null);
                $privati = $this->parsePrice($data['prezzo_privati'] ?? null);
                $ristoratori = $this->parsePrice($data['prezzo_ristoratori'] ?? null);
                $partner = $this->parsePrice($data['prezzo_partner'] ?? null);

                if ($costoNetto === null && $privati === null && $ristoratori === null && $partner === null) {
                    $skippedNoChange[] = $name.' (nessun prezzo nel PDF)';

                    continue;
                }

                $before = [
                    'costo_netto' => (float) $product->purchase_cost_per_unit,
                    'privati' => (float) $product->base_price_per_unit,
                    'ristoratori' => (float) $product->restaurant_price_per_unit,
                    'partner' => (float) $product->partner_price_per_unit,
                ];

                if ($partner !== null && $privati !== null && $partner > $privati) {
                    $flagged[] = "{$name}: prezzo partner ({$partner}) superiore al prezzo privati ({$privati}) - verificare a mano";
                }

                if ($privati !== null && $ristoratori !== null && $ristoratori > $privati) {
                    $flagged[] = "{$name}: prezzo ristoratori ({$ristoratori}) superiore al prezzo privati ({$privati}) - verificare a mano";
                }

                if ($costoNetto !== null) {
                    $product->purchase_cost_per_unit = $costoNetto;
                }

                if ($privati !== null) {
                    $product->base_price_per_unit = $privati;
                }

                if ($ristoratori !== null) {
                    $product->restaurant_price_per_unit = $ristoratori;
                }

                if ($partner !== null) {
                    $product->partner_price_per_unit = $partner;
                }

                if (! $product->isDirty()) {
                    $skippedNoChange[] = "{$name} (prezzi gia' allineati)";

                    continue;
                }

                if ($apply) {
                    $product->save();
                }

                $after = [
                    'costo_netto' => (float) $product->purchase_cost_per_unit,
                    'privati' => (float) $product->base_price_per_unit,
                    'ristoratori' => (float) $product->restaurant_price_per_unit,
                    'partner' => (float) $product->partner_price_per_unit,
                ];

                $updated[] = sprintf(
                    '%s | costo %.2f -> %.2f | privati %.2f -> %.2f | ristoratori %.2f -> %.2f | partner %.2f -> %.2f',
                    $name,
                    $before['costo_netto'], $after['costo_netto'],
                    $before['privati'], $after['privati'],
                    $before['ristoratori'], $after['ristoratori'],
                    $before['partner'], $after['partner'],
                );
            }
        };

        if ($apply) {
            DB::transaction($run);
        } else {
            $run();
        }

        $notTouched = Product::query()
            ->whereNotIn('id', $touchedProductIds)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $this->line('');
        $this->info($apply ? '=== MODIFICHE APPLICATE ===' : '=== DRY-RUN (nessuna modifica salvata, usa --apply per confermare) ===');

        $this->line('');
        $this->comment("Prodotti aggiornati ({$this->count($updated)}):");
        foreach ($updated as $line) {
            $this->line("  - {$line}");
        }

        $this->line('');
        $this->comment('Valori sospetti da verificare a mano ('.count($flagged).'):');
        foreach ($flagged as $line) {
            $this->warn("  - {$line}");
        }

        $this->line('');
        $this->comment('Righe ambigue, piu\' candidati nel DB ('.count($ambiguous).') - da risolvere a mano:');
        foreach ($ambiguous as $line) {
            $this->warn("  - {$line}");
        }

        $this->line('');
        $this->comment('Righe del PDF senza prodotto corrispondente nel DB ('.count($notFoundInDb).') - da creare manualmente:');
        foreach ($notFoundInDb as $line) {
            $this->line("  - {$line}");
        }

        $this->line('');
        $this->comment('Prodotti gia\' aggiornati/senza prezzo nel PDF, nessuna modifica ('.count($skippedNoChange).'):');
        foreach ($skippedNoChange as $line) {
            $this->line("  - {$line}");
        }

        $this->line('');
        $this->comment('Prodotti nel DB MAI toccati dal PDF ('.count($notTouched).'):');
        foreach ($notTouched as $line) {
            $this->line("  - {$line}");
        }

        return self::SUCCESS;
    }

    private function count(array $items): int
    {
        return count($items);
    }

    /**
     * Corrispondenze manuali per prodotti scritti in modo diverso tra PDF e DB
     * ma che indicano lo stesso articolo.
     *
     * @return array<string, string>
     */
    private function manualAliases(): array
    {
        return [
            'Fiordilatte Agerola - (500 G)' => 'Fior di latte Agerola',
            'Fiordilatte Julienne' => 'Fior di latte Julienne',
            'Fiordilatte Intero' => 'Fior di latte intero',
            'Provola Fiordilatte' => 'Provola fior di latte',
            'Provola Affumicata - (500 G)' => 'Provola affumicata da 500 g',
            'Noccioline Tostate' => 'Noccioline',
            'Insalata Icerbeg' => 'Insalata Iceberg',
            'Misticanza Orientale - (1 kg)' => 'Misticanza',
            'Box Basilico (500G)' => 'Basilico 500 g',
            'Basilico (30G)' => 'Basilico 30 g',
            'Box Rosmarino (500G)' => 'Rosmarino 500 g',
            'Rosmarino (30G)' => 'Rosmarino 30 g',
            'Box Menta (500G)' => 'Menta 500 g',
            'Menta (30G)' => 'Menta 30 g',
            'Box Salvia (500G)' => 'Salvia',
            'Taralli Pugliesi Il Covo' => 'Taralli Covo',
            'Rotoloni asciugatutto con da (2 pz)' => 'Rotoloni asciugatutto',
            'Saltimbocca Pane Pizza' => 'Saltimbocca',
            'Sacco Patate Gialle 5 kg' => 'Patate Gialle 5 kg',
            'Patata dolce o americana' => 'Patate Dolci',
            'Cime di Rapa pugliesi' => 'Cime di Rapa',
            'Pomodoro Ramato' => 'Pomodori Ramato',
            'Salame a ciampa piccante' => 'Ciampa piccante',
            'Salame a Ciampa dolce' => 'Ciampa dolce',
            'Olive nere di gaeta' => 'Olive nere',
            'Olive verdi napoletane' => 'Olive verdi',
            'Uova Fresche XXL' => 'Uova',
            'Piccola pasticceria mista - (500 g)' => 'Piccola pasticceria',
            'Insalata Novella (100G)' => 'Novella',
        ];
    }

    private function normalize(string $name): string
    {
        $name = Str::lower($name);
        $name = preg_replace('/\s*[-–]\s*\(.*?\)\s*$/u', '', $name) ?? $name;
        $name = preg_replace('/\s*\(.*?\)\s*$/u', '', $name) ?? $name;
        $name = preg_replace('/\s*[-–]\s*\d[\d.,]*\s*(kg|g|pz|ml|l|cad)\s*$/iu', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function parsePrice(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}
