<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Filament\Pages\DailySummary;
use App\Models\Customer;
use App\Models\DailySummaryOverride;
use App\Models\Partner;
use App\Models\PartnerGoodsEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Documents\CreateDeliveryDocumentService;
use App\Services\Orders\CreateManualOrderService;
use App\Services\Reports\DailySummaryService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Richiesta del cliente: un unico elenco che somma tutti gli articoli di tutte le
 * bolle del giorno (clienti + partner), sommando solo le righe dello stesso
 * prodotto, per sapere in totale quanto preparare.
 */
class DailySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_matching_products_across_customer_and_partner_deliveries_of_the_same_day(): void
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

        $partner = Partner::create(['name' => 'Angela', 'active' => true]);
        PartnerGoodsEntry::create([
            'partner_id' => $partner->id,
            'product_id' => $tomato->id,
            'delivered_on' => now()->toDateString(),
            'quantity' => 5,
            'unit_purchase_price_net' => 1.8,
        ]);

        $rows = app(DailySummaryService::class)->build(Carbon::today(), true);

        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame('Pomodori', $row->name);
        $this->assertSame(15.0, $row->quantity);
        $this->assertSame(['Cliente', 'Partner'], $row->sources);

        // Deve mostrare quanto Antonio SPENDE (costo, 1€/kg), non quanto incassa
        // (prezzo cliente 2€/kg, prezzo partner 1,8€/kg): 10kg + 5kg a 1€ = 15€ netti.
        $this->assertSame(15.0, $row->net);
        $this->assertSame(0.6, round($row->tax, 2));
        $this->assertSame(15.6, round($row->gross, 2));

        // Escludendo i partner, deve restare solo la quantita' del cliente.
        $customerOnly = app(DailySummaryService::class)->build(Carbon::today(), false);
        $this->assertSame(10.0, $customerOnly->first()->quantity);

        // Un giorno diverso non deve avere nulla.
        $otherDay = app(DailySummaryService::class)->build(Carbon::yesterday(), true);
        $this->assertCount(0, $otherDay);
    }

    public function test_a_manual_override_replaces_the_computed_row_without_touching_real_data(): void
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
        $deliveryDocument = app(CreateDeliveryDocumentService::class)->create($order, $admin, ['issued_at' => now()]);
        $originalItem = $order->items()->first();

        DailySummaryOverride::create([
            'date' => now()->toDateString(),
            'product_id' => $tomato->id,
            'name' => 'Pomodori',
            'symbol' => 'kg',
            'quantity' => 999,
            'net' => 50,
            'tax' => 2,
            'gross' => 52,
            'created_by' => $admin->id,
        ]);

        $rows = app(DailySummaryService::class)->build(Carbon::today(), true);

        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertSame(999.0, $row->quantity);
        $this->assertSame(50.0, $row->net);
        $this->assertSame(['Modificato manualmente'], $row->sources);
        $this->assertTrue($row->is_manual);

        // Le bolle/ordini reali non devono essere toccati dalla correzione.
        $this->assertSame(10.0, (float) $originalItem->fresh()->quantity);
        $this->assertNotNull($deliveryDocument->fresh());
    }

    public function test_a_manual_override_can_add_a_product_not_present_in_any_delivery_that_day(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $category = ProductCategory::create(['name' => 'Verdura', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Cipolle',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);

        DailySummaryOverride::create([
            'date' => now()->toDateString(),
            'product_id' => $onion->id,
            'name' => 'Cipolle',
            'symbol' => 'kg',
            'quantity' => 20,
            'net' => 15,
            'tax' => 0.6,
            'gross' => 15.6,
            'created_by' => $admin->id,
        ]);

        $rows = app(DailySummaryService::class)->build(Carbon::today(), true);

        $this->assertCount(1, $rows);
        $this->assertSame('Cipolle', $rows->first()->name);
        $this->assertTrue($rows->first()->is_manual);
    }

    public function test_an_admin_can_create_and_remove_an_override_from_the_page(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();
        $this->actingAs($admin, 'admin');

        $category = ProductCategory::create(['name' => 'Verdura', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $onion = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Cipolle',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'active' => true,
        ]);

        Livewire::test(DailySummary::class)
            ->mountAction('addOverride')
            ->setActionData([
                'product_id' => $onion->id,
                'quantity' => 12,
                'net' => 10,
                'tax' => 0.4,
                'gross' => 10.4,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $override = DailySummaryOverride::query()->where('product_id', $onion->id)->firstOrFail();
        $this->assertSame(12.0, (float) $override->quantity);

        Livewire::test(DailySummary::class)
            ->mountAction('deleteOverride', ['overrideId' => $override->id])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(0, DailySummaryOverride::query()->count());
    }

    public function test_the_page_renders_for_an_admin(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(DailySummary::class)->assertOk();
    }

    public function test_the_pdf_download_action_opens_the_chosen_columns_only(): void
    {
        $this->seed([CompanySeeder::class, UserSeeder::class]);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin');

        Livewire::test(DailySummary::class)
            ->mountAction('downloadPdf')
            ->setActionData(['columns' => ['tax']])
            ->callMountedAction()
            ->assertHasNoActionErrors();
    }
}
