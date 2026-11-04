<?php

namespace App\Services\Reports;

use App\Models\DailySummaryOverride;
use App\Models\OrderItem;
use App\Models\PartnerGoodsEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Riepilogo giornaliero: somma in un unico elenco tutti gli articoli di tutte le
 * bolle (clienti, e facoltativamente partner) emesse/consegnate in un giorno,
 * raggruppando per prodotto. Serve per sapere subito quanto preparare in totale,
 * senza dover scorrere bolla per bolla.
 */
class DailySummaryService
{
    /**
     * @return Collection<int, object{product_id: int|null, name: string, symbol: ?string, quantity: float, net: float, tax: float, gross: float, sources: array<int, string>}>
     */
    public function build(CarbonInterface $date, bool $includePartners = true): Collection
    {
        $rows = collect();

        // Il riepilogo serve ad Antonio per sapere quanto SPENDE, non quanto incassa:
        // usa sempre il costo di acquisto della riga (quello "fotografato" sull'ordine
        // o sul carico partner), mai il prezzo di vendita al cliente/partner.
        OrderItem::query()
            ->whereHas('order.deliveryDocument', fn ($query) => $query->whereDate('issued_at', $date->toDateString()))
            ->get()
            ->each(function (OrderItem $item) use (&$rows): void {
                $this->accumulate(
                    $rows,
                    $item->product_id,
                    $item->product_name,
                    $item->unit_of_measure_symbol,
                    (float) $item->quantity,
                    (float) $item->purchase_cost_net,
                    (float) $item->purchase_cost_tax,
                    (float) $item->purchase_cost_gross,
                    'Cliente',
                );
            });

        if ($includePartners) {
            PartnerGoodsEntry::query()
                ->whereDate('delivered_on', $date->toDateString())
                ->with(['product.defaultUnitOfMeasure'])
                ->get()
                ->each(function (PartnerGoodsEntry $entry) use (&$rows): void {
                    $this->accumulate(
                        $rows,
                        $entry->product_id,
                        $entry->product?->name ?? 'Prodotto eliminato',
                        $entry->product?->defaultUnitOfMeasure?->symbol,
                        (float) $entry->quantity,
                        (float) $entry->total_cost_net,
                        (float) $entry->total_cost_tax,
                        (float) $entry->total_cost_gross,
                        'Partner',
                    );
                });
        }

        $this->applyOverrides($rows, $date);

        return $rows
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Sovrascrive (replace, non somma) le righe calcolate con le eventuali
     * correzioni manuali di Antonio per quel giorno, e aggiunge come righe a
     * parte le correzioni per prodotti non presenti in nessuna bolla del giorno.
     * Le bolle/ordini/carichi partner non vengono mai modificati da qui.
     *
     * @param  Collection<int|string, object>  $rows
     */
    private function applyOverrides(Collection $rows, CarbonInterface $date): void
    {
        DailySummaryOverride::query()
            ->whereDate('date', $date->toDateString())
            ->get()
            ->each(function (DailySummaryOverride $override) use ($rows): void {
                $key = $override->product_id ?? 'name:'.$override->name;

                $rows->put($key, (object) [
                    'override_id' => $override->id,
                    'product_id' => $override->product_id,
                    'name' => $override->name,
                    'symbol' => $override->symbol,
                    'quantity' => (float) $override->quantity,
                    'net' => (float) $override->net,
                    'tax' => (float) $override->tax,
                    'gross' => (float) $override->gross,
                    'sources' => ['Modificato manualmente'],
                    'is_manual' => true,
                ]);
            });
    }

    /**
     * @param  Collection<int, object>  $rows
     */
    private function accumulate(
        Collection $rows,
        ?int $productId,
        string $name,
        ?string $symbol,
        float $quantity,
        float $net,
        float $tax,
        float $gross,
        string $source,
    ): void {
        $key = $productId ?? 'name:'.$name;
        $row = $rows->get($key);

        if (! $row) {
            $row = (object) [
                'product_id' => $productId,
                'name' => $name,
                'symbol' => $symbol,
                'quantity' => 0.0,
                'net' => 0.0,
                'tax' => 0.0,
                'gross' => 0.0,
                'sources' => [],
            ];
            $rows->put($key, $row);
        }

        $row->quantity += $quantity;
        $row->net += $net;
        $row->tax += $tax;
        $row->gross += $gross;

        if (! in_array($source, $row->sources, true)) {
            $row->sources[] = $source;
        }
    }
}
