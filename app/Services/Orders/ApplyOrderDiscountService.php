<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Services\Pricing\PriceCalculator;
use Illuminate\Support\Facades\DB;

class ApplyOrderDiscountService
{
    public function __construct(
        private readonly PriceCalculator $calculator,
        private readonly OrderItemSnapshotService $snapshots,
    ) {}

    public function apply(Order $order, string|int|float $percentage): void
    {
        $discountPercentage = min(100, max(0, (float) $percentage));

        DB::transaction(function () use ($discountPercentage, $order): void {
            foreach ($order->items()->get() as $item) {
                $originalNet = (float) $item->original_line_net > 0
                    ? $item->original_line_net
                    : $this->calculator->lineTotal($item->unit_price_net, $item->quantity);
                $lineNet = $this->calculator->discountedPrice($originalNet, $discountPercentage);
                $discountAmount = $this->calculator->difference($originalNet, $lineNet);
                $lineTax = $this->calculator->tax($lineNet, $item->tax_percentage);
                $lineGross = $this->calculator->sum([$lineNet, $lineTax]);
                $margin = $this->calculator->difference($lineNet, $item->purchase_cost_net);

                $item->update([
                    'original_line_net' => $originalNet,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount_net' => $discountAmount,
                    'line_total' => $lineGross,
                    'line_net' => $lineNet,
                    'line_tax' => $lineTax,
                    'line_gross' => $lineGross,
                    'margin_amount' => $margin,
                    'margin_percentage' => $this->calculator->percentage($margin, $lineNet),
                ]);
            }

            $order->update(['discount_percentage' => $discountPercentage]);
            $this->snapshots->recalculate($order);
        });
    }

    public function estimateGross(Order $order, string|int|float $percentage): float
    {
        $discountPercentage = min(100, max(0, (float) $percentage));
        $itemsGross = $order->items()->get()->sum(function ($item) use ($discountPercentage): float {
            $originalNet = (float) $item->original_line_net > 0
                ? $item->original_line_net
                : $this->calculator->lineTotal($item->unit_price_net, $item->quantity);
            $discountedNet = $this->calculator->discountedPrice($originalNet, $discountPercentage);

            return $this->calculator->sum([
                $discountedNet,
                $this->calculator->tax($discountedNet, $item->tax_percentage),
            ]);
        });

        return $this->calculator->sum([
            $itemsGross,
            $order->shipping_amount_net ?? 0,
            $order->shipping_tax ?? 0,
        ]);
    }
}
