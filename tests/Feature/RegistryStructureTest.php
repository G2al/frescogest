<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Pages\BusinessReports;
use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\CostCategories\CostCategoryResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\TaxRates\TaxRateResource;
use App\Filament\Resources\UnitOfMeasures\UnitOfMeasureResource;
use App\Livewire\PendingOrdersPoller;
use App\Models\Company;
use App\Models\CostCategory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\TaxRateSeeder;
use Database\Seeders\UnitOfMeasureSeeder;
use Database\Seeders\UserSeeder;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class RegistryStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_the_verified_panel_administrator(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', 'admin@ilparadisodellafrutta.it')->firstOrFail();

        $this->assertSame('Amministratore Il Paradiso della Frutta', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->active);
        $this->assertTrue($admin->can_access_panel);
    }

    public function test_panel_access_depends_on_active_and_authorized_flags(): void
    {
        $adminPanel = Panel::make()->id('admin');
        $otherPanel = Panel::make()->id('staff');

        $authorizedUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
        ]);
        $inactiveUser = User::factory()->create([
            'active' => false,
            'can_access_panel' => true,
        ]);
        $unauthorizedUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => false,
        ]);

        $this->assertTrue($authorizedUser->canAccessPanel($adminPanel));
        $this->assertFalse($inactiveUser->canAccessPanel($adminPanel));
        $this->assertFalse($unauthorizedUser->canAccessPanel($adminPanel));
        $this->assertFalse($authorizedUser->canAccessPanel($otherPanel));

        $this->actingAs($authorizedUser, 'admin')->get('/admin')->assertOk();
        $this->actingAs($inactiveUser, 'admin')->get('/admin')->assertForbidden();
        $this->actingAs($unauthorizedUser, 'admin')->get('/admin')->assertForbidden();
    }

    public function test_order_navigation_badge_counts_only_whatsapp_orders(): void
    {
        $customer = Customer::factory()->create();

        foreach ([OrderStatus::WhatsAppPending, OrderStatus::WhatsAppPending, OrderStatus::Confirmed] as $index => $status) {
            Order::create([
                'order_number' => sprintf('IPF-%06d', $index + 1),
                'customer_id' => $customer->id,
                'status' => $status,
                'requested_at' => now(),
            ]);
        }

        $this->assertSame('2', OrderResource::getNavigationBadge());
        $this->assertSame('warning', OrderResource::getNavigationBadgeColor());
    }

    public function test_pending_order_poller_updates_without_reloading_the_panel(): void
    {
        $customer = Customer::factory()->create();
        $poller = Livewire::test(PendingOrdersPoller::class)->assertSet('count', 0);

        Order::create([
            'order_number' => 'IPF-000001',
            'customer_id' => $customer->id,
            'status' => OrderStatus::WhatsAppPending,
            'requested_at' => now(),
        ]);

        $poller->call('poll')->assertSet('count', 1);
    }

    public function test_reference_data_is_seeded_idempotently(): void
    {
        $seeders = [
            ProductCategorySeeder::class,
            TaxRateSeeder::class,
            UnitOfMeasureSeeder::class,
            PaymentMethodSeeder::class,
            ProductSeeder::class,
        ];

        $this->seed($seeders);
        $this->seed($seeders);

        $this->assertDatabaseCount('product_categories', 5);
        $this->assertDatabaseCount('tax_rates', 3);
        $this->assertDatabaseCount('unit_of_measures', 7);
        $this->assertDatabaseCount('payment_methods', 2);
        $this->assertDatabaseCount('products', 153);
        $this->assertDatabaseHas('payment_methods', ['name' => 'Contanti']);
        $this->assertDatabaseHas('payment_methods', ['name' => 'Bonifico']);
    }

    public function test_demo_seeder_creates_customers_and_real_linked_products(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('customers', 50);
        $this->assertDatabaseCount('products', 153);
        $this->assertDatabaseCount('customer_product_prices', 7650);
        $this->assertDatabaseHas('products', ['name' => 'Mozzarella di bufala campana']);
        $this->assertDatabaseHas('products', ['name' => 'Mele Fuji']);

        foreach ([
            'company_name',
            'first_name',
            'last_name',
            'vat_number',
            'tax_code',
            'email',
            'phone',
            'billing_address',
            'delivery_address',
            'city',
            'postal_code',
            'province',
            'notes',
        ] as $column) {
            $this->assertSame(0, Customer::query()->whereNull($column)->count());
        }

        $productsAreLinked = Product::query()
            ->with(['productCategory', 'taxRate', 'defaultUnitOfMeasure'])
            ->get()
            ->every(fn (Product $product): bool => $product->productCategory !== null
                && $product->taxRate !== null
                && $product->defaultUnitOfMeasure !== null);

        $this->assertTrue($productsAreLinked);
    }

    public function test_tax_rate_percentage_must_be_unique(): void
    {
        TaxRate::create([
            'name' => 'IVA ordinaria',
            'percentage' => 22,
            'active' => true,
        ]);

        $this->expectException(QueryException::class);

        TaxRate::create([
            'name' => 'IVA duplicata',
            'percentage' => 22,
            'active' => true,
        ]);
    }

    public function test_product_relations_are_configured(): void
    {
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Mele',
            'active' => true,
        ]);

        $this->assertTrue($product->productCategory->is($category));
        $this->assertTrue($product->taxRate->is($taxRate));
        $this->assertTrue($product->defaultUnitOfMeasure->is($unit));
        $this->assertTrue($category->products->contains($product));
        $this->assertTrue($taxRate->products->contains($product));
        $this->assertTrue($unit->products->contains($product));
    }

    public function test_customer_requires_a_company_or_a_complete_person_name(): void
    {
        $this->actingAs(User::factory()->create([
            'email' => 'admin@ilparadisodellafrutta.it',
            'email_verified_at' => now(),
            'can_access_panel' => true,
        ]), 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'company_name' => null,
                'first_name' => null,
                'last_name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['company_name', 'first_name', 'last_name']);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'company_name' => null,
                'first_name' => 'Mario',
                'last_name' => 'Rossi',
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
        ]);
    }

    public function test_all_filament_resource_pages_open(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@ilparadisodellafrutta.it')->firstOrFail(), 'admin');

        $category = ProductCategory::firstOrFail();
        $taxRate = TaxRate::firstOrFail();
        $unit = UnitOfMeasure::firstOrFail();
        $customer = Customer::create([
            'company_name' => 'Cliente ordine',
            'active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'IPF-000001',
            'customer_id' => $customer->id,
            'status' => 'whatsapp_pending',
            'requested_at' => now(),
        ]);

        foreach ([
            [CostCategoryResource::class, CostCategory::firstOrFail()],
            [CompanyResource::class, Company::create([
                'business_name' => 'Il Paradiso della Frutta',
                'vat_number' => '00000000000',
                'active' => true,
            ])],
            [CustomerResource::class, Customer::create([
                'company_name' => 'Cliente di prova',
                'active' => true,
            ])],
            [OrderResource::class, $order],
            [ProductCategoryResource::class, $category],
            [TaxRateResource::class, $taxRate],
            [UnitOfMeasureResource::class, $unit],
            [PaymentMethodResource::class, PaymentMethod::firstOrFail()],
            [ProductResource::class, Product::create([
                'product_category_id' => $category->id,
                'tax_rate_id' => $taxRate->id,
                'default_unit_of_measure_id' => $unit->id,
                'name' => 'Prodotto di prova',
                'active' => true,
            ])],
        ] as [$resource, $record]) {
            $this->get($resource::getUrl('index'))->assertOk();
            if ($resource !== CompanyResource::class) {
                $this->get($resource::getUrl('create'))->assertOk();
            }
            $this->get($resource::getUrl('edit', ['record' => $record]))->assertOk();
        }

        $this->get(BusinessReports::getUrl())->assertOk();
        $this->get('/admin')->assertOk();
    }
}
