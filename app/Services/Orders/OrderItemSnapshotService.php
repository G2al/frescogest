<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Services\Documents\DeliveryDocumentSnapshotService;
use App\Services\Pricing\PriceCalculator;
use App\Services\Pricing\ProductPricingService;
use Illuminate\Validation\ValidationException;

class OrderItemSnapshotService
{
    public function __construct(
        private readonly ProductPricingService $pricing,
        private readonly PriceCalculator $calculator,
        private readonly DeliveryDocumentSnapshotService $documentSnapshots,
    ) {}

    public function enrich(array $data, Order $order): array
    {
        $product = Product::query()->with(['taxRate', 'defaultUnitOfMeasure'])->findOrFail($data['product_id']);
        $pricing = $this->pricing->details($product, $order->customer);

        if ((float) $data['quantity'] < (float) $pricing['minimum_quantity']) {
            throw ValidationException::withMessages([
                'quantity' => "La quantità minima per {$product->name} è {$this->quantity($pricing['minimum_quantity'])} {$product->defaultUnitOfMeasure->symbol}.",
            ]);
        }

        return $this->snapshot($data, $order, $product, $pricing['price']);
    }

    public function enrichManual(array $data, Order $order): array
    {
        $product = Product::query()
            ->with(['taxRate', 'defaultUnitOfMeasure'])
            ->findOrFail($data['product_id']);
        $unitPrice = $data['unit_price_net'] ?? $this->pricing->details($product, $order->customer)['price'];
        $customCost = $this->customCost($data);

        if ((float) $data['quantity'] <= 0 || (float) $unitPrice < 0) {
            throw ValidationException::withMessages([
                'items' => "Controlla quantità e prezzo di {$product->name}.",
            ]);
        }

        if ($customCost !== null && $customCost < 0) {
            throw ValidationException::withMessages([
                'items' => "Controlla il prezzo di costo di {$product->name}.",
            ]);
        }

        return $this->snapshot($data, $order, $product, $unitPrice, $customCost);
    }

    private function customCost(array $data): ?float
    {
        $value = $data['purchase_cost_per_unit_net'] ?? null;

        return $value === null || $value === '' ? null : (float) $value;
    }

    private function snapshot(
        array $data,
        Order $order,
        Product $product,
        string|int|float $unitPrice,
        ?float $customUnitCostNet = null,
    ): array {
        $originalLineNet = $this->calculator->lineTotal($unitPrice, $data['quantity']);
        $discountPercentage = (float) ($order->discount_percentage ?? 0);
        $lineNet = $this->calculator->discountedPrice($originalLineNet, $discountPercentage);
        $discountAmount = $this->calculator->difference($originalLineNet, $lineNet);
        $taxPercentage = $product->taxRate->percentage;
        $lineTax = $this->calculator->tax($lineNet, $taxPercentage);
        $lineGross = $this->calculator->sum([$lineNet, $lineTax]);

        return [
            ...$data,
            'product_name' => $product->name,
            'price_per_kg' => $unitPrice,
            'unit_price_net' => $unitPrice,
            'tax_percentage' => $taxPercentage,
            'line_total' => $lineGross,
            'original_line_net' => $originalLineNet,
            'discount_percentage' => $discountPercentage,
            'discount_amount_net' => $discountAmount,
            'line_net' => $lineNet,
            'line_tax' => $lineTax,
            'line_gross' => $lineGross,
            ...$this->costSnapshot($product, $data['quantity'], $lineNet, $customUnitCostNet),
            'unit_of_measure_name' => $product->defaultUnitOfMeasure->name,
            'unit_of_measure_symbol' => $product->defaultUnitOfMeasure->symbol,
        ];
    }

    /**
     * Costo di acquisto e margine di una riga. Se $customUnitCostNet è valorizzato (lo
     * ha scritto l'admin a mano in una riga d'ordine), si usa quello invece del costo
     * attuale del prodotto, e la riga viene marcata "personalizzata": da quel momento
     * OrderCostRefreshService non la tocca più quando il costo del prodotto cambia o
     * quando si genera la bolla, altrimenti il valore scritto a mano verrebbe
     * silenziosamente sovrascritto. Senza override è l'unico punto in cui questi valori
     * vengono determinati: lo usano sia la creazione della riga sia il ricalcolo
     * automatico dei costi.
     */
    public function costSnapshot(
        Product $product,
        string|int|float $quantity,
        string|int|float $lineNet,
        ?float $customUnitCostNet = null,
    ): array {
        $isCustom = $customUnitCostNet !== null;
        $unitCostNet = $isCustom ? $customUnitCostNet : (float) $product->purchase_cost_per_unit;
        $unitCostGross = $isCustom
            ? $unitCostNet * (1 + ((float) $product->taxRate->percentage / 100))
            : (float) $product->purchase_cost_per_unit_gross;

        $purchaseCost = $this->calculator->lineTotal($unitCostNet, $quantity);
        $purchaseGross = $this->calculator->lineTotal($unitCostGross, $quantity);
        $purchaseTax = $this->calculator->difference($purchaseGross, $purchaseCost);
        $margin = $this->calculator->difference($lineNet, $purchaseCost);

        return [
            'purchase_cost_per_unit_net' => $unitCostNet,
            'purchase_cost_is_custom' => $isCustom,
            'purchase_cost_net' => $purchaseCost,
            'purchase_cost_tax' => $purchaseTax,
            'purchase_cost_gross' => $purchaseGross,
            'margin_amount' => $margin,
            'margin_percentage' => $this->calculator->percentage($margin, $lineNet),
        ];
    }

    public function recalculate(Order $order): void
    {
        $items = $order->items()->get();
        $subtotalNet = $this->calculator->sum($items->pluck('original_line_net')->all());
        $discountAmount = $this->calculator->sum($items->pluck('discount_amount_net')->all());
        $productsNet = $this->calculator->sum($items->pluck('line_net')->all());
        $shippingNet = $order->shipping_amount_net ?? 0;
        $shippingTax = $order->shipping_tax ?? 0;
        $totalNet = $this->calculator->sum([$productsNet, $shippingNet]);
        $productsTax = $this->calculator->sum($items->pluck('line_tax')->all());
        $totalTax = $this->calculator->sum([$productsTax, $shippingTax]);
        $totalGross = $this->calculator->sum([$totalNet, $totalTax]);
        $purchaseCost = $this->calculator->sum($items->pluck('purchase_cost_net')->all());
        $margin = $this->calculator->difference($totalNet, $purchaseCost);

        $data = [
            'total_amount' => $totalGross,
            'subtotal_net' => $subtotalNet,
            'discount_amount_net' => $discountAmount,
            'total_net' => $totalNet,
            'total_tax' => $totalTax,
            'total_gross' => $totalGross,
            'total_purchase_cost_net' => $purchaseCost,
            'gross_margin' => $margin,
            'gross_margin_percentage' => $this->calculator->percentage($margin, $totalNet),
        ];

        if ($order->status === OrderStatus::Paid) {
            $data['payment_amount'] = $totalGross;
        }

        $order->update($data);

        if ($order->deliveryDocument()->exists()) {
            $order->deliveryDocument()->update([
                'items_snapshot' => $this->documentSnapshots->items($order->setRelation('items', $items)),
                'subtotal_net' => $subtotalNet,
                'discount_percentage' => $order->discount_percentage,
                'discount_amount_net' => $discountAmount,
                'shipping_amount_net' => $shippingNet,
                'total_net' => $totalNet,
                'total_tax' => $totalTax,
                'total_gross' => $totalGross,
            ]);
        }
    }

    /**
     * Riallinea soltanto costo e margine dell'ordine alle sue righe. Ricavi, IVA,
     * sconti e bolla restano esattamente come sono: qui cambia solo il guadagno.
     */
    public function recalculateCosts(Order $order): void
    {
        $purchaseCost = $this->calculator->sum($order->items()->pluck('purchase_cost_net')->all());
        $margin = $this->calculator->difference($order->total_net, $purchaseCost);

        $order->update([
            'total_purchase_cost_net' => $purchaseCost,
            'gross_margin' => $margin,
            'gross_margin_percentage' => $this->calculator->percentage($margin, $order->total_net),
        ]);
    }

    private function quantity(string|int|float $quantity): string
    {
        return rtrim(rtrim(number_format((float) $quantity, 3, ',', ''), '0'), ',');
    }
}
