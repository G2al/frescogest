<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Filament\Pages\BusinessReports;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DeliveryDocument;
use App\Models\Order;
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
use App\Services\Documents\PartnerDeliveryDocumentService;
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
        $this->assertSame('1.0000', $entry->unit_cost_net);
        $this->assertSame('10.00', $entry->total_cost_net);
        $this->assertSame('10.40', $entry->total_cost_gross);
        $this->assertSame(33.2, $report['summary']['estimated_result']);
        $this->assertSame(55.33, $report['summary']['estimated_margin_percentage']);
    }

    public function test_general_report_can_include_or_isolate_partner_supplies(): void
    {
        [$partner, $product] = $this->partnerAndProduct();
        $customer = Customer::factory()->create(['type' => CustomerType::Private]);
        Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'IPF-000001',
            'status' => OrderStatus::Paid,
            'requested_at' => now(),
            'paid_at' => now(),
            'total_net' => 10,
            'total_tax' => 0.4,
            'total_gross' => 10.4,
            'total_purchase_cost_net' => 5,
            'gross_margin' => 5,
        ]);
        PartnerGoodsEntry::create([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'delivered_on' => today(),
            'quantity' => 2,
            'unit_purchase_price_net' => 2,
            'tax_percentage' => 4,
        ]);

        $page = app(BusinessReports::class);
        $page->month = now()->format('Y-m');
        $page->customerType = 'all';

        $this->assertSame(14.0, $page->summary()['revenue']);
        $this->assertSame(7.0, $page->summary()['costOfGoods']);
        $this->assertSame(7.0, $page->summary()['grossMargin']);

        $page->customerType = 'partners';
        $this->assertSame(4.0, $page->summary()['revenue']);
        $this->assertSame(2.0, $page->summary()['costOfGoods']);

        $page->customerType = CustomerType::Private->value;
        $this->assertSame(10.0, $page->summary()['revenue']);
        $this->assertSame(5.0, $page->summary()['costOfGoods']);
    }

    public function test_partner_delivery_document_creates_the_document_and_goods_entries_together(): void
    {
        [$partner, $product] = $this->partnerAndProduct();
        $admin = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
            'panel_role' => 'admin',
        ]);
        Company::create([
            'business_name' => 'Il Paradiso della Frutta di Castaldo Mariarosaria',
            'vat_number' => '02396610186',
            'active' => true,
        ]);
        PartnerProductPrice::query()
            ->whereBelongsTo($partner)
            ->whereBelongsTo($product)
            ->firstOrFail()
            ->update(['purchase_price_net' => 2]);

        $documents = app(PartnerDeliveryDocumentService::class);
        $document = $documents->create(
            $partner,
            $admin,
            [
                'issued_at' => now(),
                'payment_method_snapshot' => 'Contanti',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price_net' => 2,
                ]],
            ],
        );

        $entry = PartnerGoodsEntry::query()->firstOrFail();

        $this->assertInstanceOf(DeliveryDocument::class, $document);
        $this->assertNull($document->order_id);
        $this->assertSame($partner->id, $document->partner_id);
        $this->assertSame('Angela', $document->recipient_name);
        $this->assertSame('6.00', $document->total_net);
        $this->assertSame('0.24', $document->total_tax);
        $this->assertSame('6.24', $document->total_gross);
        $this->assertSame('Contanti', $document->payment_method_snapshot);
        $this->assertSame($document->id, $entry->delivery_document_id);
        $this->assertSame('6.24', $entry->total_gross);

        $updated = $documents->update($document, $admin, [
            'issued_at' => now()->addDay(),
            'payment_method_snapshot' => 'Bonifico',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'unit_price_net' => 2.5,
            ]],
            'notes' => 'Bolla aggiornata',
        ]);

        $this->assertSame(2, $updated->revision);
        $this->assertNotNull($updated->regenerated_at);
        $this->assertSame('10.00', $updated->total_net);
        $this->assertSame('10.40', $updated->total_gross);
        $this->assertSame('Bonifico', $updated->payment_method_snapshot);
        $this->assertDatabaseCount('partner_goods_entries', 1);
        $this->assertDatabaseHas('partner_goods_entries', [
            'delivery_document_id' => $document->id,
            'quantity' => 4,
            'total_gross' => 10.40,
        ]);

        $documents->delete($updated);

        $this->assertDatabaseMissing('delivery_documents', ['id' => $document->id]);
        $this->assertDatabaseMissing('partner_goods_entries', ['delivery_document_id' => $document->id]);
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
        $partner = Partner::create(['user_id' => $partnerUser->id, 'name' => 'Angela', 'active' => true]);
        $document = DeliveryDocument::create([
            'partner_id' => $partner->id,
            'document_number' => 'BC-2026-000001',
            'issued_at' => now(),
            'transport_reason' => 'Vendita',
            'transport_method' => 'Mittente',
            'sender_snapshot' => ['business_name' => 'Il Paradiso della Frutta'],
            'recipient_name' => 'Angela',
            'recipient_snapshot' => ['display_name' => 'Angela', 'type' => 'Partner'],
            'destination_snapshot' => [],
            'items_snapshot' => [],
            'total_net' => 10,
            'total_tax' => 0.4,
            'total_gross' => 10.4,
            'revision' => 1,
        ]);

        $this->actingAs($partnerUser, 'admin');

        $this->get('/partner')->assertRedirect('/partner/dashboard');
        $this->get('/partner/dashboard')->assertOk();
        $this->get('/partner/delivery-documents')->assertOk()->assertSee('BC-2026-000001');
        $this->get(route('partner.delivery-documents.show', $document))->assertOk();
        $this->get('/partner/daily-receipts')->assertNotFound();
        $this->get('/partner/daily-wastes')->assertNotFound();
        $this->get('/partner/expenses')->assertNotFound();
        $this->get('/partner/goods-entries')->assertNotFound();
        $this->get('/partner/product-prices')->assertNotFound();
        $this->get('/admin')->assertForbidden();
    }

    public function test_partner_cannot_download_another_partners_delivery_document(): void
    {
        $partnerUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
            'panel_role' => 'partner',
        ]);
        Partner::create(['user_id' => $partnerUser->id, 'name' => 'Angela', 'active' => true]);
        $otherPartner = Partner::create(['name' => 'Altro partner', 'active' => true]);
        $document = DeliveryDocument::create([
            'partner_id' => $otherPartner->id,
            'document_number' => 'BC-2026-000002',
            'issued_at' => now(),
            'transport_reason' => 'Vendita',
            'transport_method' => 'Mittente',
            'sender_snapshot' => ['business_name' => 'Il Paradiso della Frutta'],
            'recipient_name' => 'Altro partner',
            'recipient_snapshot' => ['display_name' => 'Altro partner', 'type' => 'Partner'],
            'destination_snapshot' => [],
            'items_snapshot' => [],
            'total_net' => 10,
            'total_tax' => 0.4,
            'total_gross' => 10.4,
            'revision' => 1,
        ]);

        $this->actingAs($partnerUser, 'admin')
            ->get(route('partner.delivery-documents.show', $document))
            ->assertForbidden();
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
