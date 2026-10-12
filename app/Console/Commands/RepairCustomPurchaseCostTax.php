<?php

namespace App\Console\Commands;

use App\Models\OrderItem;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ricalcola IVA e totale IVA inclusa del costo di acquisto sulle righe ordine con
 * prezzo di costo personalizzato, create prima della correzione del bug di
 * arrotondamento in OrderItemSnapshotService::costSnapshot() (l'IVA sugli acquisti
 * poteva risultare azzerata quando il costo unitario con IVA aveva un terzo
 * decimale, es. 0,104 €). Tocca solo le righe con purchase_cost_is_custom = true:
 * sono le uniche su cui OrderCostRefreshService non interviene mai.
 */
class RepairCustomPurchaseCostTax extends Command
{
    protected $signature = 'orders:repair-custom-purchase-cost-tax {--apply : Applica davvero le modifiche. Senza questo flag viene solo mostrato il report (dry-run).}';

    protected $description = 'Ricalcola IVA sugli acquisti per le righe ordine con costo personalizzato affette dal bug di arrotondamento';

    public function handle(PriceCalculator $calculator): int
    {
        $apply = (bool) $this->option('apply');

        $items = OrderItem::query()
            ->where('purchase_cost_is_custom', true)
            ->with('order:id,order_number')
            ->get();

        $fixed = [];
        $alreadyCorrect = 0;

        $run = function () use ($items, $calculator, &$fixed, &$alreadyCorrect, $apply): void {
            foreach ($items as $item) {
                $taxPercentage = (float) $item->tax_percentage;
                $correctTax = $calculator->tax($item->purchase_cost_net, $taxPercentage);
                $correctGross = $calculator->sum([$item->purchase_cost_net, $correctTax]);

                if ($item->purchase_cost_tax === $correctTax && $item->purchase_cost_gross === $correctGross) {
                    $alreadyCorrect++;

                    continue;
                }

                $before = ['tax' => $item->purchase_cost_tax, 'gross' => $item->purchase_cost_gross];

                if ($apply) {
                    $item->update([
                        'purchase_cost_tax' => $correctTax,
                        'purchase_cost_gross' => $correctGross,
                    ]);
                }

                $fixed[] = sprintf(
                    'Riga #%d (ordine %s): IVA %s -> %s | totale IVA inclusa %s -> %s',
                    $item->id,
                    $item->order?->order_number ?? '?',
                    $before['tax'],
                    $correctTax,
                    $before['gross'],
                    $correctGross,
                );
            }
        };

        if ($apply) {
            DB::transaction($run);
        } else {
            $run();
        }

        $this->line('');
        $this->info($apply ? '=== CORREZIONI APPLICATE ===' : '=== DRY-RUN (nessuna modifica salvata, usa --apply per confermare) ===');
        $this->line('');
        $this->comment('Righe da correggere trovate ('.count($fixed).'):');

        foreach ($fixed as $line) {
            $this->line("  - {$line}");
        }

        $this->line('');
        $this->comment("Righe già corrette, nessuna modifica ({$alreadyCorrect}).");

        return self::SUCCESS;
    }
}
