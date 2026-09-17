<?php

namespace App\Services\Documents;

use App\Models\DeliveryDocument;
use App\Models\Order;
use App\Services\Orders\ApplyOrderDiscountService;
use App\Services\Orders\OrderItemSnapshotService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderDeliveryDocumentService
{
    public function __construct(
        private readonly OrderItemSnapshotService $snapshots,
        private readonly ApplyOrderDiscountService $discounts,
    ) {}

    public function update(DeliveryDocument $document, array $data): DeliveryDocument
    {
        $this->ensureOrderDocument($document);

        return DB::transaction(function () use ($data, $document): DeliveryDocument {
            $document = DeliveryDocument::query()->lockForUpdate()->findOrFail($document->getKey());
            $order = Order::query()->lockForUpdate()->findOrFail($document->order_id);

            $order->update([
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'internal_notes' => $data['notes'] ?? null,
            ]);

            $order->items()->delete();

            foreach (array_values($data['items'] ?? []) as $index => $item) {
                $order->items()->create($this->snapshots->enrichManual([
                    ...$item,
                    'sort_order' => $index,
                ], $order));
            }

            $order->refresh();

            // apply() recalculates every line, updates the order totals, and (since a
            // delivery document already exists) refreshes its items_snapshot/totals too.
            $this->discounts->apply($order, $data['discount_percentage'] ?? 0);

            $order->refresh()->load('paymentMethod');

            $document->update([
                'issued_at' => Carbon::parse($data['issued_at']),
                'payment_method_snapshot' => $order->paymentMethod?->name,
                'revision' => $document->revision + 1,
                'regenerated_at' => now(),
            ]);

            return $document->refresh();
        });
    }

    private function ensureOrderDocument(DeliveryDocument $document): void
    {
        if (! $document->order_id) {
            throw ValidationException::withMessages([
                'document' => 'Questa operazione è disponibile soltanto per le bolle di un ordine cliente.',
            ]);
        }
    }
}
