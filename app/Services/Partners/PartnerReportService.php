<?php

namespace App\Services\Partners;

use App\Models\Partner;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PartnerReportService
{
    public function build(Partner $partner, CarbonInterface $from, CarbonInterface $to): array
    {
        $goods = $partner->goodsEntries()
            ->with(['product.defaultUnitOfMeasure'])
            ->whereDate('delivered_on', '>=', $from->toDateString())
            ->whereDate('delivered_on', '<=', $to->toDateString());
        $receipts = $partner->dailyReceipts()
            ->whereDate('receipt_date', '>=', $from->toDateString())
            ->whereDate('receipt_date', '<=', $to->toDateString());
        $wastes = $partner->dailyWastes()
            ->whereDate('waste_date', '>=', $from->toDateString())
            ->whereDate('waste_date', '<=', $to->toDateString());
        $expenses = $partner->expenses()
            ->whereDate('expense_date', '>=', $from->toDateString())
            ->whereDate('expense_date', '<=', $to->toDateString());

        $purchasesNet = (float) (clone $goods)->sum('total_net');
        $purchasesTax = (float) (clone $goods)->sum('total_tax');
        $purchasesGross = (float) (clone $goods)->sum('total_gross');
        $revenueGross = (float) (clone $receipts)->sum('gross_amount');
        $wasteAmount = (float) (clone $wastes)->sum('amount');
        $expenseAmount = (float) (clone $expenses)->sum('amount');
        $estimatedResult = $revenueGross - $purchasesGross - $wasteAmount - $expenseAmount;

        return [
            'summary' => [
                'purchases_net' => $purchasesNet,
                'purchases_tax' => $purchasesTax,
                'purchases_gross' => $purchasesGross,
                'revenue_gross' => $revenueGross,
                'waste_amount' => $wasteAmount,
                'expense_amount' => $expenseAmount,
                'estimated_result' => $estimatedResult,
                'estimated_margin_percentage' => $revenueGross > 0
                    ? round($estimatedResult / $revenueGross * 100, 2)
                    : 0,
            ],
            'products' => $this->productSummary((clone $goods)->get()),
            'receipts' => (clone $receipts)->orderBy('receipt_date')->get(),
            'wastes' => (clone $wastes)->orderBy('waste_date')->get(),
            'expenses' => (clone $expenses)->orderBy('expense_date')->get(),
        ];
    }

    private function productSummary(Collection $entries): Collection
    {
        return $entries
            ->groupBy('product_id')
            ->map(function (Collection $rows): object {
                $first = $rows->first();

                return (object) [
                    'name' => $first->product->name,
                    'symbol' => $first->product->defaultUnitOfMeasure?->symbol,
                    'quantity' => $rows->sum('quantity'),
                    'net' => $rows->sum('total_net'),
                    'tax' => $rows->sum('total_tax'),
                    'gross' => $rows->sum('total_gross'),
                ];
            })
            ->sortByDesc('gross')
            ->values();
    }
}
