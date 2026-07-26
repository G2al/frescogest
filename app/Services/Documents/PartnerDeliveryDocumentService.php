<?php

namespace App\Services\Documents;

use App\Models\Company;
use App\Models\DeliveryDocument;
use App\Models\Partner;
use App\Models\PartnerGoodsEntry;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerDeliveryDocumentService
{
    public function __construct(
        private readonly DeliveryDocumentNumberService $numbers,
        private readonly PriceCalculator $calculator,
    ) {}

    public function create(Partner $partner, User $creator, array $data): DeliveryDocument
    {
        return DB::transaction(function () use ($creator, $data, $partner): DeliveryDocument {
            $issuedAt = Carbon::parse($data['issued_at']);
            $items = $this->items($data['items'] ?? []);
            $document = DeliveryDocument::query()->create(
                $this->documentData($partner, $creator, $data, $issuedAt, $items) + [
                    'document_number' => $this->numbers->next($issuedAt),
                    'revision' => 1,
                ],
            );

            $this->replaceGoodsEntries($document, $items, $data);

            return $document->refresh();
        });
    }

    public function update(DeliveryDocument $document, User $editor, array $data): DeliveryDocument
    {
        $this->ensurePartnerDocument($document);

        return DB::transaction(function () use ($data, $document, $editor): DeliveryDocument {
            $document = DeliveryDocument::query()->lockForUpdate()->findOrFail($document->getKey());
            $partner = $document->partner()->firstOrFail();
            $issuedAt = Carbon::parse($data['issued_at']);
            $items = $this->items($data['items'] ?? []);
            $documentData = $this->documentData($partner, $editor, $data, $issuedAt, $items);
            unset($documentData['created_by']);

            $document->update(
                $documentData + [
                    'revision' => $document->revision + 1,
                    'regenerated_at' => now(),
                ],
            );

            $this->replaceGoodsEntries($document, $items, $data);

            return $document->refresh();
        });
    }

    public function delete(DeliveryDocument $document): void
    {
        $this->ensurePartnerDocument($document);

        DB::transaction(function () use ($document): void {
            PartnerGoodsEntry::query()
                ->where('delivery_document_id', $document->getKey())
                ->delete();
            $document->delete();
        });
    }

    private function documentData(
        Partner $partner,
        User $user,
        array $data,
        Carbon $issuedAt,
        array $items,
    ): array {
        $company = Company::query()
            ->where('vat_number', '02396610186')
            ->where('active', true)
            ->firstOrFail();
        $totalNet = $this->calculator->sum(array_column($items, 'line_net'));
        $totalTax = $this->calculator->sum(array_column($items, 'line_tax'));

        return [
            'partner_id' => $partner->getKey(),
            'created_by' => $user->getKey(),
            'issued_at' => $issuedAt,
            'transport_reason' => 'Vendita',
            'transport_method' => 'Mittente',
            'notes' => $data['notes'] ?? null,
            'sender_snapshot' => $company->only([
                'business_name',
                'vat_number',
                'address',
                'city',
                'province',
                'logo_path',
            ]),
            'recipient_snapshot' => ['display_name' => $partner->name],
            'destination_snapshot' => [],
            'items_snapshot' => $items,
            'subtotal_net' => $totalNet,
            'discount_percentage' => 0,
            'discount_amount_net' => 0,
            'shipping_amount_net' => 0,
            'payment_method_snapshot' => $data['payment_method_snapshot'] ?? null,
            'total_net' => $totalNet,
            'total_tax' => $totalTax,
            'total_gross' => $this->calculator->sum([$totalNet, $totalTax]),
        ];
    }

    private function replaceGoodsEntries(DeliveryDocument $document, array $items, array $data): void
    {
        PartnerGoodsEntry::query()
            ->where('delivery_document_id', $document->getKey())
            ->delete();

        foreach ($items as $item) {
            PartnerGoodsEntry::query()->create([
                'partner_id' => $document->partner_id,
                'delivery_document_id' => $document->getKey(),
                'product_id' => $item['product_id'],
                'delivered_on' => $document->issued_at->toDateString(),
                'quantity' => $item['quantity'],
                'unit_purchase_price_net' => $item['unit_price_net'],
                'tax_percentage' => $item['tax_percentage'],
                'notes' => $data['notes'] ?? null,
            ]);
        }
    }

    private function items(array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Inserisci almeno un prodotto nella bolla.',
            ]);
        }

        return collect($items)
            ->values()
            ->map(function (array $item): array {
                $product = Product::query()
                    ->with(['taxRate', 'defaultUnitOfMeasure'])
                    ->where('active', true)
                    ->findOrFail($item['product_id']);
                $quantity = (float) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price_net'] ?? 0);

                if ($quantity <= 0 || $unitPrice < 0) {
                    throw ValidationException::withMessages([
                        'items' => "Controlla quantità e prezzo di {$product->name}.",
                    ]);
                }

                $lineNet = $this->calculator->lineTotal($unitPrice, $quantity);
                $lineTax = $this->calculator->tax($lineNet, $product->taxRate->percentage);

                return [
                    'product_id' => $product->getKey(),
                    'name' => $product->name,
                    'quantity' => number_format($quantity, 3, '.', ''),
                    'unit_symbol' => $product->defaultUnitOfMeasure->symbol,
                    'unit_price_net' => number_format($unitPrice, 4, '.', ''),
                    'tax_percentage' => number_format((float) $product->taxRate->percentage, 2, '.', ''),
                    'original_line_net' => $lineNet,
                    'discount_percentage' => '0.00',
                    'discount_amount_net' => '0.00',
                    'line_net' => $lineNet,
                    'line_tax' => $lineTax,
                    'line_gross' => $this->calculator->sum([$lineNet, $lineTax]),
                ];
            })
            ->all();
    }

    private function ensurePartnerDocument(DeliveryDocument $document): void
    {
        if (! $document->partner_id || $document->order_id) {
            throw ValidationException::withMessages([
                'document' => 'Questa operazione è disponibile soltanto per le bolle partner.',
            ]);
        }
    }
}
