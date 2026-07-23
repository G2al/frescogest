<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Customers\DeleteCustomerService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UserSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistryStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_cerino_panel_administrator(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::query()->where('email', 'admin@cerinostore.it')->firstOrFail();

        $this->assertSame('Amministratore Cerino Store', $admin->name);
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->active);
        $this->assertTrue($admin->can_access_panel);
    }

    public function test_panel_access_uses_explicit_flags(): void
    {
        $adminPanel = Panel::make()->id('admin');
        $otherPanel = Panel::make()->id('staff');
        $user = User::factory()->create(['active' => true, 'can_access_panel' => true]);

        $this->assertTrue($user->canAccessPanel($adminPanel));
        $this->assertFalse($user->canAccessPanel($otherPanel));
        $this->assertFalse($user->forceFill(['active' => false])->canAccessPanel($adminPanel));
    }

    public function test_seeded_filament_catalog_pages_open(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@cerinostore.it')->firstOrFail();
        $this->actingAs($admin, 'admin');
        $product = Product::query()->firstOrFail();

        $this->get('/admin')->assertOk();
        $this->get(ProductResource::getUrl('index'))->assertOk();
        $this->get(ProductResource::getUrl('edit', ['record' => $product]))->assertOk();
        $this->get(CustomerResource::getUrl('index'))->assertOk();
    }

    public function test_deleting_a_customer_permanently_removes_account_and_orders(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['user_id' => $user->id]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'status' => 'whatsapp_pending',
            'requested_at' => now(),
        ]);

        app(DeleteCustomerService::class)->delete($customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }
}
