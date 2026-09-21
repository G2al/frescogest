<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Riallinea il COSTO di acquisto (e quindi il guadagno) delle righe ordine al costo
 * attuale del prodotto.
 *
 * Il costo viene "fotografato" sulla riga quando questa viene creata: se la bolla
 * viene fatta prima di aggiornare i prezzi di acquisto, il guadagno risulta calcolato
 * sul costo del giorno prima. Questo servizio corregge quella fotografia.
 *
 * Tocca soltanto costo e margine: prezzi di vendita, IVA, sconti, contenuto e revisione
 * della bolla non vengono mai modificati. L'Analisi economica legge questi stessi campi,
 * quindi si riallinea da sola.
 */
class OrderCostRefreshService
{
    public function __construct(private readonly OrderItemSnapshotService $snapshots) {}

    /**
     * Ricalcola il costo del prodotto sulle righe delle bolle emesse nel giorno indicato
     * (oggi, se non specificato). Le bolle dei giorni precedenti non vengono toccate,
     * così lo storico resta quello reale.
     *
     * @return int numero di righe aggiornate
     */
    public function refreshProduct(Product $product, ?Carbon $day = null): int
    {
        $day ??= now();

        $items = OrderItem::query()
            ->where('product_id', $product->getKey())
            ->whereHas('order', fn (Builder $orders): Builder => $orders
                ->whereIn('status', [OrderStatus::Confirmed, OrderStatus::Paid])
                ->whereHas('deliveryDocument', fn (Builder $documents): Builder => $documents
                    ->whereDate('issued_at', $day->toDateString())))
            ->get();

        $items->each(fn (OrderItem $item) => $item->setRelation('product', $product));

        return $this->refresh($items);
    }

    /**
     * Ricalcola il costo di tutte le righe di un ordine con i costi attuali dei prodotti.
     *
     * @return int numero di righe aggiornate
     */
    public function refreshOrder(Order $order): int
    {
        return $this->refresh($order->items()->with('product')->get());
    }

    private function refresh(Collection $items): int
    {
        $items = $items->filter(fn (OrderItem $item): bool => $item->product !== null);

        if ($items->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                $item->update($this->snapshots->costSnapshot($item->product, $item->quantity, $item->line_net));
            }

            Order::query()
                ->whereKey($items->pluck('order_id')->unique()->all())
                ->get()
                ->each(fn (Order $order) => $this->snapshots->recalculateCosts($order));
        });

        return $items->count();
    }
}
