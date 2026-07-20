<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_login_and_logout(): void
    {
        $payload = [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'MARIO@EXAMPLE.COM',
            'phone' => '+39 333 123 4567',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ];

        $this->postJson('/api/v1/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('data.email', 'mario@example.com')
            ->assertJsonPath('data.customer.phone', '+39 333 123 4567');

        $user = User::query()->where('email', 'mario@example.com')->firstOrFail();
        $this->assertTrue($user->active);
        $this->assertFalse($user->can_access_panel);
        $this->assertSame('393331234567', $user->customer->phone_normalized);

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->postJson('/api/v1/auth/login', ['email' => 'mario@example.com', 'password' => 'pass'])
            ->assertOk();
        $this->getJson('/api/v1/auth/user')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->getJson('/api/v1/auth/user')->assertUnauthorized();
    }

    public function test_registration_returns_conflict_for_customer_identity_without_linking_it(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'existing@example.com',
            'phone' => '+39 333 000 1111',
            'phone_normalized' => '393330001111',
            'user_id' => null,
        ]);

        $base = [
            'first_name' => 'Anna',
            'last_name' => 'Verdi',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->postJson('/api/v1/auth/register', $base + [
            'email' => 'existing@example.com',
            'phone' => '+39 320 111 2222',
        ])->assertConflict()
            ->assertJsonPath('errors.email.0', 'Questo indirizzo email è già associato a un cliente. Accedi oppure contatta l’assistenza.');

        $this->postJson('/api/v1/auth/register', $base + [
            'email' => 'other@example.com',
            'phone' => '+39 333 000 1111',
        ])->assertConflict()
            ->assertJsonPath('errors.phone.0', 'Questo numero di telefono è già associato a un cliente. Accedi oppure contatta l’assistenza.');

        $this->assertNull($customer->fresh()->user_id);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_authentication_validation_returns_clear_italian_messages(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Mario',
            'last_name' => 'Rossi',
            'email' => 'EXISTING@EXAMPLE.COM',
            'phone' => '+39 333 123 4567',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Esiste già un account con questo indirizzo email. Accedi oppure recupera la password.');

        $this->postJson('/api/v1/auth/register', [
            'email' => 'not-an-email',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.first_name.0', 'Inserisci il tuo nome.')
            ->assertJsonPath('errors.email.0', 'Inserisci un indirizzo email valido.')
            ->assertJsonPath('errors.phone.0', 'Inserisci il tuo numero di telefono.')
            ->assertJsonPath('errors.password.0', 'Inserisci una password.');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'existing@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Email o password non corretti.')
            ->assertJsonPath('errors.email.0', 'Controlla l’indirizzo email e la password inseriti.');
    }

    public function test_password_can_be_recovered_without_disclosing_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = null;

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'unknown@example.com'])->assertOk();
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new1',
            'password_confirmation' => 'new1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new1', $user->fresh()->password));
    }

    public function test_admin_and_customer_authentication_are_independent(): void
    {
        $admin = User::factory()->create([
            'active' => true,
            'can_access_panel' => true,
        ]);
        $customerUser = User::factory()->create([
            'active' => true,
            'can_access_panel' => false,
        ]);
        Customer::factory()->create(['user_id' => $customerUser->id]);

        $this->actingAs($admin, 'admin');
        $this->actingAs($customerUser, 'customer');

        $this->get('/admin')->assertOk();
        $this->getJson('/api/v1/auth/user')->assertOk()->assertJsonPath('data.id', $customerUser->id);
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($customerUser, 'customer');

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertGuest('customer');
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get('/admin')->assertOk();

        $this->actingAs($customerUser, 'customer');
        $this->post('/admin/logout')->assertRedirect();
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($customerUser, 'customer');
        $this->getJson('/api/v1/auth/user')->assertOk()->assertJsonPath('data.id', $customerUser->id);
    }
}
