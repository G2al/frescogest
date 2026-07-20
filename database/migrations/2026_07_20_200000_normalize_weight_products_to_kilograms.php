<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $gramUnitId = DB::table('unit_of_measures')->where('symbol', 'g')->value('id');
        $kilogramUnitId = DB::table('unit_of_measures')->where('symbol', 'kg')->value('id');

        if (! $gramUnitId || ! $kilogramUnitId) {
            return;
        }

        $products = DB::table('products')
            ->where('default_unit_of_measure_id', $gramUnitId)
            ->get();

        foreach ($products as $product) {
            $minimumInGrams = (float) $product->base_minimum_quantity;

            if ($minimumInGrams <= 0) {
                continue;
            }

            $priceMultiplier = 1000 / $minimumInGrams;
            $productId = (int) $product->id;

            DB::table('products')->where('id', $productId)->update([
                'default_unit_of_measure_id' => $kilogramUnitId,
                'price_per_kg' => round((float) $product->base_price_per_unit * $priceMultiplier, 2),
                'purchase_cost_per_unit' => round((float) $product->purchase_cost_per_unit * $priceMultiplier, 4),
                'base_price_per_unit' => round((float) $product->base_price_per_unit * $priceMultiplier, 4),
                'restaurant_price_per_unit' => round((float) $product->restaurant_price_per_unit * $priceMultiplier, 4),
                'base_minimum_quantity' => round($minimumInGrams / 1000, 3),
                'restaurant_minimum_quantity' => round((float) $product->restaurant_minimum_quantity / 1000, 3),
            ]);

            DB::table('customer_product_prices')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->each(function (object $price) use ($priceMultiplier): void {
                    $customPrice = $price->custom_price_per_unit ?? $price->custom_price_per_kg;

                    DB::table('customer_product_prices')->where('id', $price->id)->update([
                        'custom_price_per_kg' => $customPrice === null ? null : round((float) $customPrice * $priceMultiplier, 2),
                        'custom_price_per_unit' => $customPrice === null ? null : round((float) $customPrice * $priceMultiplier, 4),
                        'custom_minimum_quantity' => $price->custom_minimum_quantity === null
                            ? null
                            : round((float) $price->custom_minimum_quantity / 1000, 3),
                    ]);
                });

            DB::table('order_items')
                ->where('product_id', $productId)
                ->orderBy('id')
                ->each(function (object $item) use ($priceMultiplier): void {
                    $quantity = round((float) $item->quantity / 1000, 3);
                    $unitPrice = round((float) ($item->unit_price_net ?: $item->price_per_kg) * $priceMultiplier, 4);
                    $purchaseUnitCost = round((float) $item->purchase_cost_per_unit_net * $priceMultiplier, 4);
                    $lineNet = round($unitPrice * $quantity, 2);
                    $lineTax = round($lineNet * (float) $item->tax_percentage / 100, 2);
                    $lineGross = round($lineNet + $lineTax, 2);
                    $purchaseNet = round($purchaseUnitCost * $quantity, 2);
                    $purchaseTax = round($purchaseNet * (float) $item->tax_percentage / 100, 2);
                    $purchaseGross = round($purchaseNet + $purchaseTax, 2);
                    $margin = round($lineNet - $purchaseNet, 2);

                    DB::table('order_items')->where('id', $item->id)->update([
                        'quantity' => $quantity,
                        'price_per_kg' => round($unitPrice, 2),
                        'unit_price_net' => $unitPrice,
                        'line_total' => $lineGross,
                        'line_net' => $lineNet,
                        'line_tax' => $lineTax,
                        'line_gross' => $lineGross,
                        'purchase_cost_per_unit_net' => $purchaseUnitCost,
                        'purchase_cost_net' => $purchaseNet,
                        'purchase_cost_tax' => $purchaseTax,
                        'purchase_cost_gross' => $purchaseGross,
                        'margin_amount' => $margin,
                        'margin_percentage' => $lineNet > 0 ? round($margin / $lineNet * 100, 2) : 0,
                        'unit_of_measure_name' => 'Chilogrammi',
                        'unit_of_measure_symbol' => 'kg',
                    ]);
                });
        }

        $orderIds = DB::table('order_items')
            ->whereIn('product_id', $products->pluck('id'))
            ->distinct()
            ->pluck('order_id');

        foreach ($orderIds as $orderId) {
            $items = DB::table('order_items')->where('order_id', $orderId)->orderBy('sort_order')->get();
            $totalNet = round($items->sum(fn (object $item): float => (float) $item->line_net), 2);
            $totalTax = round($items->sum(fn (object $item): float => (float) $item->line_tax), 2);
            $totalGross = round($totalNet + $totalTax, 2);
            $purchaseCost = round($items->sum(fn (object $item): float => (float) $item->purchase_cost_net), 2);
            $margin = round($totalNet - $purchaseCost, 2);
            $order = DB::table('orders')->where('id', $orderId)->first();

            DB::table('orders')->where('id', $orderId)->update([
                'total_amount' => $totalGross,
                'total_net' => $totalNet,
                'total_tax' => $totalTax,
                'total_gross' => $totalGross,
                'total_purchase_cost_net' => $purchaseCost,
                'gross_margin' => $margin,
                'gross_margin_percentage' => $totalNet > 0 ? round($margin / $totalNet * 100, 2) : 0,
                'payment_amount' => $order?->payment_amount === null ? null : $totalGross,
            ]);

            $snapshot = $items->map(fn (object $item): array => [
                'name' => $item->product_name,
                'quantity' => (string) $item->quantity,
                'unit_symbol' => $item->unit_of_measure_symbol,
                'unit_price_net' => (string) $item->unit_price_net,
                'tax_percentage' => (string) $item->tax_percentage,
                'line_net' => (string) $item->line_net,
                'line_gross' => (string) $item->line_gross,
            ])->values()->all();

            DB::table('delivery_documents')->where('order_id', $orderId)->update([
                'items_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                'total_net' => $totalNet,
                'total_tax' => $totalTax,
                'total_gross' => $totalGross,
            ]);
        }
    }

    public function down(): void {}
};
