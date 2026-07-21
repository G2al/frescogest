<?php

namespace App\Filament\Widgets;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

class CustomerTypeRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Ricavi per tipologia cliente';

    protected ?string $description = 'Totali IVA inclusa degli ultimi sei mesi, distinti tra privati e ristoratori.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $periods = collect(range(5, 0))->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));

        $datasets = collect(CustomerType::cases())->map(function (CustomerType $type) use ($periods): array {
            $data = $periods->map(fn ($period): float => (float) Order::query()
                ->where('status', OrderStatus::Paid)
                ->whereYear('paid_at', $period->year)
                ->whereMonth('paid_at', $period->month)
                ->whereHas('customer', fn ($customers) => $customers->where('type', $type->value))
                ->sum('total_gross'));

            return [
                'label' => $type->label(),
                'data' => $data,
                'backgroundColor' => $type === CustomerType::Private ? '#16a34a' : '#2563eb',
            ];
        });

        return [
            'datasets' => $datasets->all(),
            'labels' => $periods->map(fn ($period) => $period->translatedFormat('M Y'))->all(),
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
                'x' => ['stacked' => false, 'grid' => ['display' => false]],
                'y' => ['beginAtZero' => true],
            ],
        ];
    }
}
