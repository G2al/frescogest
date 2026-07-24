<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\PartnerDailyReceipt;
use App\Models\PartnerDailyWaste;
use App\Models\PartnerExpense;
use App\Models\PartnerGoodsEntry;
use App\Models\PartnerProductPrice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Partners\PartnerPriceListService;
use App\Services\Partners\PartnerReportService;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_price_list_uses_shared_products_and_calculates_prices(): void
    {
        [$partner, $product] = $this->partnerAndProduct();

        app(PartnerPriceListService::class)->syncPartner($partner);

        $price = PartnerProductPrice::query()->firstOrFail();

        $this->assertSame($product->id, $price->product_id);
        $this->assertSame('2.0000', $price->purchase_price_net);
        $this->assertSame('4.0000', $price->sale_price_net);
        $this->assertSame(4.16, $price->sale_price_gross);

        $price->update(['sale_price_net' => 5]);

        $this->assertSame('150.00', $price->fresh()->markup_percentage);
    }

    public function test_goods_entry_snapshots_partner_price_and_report_totals(): void
    {
        [$partner, $product] = $this->partnerAndProduct();

        PartnerProductPrice::query()
            ->whereBelongsTo($partner)
            ->whereBelongsTo($product)
            ->firstOrFail()
            ->update([
                'purchase_price_net' => 2,
                'markup_percentage' => 100,
            ]);
        PartnerGoodsEntry::create([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'delivered_on' => today(),
            'quantity' => 10,
        ]);
        PartnerDailyReceipt::create([
            'partner_id' => $partner->id,
            'receipt_date' => today(),
            'gross_amount' => 60,
        ]);
        PartnerDailyWaste::create([
            'partner_id' => $partner->id,
            'waste_date' => today(),
            'amount' => 4,
        ]);
        PartnerExpense::create([
            'partner_id' => $partner->id,
            'expense_date' => today(),
            'description' => 'Sacchetti',
            'amount' => 2,
        ]);

        $entry = PartnerGoodsEntry::query()->firstOrFail();
        $report = app(PartnerReportService::class)->build($partner, today(), today());

        $this->assertSame('20.00', $entry->total_net);
        $this->assertSame('0.80', $entry->total_tax);
        $this->assertSame('20.80', $entry->total_gross);
        $this->assertSame(33.2, $report['summary']['estimated_result']);
        $this->assertSame(55.33, $report['summary']['estimated_margin_percentage']);
    }

    public function test_panel_roles_keep_admin_and_partner_access_separate(): void
    {
        $admin = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
            'panel_role' => 'admin',
        ]);
        $partnerUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
            'panel_role' => 'partner',
        ]);
        Partner::create(['user_id' => $partnerUser->id, 'name' => 'Angela', 'active' => true]);

        $adminPanel = Panel::make()->id('admin');
        $partnerPanel = Panel::make()->id('partner');

        $this->assertTrue($admin->canAccessPanel($adminPanel));
        $this->assertFalse($admin->canAccessPanel($partnerPanel));
        $this->assertFalse($partnerUser->canAccessPanel($adminPanel));
        $this->assertTrue($partnerUser->canAccessPanel($partnerPanel));
    }

    public function test_partner_can_open_only_the_dedicated_panel_pages(): void
    {
        $partnerUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
            'panel_role' => 'partner',
        ]);
        Partner::create(['user_id' => $partnerUser->id, 'name' => 'Angela', 'active' => true]);

        $this->actingAs($partnerUser, 'admin');

        $this->get('/partner/dashboard')->assertOk();
        $this->get('/partner/daily-receipts')->assertOk();
        $this->get('/partner/daily-wastes')->assertOk();
        $this->get('/partner/expenses')->assertOk();
        $this->get('/partner/goods-entries')->assertOk();
        $this->get('/partner/product-prices')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    private function partnerAndProduct(): array
    {
        $partner = Partner::create(['name' => 'Angela', 'active' => true]);
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mele',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 100,
            'restaurant_markup_percentage' => 100,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);

        return [$partner, $product];
    }
}
