<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class MonthlyPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Andamento economico ultimi 12 mesi';

    protected ?string $description = 'Confronto mensile tra ricavi netti, margine lordo e risultato dopo i costi extra.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '390px';

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
                ['label' => 'Ricavi netti', 'data' => $data->pluck('revenue'), 'backgroundColor' => '#07845f'],
                ['label' => 'Margine lordo', 'data' => $data->pluck('margin'), 'backgroundColor' => '#0ea5e9'],
                ['label' => 'Risultato reale', 'data' => $data->pluck('result'), 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $periods->map(fn ($period) => $period->translatedFormat('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => [
                'x' => ['grid' => ['display' => false]],
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}
