<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\DeliveryDocument;
use App\Models\DocumentSequence;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateDeliveryDocumentService
{
    public function create(Order $order, User $creator, array $data): DeliveryDocument
    {
        if (blank($order->paid_at)) {
            throw ValidationException::withMessages([
                'paid_at' => 'L’ordine deve risultare pagato prima di generare il DDT.',
            ]);
        }

        if ($order->deliveryDocument()->exists()) {
            throw ValidationException::withMessages([
                'document_number' => 'Per questo ordine è già stato generato un DDT.',
            ]);
        }

        $company = Company::query()
            ->whereKey($data['company_id'])
            ->where('active', true)
            ->first();

        if (! $company) {
            throw ValidationException::withMessages([
                'company_id' => 'Seleziona un’azienda emittente attiva prima di generare il DDT.',
            ]);
        }

        $order->loadMissing(['customer', 'items.product']);
        $issuedAt = Carbon::parse($data['issued_at']);
        $year = (int) $issuedAt->format('Y');

        DocumentSequence::query()->firstOrCreate(
            ['document_type' => 'ddt', 'year' => $year],
            ['last_number' => 0],
        );

        return DB::transaction(function () use ($company, $creator, $data, $issuedAt, $order, $year): DeliveryDocument {
            $sequence = DocumentSequence::query()
                ->where('document_type', 'ddt')
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
            $sequence->increment('last_number');
            $progressive = $sequence->fresh()->last_number;

            return DeliveryDocument::create([
                'order_id' => $order->id,
                'created_by' => $creator->id,
                'document_number' => sprintf('DDT-%d-%06d', $year, $progressive),
                'issued_at' => $issuedAt,
                'transport_reason' => $data['transport_reason'],
                'transport_method' => $data['transport_method'],
                'goods_appearance' => $data['goods_appearance'] ?? null,
                'packages_count' => $data['packages_count'] ?? null,
                'total_weight' => $data['total_weight'] ?? null,
                'transport_started_at' => $data['transport_started_at'] ?? null,
                'carrier_name' => $data['carrier_name'] ?? null,
                'carrier_vat_number' => $data['carrier_vat_number'] ?? null,
                'carrier_tax_code' => $data['carrier_tax_code'] ?? null,
                'vehicle_registration' => $data['vehicle_registration'] ?? null,
                'notes' => $data['notes'] ?? null,
                'sender_snapshot' => $this->senderSnapshot($company),
                'recipient_snapshot' => $this->recipientSnapshot($order),
                'destination_snapshot' => $this->destinationSnapshot($order),
                'items_snapshot' => $this->itemsSnapshot($order),
            ]);
        });
    }

    private function senderSnapshot(Company $company): array
    {
        return $company->only([
            'business_name', 'vat_number', 'tax_code', 'email', 'phone', 'address',
            'city', 'postal_code', 'province', 'iban', 'logo_path',
        ]);
    }

    private function recipientSnapshot(Order $order): array
    {
        $customer = $order->customer;

        return [
            'display_name' => $customer->display_name,
            ...$customer->only([
                'company_name', 'first_name', 'last_name', 'vat_number', 'tax_code',
                'email', 'phone', 'billing_address', 'city', 'postal_code', 'province',
            ]),
        ];
    }

    private function destinationSnapshot(Order $order): array
    {
        $customer = $order->customer;

        return [
            'address' => $order->delivery_address ?: $customer->delivery_address,
            'city' => $order->delivery_city ?: $customer->city,
            'postal_code' => $order->delivery_postal_code ?: $customer->postal_code,
            'province' => $order->delivery_province ?: $customer->province,
            'notes' => $order->delivery_notes,
        ];
    }

    private function itemsSnapshot(Order $order): array
    {
        return $order->items->map(fn ($item): array => [
            'code' => $item->product?->code,
            'name' => $item->product_name,
            'quantity' => (string) $item->quantity,
            'unit_name' => $item->unit_of_measure_name,
            'unit_symbol' => $item->unit_of_measure_symbol,
        ])->values()->all();
    }
}
