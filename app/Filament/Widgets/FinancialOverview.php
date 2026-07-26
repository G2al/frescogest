<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Employees\EmployeeCostService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $orders = Order::query()->where('status', OrderStatus::Paid);
        $revenue = (float) (clone $orders)->sum('total_net');
        $costOfGoods = (float) (clone $orders)->sum('total_purchase_cost_net');
        $grossMargin = $revenue - $costOfGoods;
        $extraCosts = (float) CostMovement::query()->sum('amount');
        $personnelCosts = app(EmployeeCostService::class)->total();
        $operatingCosts = $extraCosts + $personnelCosts;
        $best = OrderItem::query()->whereHas('order', fn ($query) => $query->where('status', OrderStatus::Paid))
            ->selectRaw('product_name, SUM(margin_amount) as aggregate_margin')
            ->groupBy('product_name')->orderByDesc('aggregate_margin')->first();

        return [
            Stat::make('Ricavi netti', $this->money($revenue))->description('Solo ordini pagati')->color('success'),
            Stat::make('Costo merce', $this->money($costOfGoods))->description('Food cost netto')->color('warning'),
            Stat::make('Margine lordo', $this->money($grossMargin))->description($revenue > 0 ? number_format($grossMargin / $revenue * 100, 1, ',', '.').'%' : '0%')->color('info'),
            Stat::make('Costo del personale', $this->money($personnelCosts))->description('Paghe giornaliere e mensili')->color('warning'),
            Stat::make('Risultato reale', $this->money($grossMargin - $operatingCosts))->description('Costi operativi: '.$this->money($operatingCosts))->color(($grossMargin - $operatingCosts) >= 0 ? 'success' : 'danger'),
            Stat::make('Prodotto più redditizio', $best?->product_name ?? 'Nessun dato')->description($best ? $this->money((float) $best->aggregate_margin) : 'Ordini pagati richiesti')->color('primary'),
        ];
    }

    private function money(float $amount): string
    {
        return '€ '.number_format($amount, 2, ',', '.');
    }
}
