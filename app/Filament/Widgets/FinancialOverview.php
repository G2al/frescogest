<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PartnerGoodsEntry;
use App\Services\Employees\EmployeeCostService;
use App\Services\Reports\TrendService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialOverview extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true;
    }

    protected function getStats(): array
    {
        $current = $this->summaryFor(now()->year, now()->month);
        $previousDate = now()->subMonthNoOverflow();
        $previous = $this->summaryFor($previousDate->year, $previousDate->month);
        $best = $this->bestProduct();

        return [
            $this->stat('Ricavi netti', 'revenue', $current, $previous, 'Ordini pagati e forniture partner', 'success'),
            $this->stat('Costo merce', 'costOfGoods', $current, $previous, 'Costo netto della merce venduta', 'warning', true),
            $this->stat('Margine lordo', 'grossMargin', $current, $previous, number_format($current['marginPercentage'], 1, ',', '.').'% sui ricavi', 'info'),
            $this->stat('Costo del personale', 'personnelCosts', $current, $previous, 'Paghe giornaliere e mensili', 'warning', true),
            $this->stat('Risultato reale', 'netResult', $current, $previous, 'Costi operativi: '.$this->money($current['operatingCosts']), $current['netResult'] >= 0 ? 'success' : 'danger'),
            Stat::make('Prodotto più redditizio', $best?->product_name ?? 'Nessun dato')
                ->description($best ? $this->money((float) $best->aggregate_margin) : 'Nessuna vendita nel mese')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('primary'),
        ];
    }

    private function summaryFor(int $year, int $month): array
    {
        $orders = Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month);
        $partnerGoods = PartnerGoodsEntry::query()
            ->whereYear('delivered_on', $year)
            ->whereMonth('delivered_on', $month);
        $revenue = (float) (clone $orders)->sum('total_net') + (float) (clone $partnerGoods)->sum('total_net');
        $costOfGoods = (float) (clone $orders)->sum('total_purchase_cost_net') + (float) (clone $partnerGoods)->sum('total_cost_net');
        $grossMargin = $revenue - $costOfGoods;
        $extraCosts = (float) CostMovement::query()
            ->whereYear('movement_date', $year)
            ->whereMonth('movement_date', $month)
            ->sum('amount');
        $personnelCosts = app(EmployeeCostService::class)->forMonth($year, $month);
        $operatingCosts = $extraCosts + $personnelCosts;

        return compact('revenue', 'costOfGoods', 'grossMargin', 'personnelCosts', 'operatingCosts') + [
            'netResult' => $grossMargin - $operatingCosts,
            'marginPercentage' => $revenue > 0 ? $grossMargin / $revenue * 100 : 0,
        ];
    }

    private function stat(
        string $label,
        string $key,
        array $current,
        array $previous,
        string $description,
        string $color,
        bool $lowerIsBetter = false,
    ): Stat {
        $trend = app(TrendService::class)->compare(
            (float) $current[$key],
            (float) $previous[$key],
            $lowerIsBetter,
        );

        return Stat::make($label, $this->money((float) $current[$key]))
            ->description($description)
            ->descriptionIcon(
                $trend['direction'] === 'flat'
                    ? 'heroicon-m-minus'
                    : ($trend['favorable']
                        ? 'heroicon-m-arrow-trending-up'
                        : 'heroicon-m-arrow-trending-down')
            )
            ->color($trend['favorable'] ? $color : 'danger');
    }

    private function bestProduct(): ?object
    {
        $year = now()->year;
        $month = now()->month;
        $customerProducts = OrderItem::query()
            ->whereHas('order', fn ($query) => $query
                ->where('status', OrderStatus::Paid)
                ->whereYear('paid_at', $year)
                ->whereMonth('paid_at', $month))
            ->selectRaw('product_id, product_name, SUM(margin_amount) as aggregate_margin')
            ->groupBy('product_id', 'product_name')
            ->get();
        $partnerProducts = DB::table('partner_goods_entries')
            ->join('products', 'products.id', '=', 'partner_goods_entries.product_id')
            ->whereYear('partner_goods_entries.delivered_on', $year)
            ->whereMonth('partner_goods_entries.delivered_on', $month)
            ->selectRaw('products.id as product_id, products.name as product_name, SUM(partner_goods_entries.total_net - partner_goods_entries.total_cost_net) as aggregate_margin')
            ->groupBy('products.id', 'products.name')
            ->get();

        return $customerProducts
            ->concat($partnerProducts)
            ->groupBy('product_id')
            ->map(fn ($rows): object => (object) [
                'product_name' => $rows->first()->product_name,
                'aggregate_margin' => (float) $rows->sum('aggregate_margin'),
            ])
            ->sortByDesc('aggregate_margin')
            ->first();
    }

    private function money(float $amount): string
    {
        return '€ '.number_format($amount, 2, ',', '.');
    }
}
