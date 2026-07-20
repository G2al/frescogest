<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class MonthlyPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Andamento economico ultimi 12 mesi';

    protected function getData(): array
    {
        $periods = collect(range(11, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo))
            ->push(now()->startOfMonth());

        $data = $periods->map(function ($period): array {
            $orders = Order::query()->where('status', OrderStatus::Paid)
                ->whereYear('paid_at', $period->year)->whereMonth('paid_at', $period->month);
            $revenue = (float) (clone $orders)->sum('total_net');
            $costOfGoods = (float) (clone $orders)->sum('total_purchase_cost_net');
            $extraCosts = (float) CostMovement::query()->whereYear('movement_date', $period->year)->whereMonth('movement_date', $period->month)->sum('amount');

            return ['revenue' => $revenue, 'margin' => $revenue - $costOfGoods, 'result' => $revenue - $costOfGoods - $extraCosts];
        });

        return [
            'datasets' => [
                ['label' => 'Ricavi netti', 'data' => $data->pluck('revenue'), 'borderColor' => '#07845f', 'backgroundColor' => '#07845f33'],
                ['label' => 'Margine lordo', 'data' => $data->pluck('margin'), 'borderColor' => '#0ea5e9', 'backgroundColor' => '#0ea5e933'],
                ['label' => 'Risultato reale', 'data' => $data->pluck('result'), 'borderColor' => '#f59e0b', 'backgroundColor' => '#f59e0b33'],
            ],
            'labels' => $periods->map(fn ($period) => $period->translatedFormat('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
