<?php

namespace App\Services\Documents;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\DeliveryDocument;
use App\Models\DocumentSequence;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\RecordOrderPaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDeliveryDocumentService
{
    public function __construct(
        private readonly DeliveryDocumentSnapshotService $snapshots,
        private readonly RecordOrderPaymentService $payments,
    ) {}

    public function create(Order $order, User $creator, array $data): DeliveryDocument
    {
        if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Paid], true)) {
            throw ValidationException::withMessages(['status' => 'La bolla è disponibile da quando l’ordine è confermato.']);
        }

        if ($order->deliveryDocument()->exists()) {
            throw ValidationException::withMessages(['document_number' => 'Per questo ordine esiste già una bolla di consegna.']);
        }

        $company = Company::query()->where('vat_number', '02396610186')->where('active', true)->firstOrFail();
        $order->loadMissing(['customer', 'items.product', 'items.product.taxRate']);
        $issuedAt = Carbon::parse($data['issued_at']);
        $year = (int) $issuedAt->format('Y');

        return DB::transaction(function () use ($company, $creator, $data, $issuedAt, $order, $year): DeliveryDocument {
            $sequence = DocumentSequence::query()->firstOrCreate(
                ['document_type' => 'delivery_note', 'year' => $year],
                ['last_number' => 0],
            );
            $sequence = DocumentSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            $sequence->increment('last_number');

            if (($data['mark_as_paid'] ?? false) === true) {
                $this->payments->record($order, $data);
            }

            $order->load('paymentMethod');

            return DeliveryDocument::query()->create([
                'order_id' => $order->id,
                'created_by' => $creator->id,
                'document_number' => sprintf('BC-%d-%06d', $year, $sequence->fresh()->last_number),
                'issued_at' => $issuedAt,
                'transport_reason' => 'Vendita',
                'transport_method' => 'Mittente',
                'sender_snapshot' => $company->only(['business_name', 'vat_number', 'address', 'city', 'province', 'logo_path']),
                'recipient_snapshot' => ['display_name' => $order->customer->display_name],
                'destination_snapshot' => [],
                'items_snapshot' => $this->snapshots->items($order),
                'subtotal_net' => $order->subtotal_net ?? $order->total_net,
                'discount_percentage' => $order->discount_percentage ?? 0,
                'discount_amount_net' => $order->discount_amount_net ?? 0,
                'shipping_amount_net' => $order->shipping_amount_net ?? 0,
                'payment_method_snapshot' => $order->paymentMethod?->name,
                'total_net' => $order->total_net,
                'total_tax' => $order->total_tax,
                'total_gross' => $order->total_gross,
            ]);
        });
    }
}
