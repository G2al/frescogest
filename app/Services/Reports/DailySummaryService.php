<?php

namespace App\Services\Reports;

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
                    (float) $item->line_net,
                    (float) $item->line_tax,
                    (float) $item->line_gross,
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
                        (float) $entry->total_net,
                        (float) $entry->total_tax,
                        (float) $entry->total_gross,
                        'Partner',
                    );
                });
        }

        return $rows
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
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
