<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\CreateManualOrderService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailySummaryPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_daily_summary_pdf(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::create(['name' => 'Verdura', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $tomato = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Pomodori',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);
        $customer = Customer::factory()->create(['type' => CustomerType::Restaurant]);
        $order = app(CreateManualOrderService::class)->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'requested_at' => now(),
            'items' => [['product_id' => $tomato->id, 'quantity' => 10, 'unit_price_net' => 2]],
        ]);
        app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.daily-summary.pdf', [
            'date' => now()->toDateString(),
            'includePartners' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_the_pdf_works_with_no_deliveries_in_the_day(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin, 'admin')->get(route('admin.daily-summary.pdf', [
            'date' => now()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_guests_cannot_download_the_daily_summary_pdf(): void
    {
        $response = $this->get(route('admin.daily-summary.pdf'));

        $response->assertRedirect();
    }
}
