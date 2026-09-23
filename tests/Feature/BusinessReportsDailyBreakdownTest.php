<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Filament\Pages\BusinessReports;
use App\Models\CostCategory;
use App\Models\CostMovement;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Orders\CreateManualOrderService;
use App\Services\Orders\RecordOrderPaymentService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BusinessReportsDailyBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);
        $this->paymentMethod = PaymentMethod::create(['name' => 'Contanti', 'active' => true]);

        $category = ProductCategory::query()->firstOrCreate(['name' => 'Frutta'], ['active' => true]);
        $tax = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        $this->product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $tax->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Pomodorino',
            'purchase_cost_per_unit' => 1,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 1,
            'active' => true,
        ]);
    }

    public function test_days_are_grouped_into_weeks_and_todays_row_is_flagged(): void
    {
        // Settembre 2026: 1 settembre è martedì, 6 settembre è domenica (prima settimana).
        $this->payOrder('2026-09-01', quantity: 10, price: 3); // 30 € ricavi, 10 € costo -> 20 €
        $this->payOrder('2026-09-02', quantity: 5, price: 3); // 15 € ricavi, 5 € costo -> 10 €
        CostMovement::create([
            'cost_category_id' => CostCategory::query()->firstOrCreate(['name' => 'Varie'], ['active' => true])->id,
            'movement_date' => '2026-09-01',
            'amount' => 5,
            'description' => 'Carburante',
        ]);

        $this->travelTo(Carbon::parse('2026-09-02 12:00'));

        $page = new BusinessReports();
        $page->month = '2026-09';
        $page->customerType = 'all';
        $rows = $page->dailyBreakdown();

        $this->assertSame(35, $rows->count()); // 30 giorni + 5 settimane

        $sept1 = $rows->firstWhere(fn ($row) => $row->type === 'day' && $row->date->toDateString() === '2026-09-01');
        $this->assertSame(30.0, $sept1->revenue);
        $this->assertSame(10.0, $sept1->cost);
        $this->assertSame(5.0, $sept1->extra_costs);
        $this->assertSame(15.0, $sept1->margin); // 30 - 10 - 5, costo personale escluso

        $sept2 = $rows->firstWhere(fn ($row) => $row->type === 'day' && $row->date->toDateString() === '2026-09-02');
        $this->assertSame(10.0, $sept2->margin); // 15 - 5, nessun costo extra quel giorno
        $this->assertTrue($sept2->date->toDateString() === now()->toDateString());

        $firstWeek = $rows->first(fn ($row) => $row->type === 'week');
        $this->assertSame('2026-09-01', $firstWeek->date->toDateString());
        $this->assertSame('2026-09-06', $firstWeek->date_end->toDateString());
        $this->assertSame(45.0, $firstWeek->revenue); // 30 + 15
        $this->assertSame(15.0, $firstWeek->cost); // 10 + 5
        $this->assertSame(5.0, $firstWeek->extra_costs);
        $this->assertSame(25.0, $firstWeek->margin); // 15 + 10

        $days = $rows->filter(fn ($row) => $row->type === 'day')->count();
        $weeks = $rows->filter(fn ($row) => $row->type === 'week')->count();
        $this->assertSame(30, $days);
        $this->assertSame(5, $weeks);
    }

    public function test_days_without_movements_are_zero_not_missing(): void
    {
        $page = new BusinessReports();
        $page->month = '2026-09';
        $page->customerType = 'all';
        $rows = $page->dailyBreakdown();

        $days = $rows->filter(fn ($row) => $row->type === 'day');
        $this->assertSame(30, $days->count());
        $this->assertTrue($days->every(fn ($row) => $row->margin === 0.0));
    }

    public function test_the_page_renders_with_the_new_section(): void
    {
        $this->payOrder('2026-09-01', quantity: 10, price: 3);
        $this->seed(UserSeeder::class);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->get('/admin/business-reports');

        $response->assertOk();
        $response->assertSee('Guadagno giorno per giorno');
        $response->assertSee('Totale settimana', false);
    }

    private function payOrder(string $date, float $quantity, float $price): void
    {
        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $this->customer->id,
            'status' => 'confirmed',
            'requested_at' => $date,
            'items' => [['product_id' => $this->product->id, 'quantity' => $quantity, 'unit_price_net' => $price]],
        ]);

        app(RecordOrderPaymentService::class)->record($order, [
            'payment_amount' => $order->fresh()->total_gross,
            'payment_method_id' => $this->paymentMethod->id,
            'paid_at' => $date,
        ]);
    }
}
