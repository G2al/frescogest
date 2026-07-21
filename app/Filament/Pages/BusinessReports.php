<?php

namespace App\Filament\Pages;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Models\CostMovement;
use App\Models\Order;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
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

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function summary(): array
    {
        [$year, $month] = $this->period();
        $orders = $this->paidOrders($year, $month);
        $revenue = (float) (clone $orders)->sum('total_net');
        $grossRevenue = (float) (clone $orders)->sum('total_gross');
        $tax = (float) (clone $orders)->sum('total_tax');
        $discounts = (float) (clone $orders)->sum('discount_amount_net');
        $costOfGoods = (float) (clone $orders)->sum('total_purchase_cost_net');
        $extraCosts = (float) CostMovement::query()->whereYear('movement_date', $year)->whereMonth('movement_date', $month)->sum('amount');
        $grossMargin = $revenue - $costOfGoods;

        return compact('revenue', 'grossRevenue', 'tax', 'discounts', 'costOfGoods', 'extraCosts', 'grossMargin') + [
            'netResult' => $grossMargin - $extraCosts,
            'marginPercentage' => $revenue > 0 ? $grossMargin / $revenue * 100 : 0,
            'ordersCount' => (clone $orders)->count(),
        ];
    }

    public function products(): Collection
    {
        [$year, $month] = $this->period();

        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', OrderStatus::Paid->value)->whereYear('orders.paid_at', $year)->whereMonth('orders.paid_at', $month)
            ->when($this->customerType !== 'all', fn ($query) => $query->where('customers.type', $this->customerType))
            ->selectRaw('order_items.product_name, order_items.unit_of_measure_symbol, SUM(order_items.quantity) as quantity, SUM(order_items.line_net) as revenue, SUM(order_items.purchase_cost_net) as cost, SUM(order_items.margin_amount) as margin')
            ->groupBy('order_items.product_name', 'order_items.unit_of_measure_symbol')->orderByDesc('margin')->get();
    }

    public function categories(): Collection
    {
        [$year, $month] = $this->period();

        return DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')->join('product_categories', 'product_categories.id', '=', 'products.product_category_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', OrderStatus::Paid->value)->whereYear('orders.paid_at', $year)->whereMonth('orders.paid_at', $month)
            ->when($this->customerType !== 'all', fn ($query) => $query->where('customers.type', $this->customerType))
            ->selectRaw('product_categories.name, SUM(order_items.line_net) as revenue, SUM(order_items.purchase_cost_net) as cost, SUM(order_items.margin_amount) as margin')
            ->groupBy('product_categories.name')->orderByDesc('margin')->get();
    }

    public function customers(): Collection
    {
        [$year, $month] = $this->period();

        return DB::table('orders')->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', OrderStatus::Paid->value)->whereYear('orders.paid_at', $year)->whereMonth('orders.paid_at', $month)
            ->when($this->customerType !== 'all', fn ($query) => $query->where('customers.type', $this->customerType))
            ->selectRaw('customers.company_name, customers.first_name, customers.last_name, COUNT(orders.id) as orders_count, SUM(orders.total_net) as revenue, SUM(orders.gross_margin) as margin')
            ->groupBy('customers.id', 'customers.company_name', 'customers.first_name', 'customers.last_name')->orderByDesc('revenue')->get();
    }

    public function taxBreakdown(): Collection
    {
        [$year, $month] = $this->period();

        $items = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereYear('orders.paid_at', $year)
            ->whereMonth('orders.paid_at', $month)
            ->when($this->customerType !== 'all', fn ($query) => $query->where('customers.type', $this->customerType))
            ->selectRaw('order_items.tax_percentage, SUM(order_items.line_net) as taxable, SUM(order_items.line_tax) as tax, SUM(order_items.line_gross) as gross')
            ->groupBy('order_items.tax_percentage')
            ->orderBy('order_items.tax_percentage')
            ->get();

        $shipping = DB::table('orders')
            ->join('customers', 'customers.id', '=', 'orders.customer_id')
            ->where('orders.status', OrderStatus::Paid->value)
            ->whereYear('orders.paid_at', $year)
            ->whereMonth('orders.paid_at', $month)
            ->where('orders.shipping_amount_net', '>', 0)
            ->when($this->customerType !== 'all', fn ($query) => $query->where('customers.type', $this->customerType))
            ->selectRaw('orders.shipping_tax_percentage as tax_percentage, SUM(orders.shipping_amount_net) as taxable, SUM(orders.shipping_tax) as tax, SUM(orders.shipping_amount_net + orders.shipping_tax) as gross')
            ->groupBy('orders.shipping_tax_percentage')
            ->get();

        return $items->concat($shipping)
            ->groupBy(fn ($row) => number_format((float) $row->tax_percentage, 2, '.', ''))
            ->map(function (Collection $rows, string $percentage): object {
                return (object) [
                    'tax_percentage' => $percentage,
                    'taxable' => $rows->sum('taxable'),
                    'tax' => $rows->sum('tax'),
                    'gross' => $rows->sum('gross'),
                ];
            })->values();
    }

    public function customerTypeOptions(): array
    {
        return ['all' => 'Tutti i clienti', ...CustomerType::options()];
    }

    private function paidOrders(int $year, int $month)
    {
        return Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->when($this->customerType !== 'all', fn ($query) => $query->whereHas('customer', fn ($customers) => $customers->where('type', $this->customerType)));
    }

    private function period(): array
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', $this->month, $matches)) {
            return [(int) now()->year, (int) now()->month];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }
}
