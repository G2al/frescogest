<?php

namespace App\Filament\Pages;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PartnerGoodsEntry;
use App\Services\Employees\EmployeeCostService;
use App\Services\Reports\TrendService;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class BusinessReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Contabilità';

    protected static ?string $navigationLabel = 'Analisi economica';

    protected static ?string $title = 'Analisi economica';

    protected string $view = 'filament.pages.business-reports';

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $month;

    public string $customerType = 'all';

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->hasAdminPanelRole() === true;
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function summary(): array
    {
        return $this->summaryFor(...$this->period());
    }

    public function trends(): array
    {
        [$year, $month] = $this->period();
        $current = $this->summaryFor($year, $month);
        $previousPeriod = CarbonImmutable::create($year, $month)->subMonth();
        $previous = $this->summaryFor($previousPeriod->year, $previousPeriod->month);
        $lowerIsBetter = ['tax', 'purchaseTax', 'discounts', 'costOfGoods', 'extraCosts', 'personnelCosts', 'operatingCosts'];

        return collect($current)
            ->only([
                'revenue',
                'grossRevenue',
                'tax',
                'purchaseTax',
                'vatBalance',
                'discounts',
                'costOfGoods',
                'grossMargin',
                'extraCosts',
                'personnelCosts',
                'operatingCosts',
                'netResult',
            ])
            ->mapWithKeys(fn ($value, string $key): array => [
                $key => app(TrendService::class)->compare(
                    (float) $value,
                    (float) ($previous[$key] ?? 0),
                    in_array($key, $lowerIsBetter, true),
                ),
            ])
            ->all();
    }

    public function products(): Collection
    {
        [$year, $month] = $this->period();
        $rows = collect();

        if ($this->includesCustomers()) {
            $rows = $rows->concat(
                DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('customers', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::Paid->value)
                    ->whereYear('orders.paid_at', $year)
                    ->whereMonth('orders.paid_at', $month)
                    ->when($this->customerTypeValue(), fn ($query, string $type) => $query->where('customers.type', $type))
                    ->selectRaw("'customer' as source, order_items.product_id, order_items.product_name, order_items.unit_of_measure_symbol, SUM(order_items.quantity) as quantity, SUM(order_items.line_net) as revenue, SUM(order_items.purchase_cost_net) as cost")
                    ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.unit_of_measure_symbol')
                    ->get()
            );
        }

        if ($this->includesPartners()) {
            $rows = $rows->concat(
                DB::table('partner_goods_entries')
                    ->join('products', 'products.id', '=', 'partner_goods_entries.product_id')
                    ->join('unit_of_measures', 'unit_of_measures.id', '=', 'products.default_unit_of_measure_id')
                    ->whereYear('partner_goods_entries.delivered_on', $year)
                    ->whereMonth('partner_goods_entries.delivered_on', $month)
                    ->selectRaw("'partner' as source, products.id as product_id, products.name as product_name, unit_of_measures.symbol as unit_of_measure_symbol, SUM(partner_goods_entries.quantity) as quantity, SUM(partner_goods_entries.total_net) as revenue, SUM(partner_goods_entries.total_cost_net) as cost")
                    ->groupBy('products.id', 'products.name', 'unit_of_measures.symbol')
                    ->get()
            );
        }

        return $rows
            ->groupBy(fn (object $row): string => $row->product_id.'|'.$row->unit_of_measure_symbol)
            ->map(function (Collection $group): object {
                $first = $group->first();
                $revenue = (float) $group->sum('revenue');
                $cost = (float) $group->sum('cost');

                return (object) [
                    'product_name' => $first->product_name,
                    'unit_of_measure_symbol' => $first->unit_of_measure_symbol,
                    'quantity' => (float) $group->sum('quantity'),
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'margin' => $revenue - $cost,
                ];
            })
            ->sortByDesc('margin')
            ->values();
    }

    public function categories(): Collection
    {
        [$year, $month] = $this->period();
        $rows = collect();

        if ($this->includesCustomers()) {
            $rows = $rows->concat(
                DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('products', 'products.id', '=', 'order_items.product_id')
                    ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
                    ->join('customers', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::Paid->value)
                    ->whereYear('orders.paid_at', $year)
                    ->whereMonth('orders.paid_at', $month)
                    ->when($this->customerTypeValue(), fn ($query, string $type) => $query->where('customers.type', $type))
                    ->selectRaw('product_categories.id, product_categories.name, SUM(order_items.line_net) as revenue, SUM(order_items.purchase_cost_net) as cost')
                    ->groupBy('product_categories.id', 'product_categories.name')
                    ->get()
            );
        }

        if ($this->includesPartners()) {
            $rows = $rows->concat(
                DB::table('partner_goods_entries')
                    ->join('products', 'products.id', '=', 'partner_goods_entries.product_id')
                    ->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
                    ->whereYear('partner_goods_entries.delivered_on', $year)
                    ->whereMonth('partner_goods_entries.delivered_on', $month)
                    ->selectRaw('product_categories.id, product_categories.name, SUM(partner_goods_entries.total_net) as revenue, SUM(partner_goods_entries.total_cost_net) as cost')
                    ->groupBy('product_categories.id', 'product_categories.name')
                    ->get()
            );
        }

        return $rows
            ->groupBy('id')
            ->map(function (Collection $group): object {
                $revenue = (float) $group->sum('revenue');
                $cost = (float) $group->sum('cost');

                return (object) [
                    'name' => $group->first()->name,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'margin' => $revenue - $cost,
                ];
            })
            ->sortByDesc('margin')
            ->values();
    }

    public function customers(): Collection
    {
        [$year, $month] = $this->period();
        $rows = collect();

        if ($this->includesCustomers()) {
            $rows = $rows->concat(
                DB::table('orders')
                    ->join('customers', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::Paid->value)
                    ->whereYear('orders.paid_at', $year)
                    ->whereMonth('orders.paid_at', $month)
                    ->when($this->customerTypeValue(), fn ($query, string $type) => $query->where('customers.type', $type))
                    ->selectRaw('customers.id, customers.company_name, customers.first_name, customers.last_name, COUNT(orders.id) as orders_count, SUM(orders.total_net) as revenue, SUM(orders.gross_margin) as margin')
                    ->groupBy('customers.id', 'customers.company_name', 'customers.first_name', 'customers.last_name')
                    ->get()
                    ->map(fn (object $row): object => (object) [
                        'row_key' => 'customer-'.$row->id,
                        'display_name' => filled($row->company_name)
                            ? $row->company_name
                            : trim($row->first_name.' '.$row->last_name),
                        'recipient_type' => 'Cliente',
                        'orders_count' => $row->orders_count,
                        'revenue' => $row->revenue,
                        'margin' => $row->margin,
                    ])
            );
        }

        if ($this->includesPartners()) {
            $rows = $rows->concat(
                DB::table('partner_goods_entries')
                    ->join('partners', 'partners.id', '=', 'partner_goods_entries.partner_id')
                    ->whereYear('partner_goods_entries.delivered_on', $year)
                    ->whereMonth('partner_goods_entries.delivered_on', $month)
                    ->selectRaw('partners.id, partners.name as display_name, COUNT(DISTINCT partner_goods_entries.delivery_document_id) as orders_count, SUM(partner_goods_entries.total_net) as revenue, SUM(partner_goods_entries.total_net - partner_goods_entries.total_cost_net) as margin')
                    ->groupBy('partners.id', 'partners.name')
                    ->get()
                    ->map(fn (object $row): object => (object) [
                        'row_key' => 'partner-'.$row->id,
                        'display_name' => $row->display_name,
                        'recipient_type' => 'Partner',
                        'orders_count' => $row->orders_count,
                        'revenue' => $row->revenue,
                        'margin' => $row->margin,
                    ])
            );
        }

        return $rows->sortByDesc('revenue')->values();
    }

    public function taxBreakdown(): Collection
    {
        [$year, $month] = $this->period();
        $rows = collect();

        if ($this->includesCustomers()) {
            $rows = $rows->concat(
                DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->join('customers', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::Paid->value)
                    ->whereYear('orders.paid_at', $year)
                    ->whereMonth('orders.paid_at', $month)
                    ->when($this->customerTypeValue(), fn ($query, string $type) => $query->where('customers.type', $type))
                    ->selectRaw('order_items.tax_percentage, SUM(order_items.line_net) as taxable, SUM(order_items.line_tax) as tax, SUM(order_items.line_gross) as gross, SUM(order_items.purchase_cost_net) as purchase_taxable, SUM(order_items.purchase_cost_tax) as purchase_tax, SUM(order_items.purchase_cost_gross) as purchase_gross')
                    ->groupBy('order_items.tax_percentage')
                    ->get()
            );

            $rows = $rows->concat(
                DB::table('orders')
                    ->join('customers', 'customers.id', '=', 'orders.customer_id')
                    ->where('orders.status', OrderStatus::Paid->value)
                    ->whereYear('orders.paid_at', $year)
                    ->whereMonth('orders.paid_at', $month)
                    ->where('orders.shipping_amount_net', '>', 0)
                    ->when($this->customerTypeValue(), fn ($query, string $type) => $query->where('customers.type', $type))
                    ->selectRaw('orders.shipping_tax_percentage as tax_percentage, SUM(orders.shipping_amount_net) as taxable, SUM(orders.shipping_tax) as tax, SUM(orders.shipping_amount_net + orders.shipping_tax) as gross, 0 as purchase_taxable, 0 as purchase_tax, 0 as purchase_gross')
                    ->groupBy('orders.shipping_tax_percentage')
                    ->get()
            );
        }

        if ($this->includesPartners()) {
            $rows = $rows->concat(
                DB::table('partner_goods_entries')
                    ->whereYear('delivered_on', $year)
                    ->whereMonth('delivered_on', $month)
                    ->selectRaw('tax_percentage, SUM(total_net) as taxable, SUM(total_tax) as tax, SUM(total_gross) as gross, SUM(total_cost_net) as purchase_taxable, SUM(total_cost_tax) as purchase_tax, SUM(total_cost_gross) as purchase_gross')
                    ->groupBy('tax_percentage')
                    ->get()
            );
        }

        return $rows
            ->groupBy(fn (object $row): string => number_format((float) $row->tax_percentage, 2, '.', ''))
            ->map(function (Collection $group, string $percentage): object {
                return (object) [
                    'tax_percentage' => $percentage,
                    'taxable' => $group->sum('taxable'),
                    'tax' => $group->sum('tax'),
                    'gross' => $group->sum('gross'),
                    'purchase_taxable' => $group->sum('purchase_taxable'),
                    'purchase_tax' => $group->sum('purchase_tax'),
                    'purchase_gross' => $group->sum('purchase_gross'),
                    'vat_balance' => $group->sum('tax') - $group->sum('purchase_tax'),
                ];
            })
            ->sortBy('tax_percentage')
            ->values();
    }

    public function customerTypeOptions(): array
    {
        return [
            'all' => 'Tutta l’attività',
            'customers' => 'Tutti i clienti',
            ...CustomerType::options(),
            'partners' => 'Partner',
        ];
    }

    private function summaryFor(int $year, int $month): array
    {
        $orders = $this->paidOrders($year, $month);
        $partnerGoods = $this->partnerGoods($year, $month);
        $revenue = (float) (clone $orders)->sum('total_net') + (float) (clone $partnerGoods)->sum('total_net');
        $grossRevenue = (float) (clone $orders)->sum('total_gross') + (float) (clone $partnerGoods)->sum('total_gross');
        $tax = (float) (clone $orders)->sum('total_tax') + (float) (clone $partnerGoods)->sum('total_tax');
        $purchaseTax = (float) $this->paidOrderItems($year, $month)->sum('purchase_cost_tax')
            + (float) (clone $partnerGoods)->sum('total_cost_tax');
        $discounts = (float) (clone $orders)->sum('discount_amount_net');
        $costOfGoods = (float) (clone $orders)->sum('total_purchase_cost_net')
            + (float) (clone $partnerGoods)->sum('total_cost_net');
        $extraCosts = (float) CostMovement::query()
            ->whereYear('movement_date', $year)
            ->whereMonth('movement_date', $month)
            ->sum('amount');
        $personnelCosts = app(EmployeeCostService::class)->forMonth($year, $month);
        $operatingCosts = $extraCosts + $personnelCosts;
        $grossMargin = $revenue - $costOfGoods;

        return compact(
            'revenue',
            'grossRevenue',
            'tax',
            'purchaseTax',
            'discounts',
            'costOfGoods',
            'extraCosts',
            'personnelCosts',
            'operatingCosts',
            'grossMargin',
        ) + [
            'vatBalance' => $tax - $purchaseTax,
            'netResult' => $grossMargin - $operatingCosts,
            'marginPercentage' => $revenue > 0 ? $grossMargin / $revenue * 100 : 0,
            'ordersCount' => (clone $orders)->count(),
            'partnerSuppliesCount' => (clone $partnerGoods)
                ->whereNotNull('delivery_document_id')
                ->distinct()
                ->count('delivery_document_id')
                + (clone $partnerGoods)->whereNull('delivery_document_id')->count(),
        ];
    }

    private function paidOrders(int $year, int $month): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->when(! $this->includesCustomers(), fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($this->customerTypeValue(), fn (Builder $query, string $type) => $query->whereHas('customer', fn (Builder $customers) => $customers->where('type', $type)));
    }

    private function paidOrderItems(int $year, int $month): Builder
    {
        return OrderItem::query()
            ->whereHas('order', function (Builder $orders) use ($year, $month): void {
                $orders->where('status', OrderStatus::Paid)
                    ->whereYear('paid_at', $year)
                    ->whereMonth('paid_at', $month)
                    ->when(! $this->includesCustomers(), fn (Builder $query) => $query->whereRaw('1 = 0'))
                    ->when($this->customerTypeValue(), fn (Builder $query, string $type) => $query->whereHas('customer', fn (Builder $customers) => $customers->where('type', $type)));
            });
    }

    private function partnerGoods(int $year, int $month): Builder
    {
        return PartnerGoodsEntry::query()
            ->whereYear('delivered_on', $year)
            ->whereMonth('delivered_on', $month)
            ->when(! $this->includesPartners(), fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    private function includesCustomers(): bool
    {
        return $this->customerType !== 'partners';
    }

    private function includesPartners(): bool
    {
        return in_array($this->customerType, ['all', 'partners'], true);
    }

    private function customerTypeValue(): ?string
    {
        return CustomerType::tryFrom($this->customerType)?->value;
    }

    private function period(): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $this->month, $matches)) {
            return [(int) now()->year, (int) now()->month];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }
}
