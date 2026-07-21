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

        $unitPrice = $pricing['price'];
        $originalLineNet = $this->calculator->lineTotal($unitPrice, $data['quantity']);
        $discountPercentage = (float) ($order->discount_percentage ?? 0);
        $lineNet = $this->calculator->discountedPrice($originalLineNet, $discountPercentage);
        $discountAmount = $this->calculator->difference($originalLineNet, $lineNet);
        $taxPercentage = $product->taxRate->percentage;
        $lineTax = $this->calculator->tax($lineNet, $taxPercentage);
        $lineGross = $this->calculator->sum([$lineNet, $lineTax]);
        $purchaseCost = $this->calculator->lineTotal($product->purchase_cost_per_unit, $data['quantity']);
        $purchaseTax = $this->calculator->tax($purchaseCost, $taxPercentage);
        $purchaseGross = $this->calculator->sum([$purchaseCost, $purchaseTax]);
        $margin = $this->calculator->difference($lineNet, $purchaseCost);

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
            'purchase_cost_per_unit_net' => $product->purchase_cost_per_unit,
            'purchase_cost_net' => $purchaseCost,
            'purchase_cost_tax' => $purchaseTax,
            'purchase_cost_gross' => $purchaseGross,
            'margin_amount' => $margin,
            'margin_percentage' => $this->calculator->percentage($margin, $lineNet),
            'unit_of_measure_name' => $product->defaultUnitOfMeasure->name,
            'unit_of_measure_symbol' => $product->defaultUnitOfMeasure->symbol,
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

    private function quantity(string|int|float $quantity): string
    {
        return rtrim(rtrim(number_format((float) $quantity, 3, ',', ''), '0'), ',');
    }
}
