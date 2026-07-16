<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_read_and_update_profile_with_normalized_identity(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com', 'active' => true]);
        Customer::factory()->create(['user_id' => $user->id, 'email' => 'old@example.com']);

        $this->actingAs($user, 'customer')->patchJson('/api/v1/profile', [
            'first_name' => 'Anna', 'last_name' => 'Verdi', 'email' => 'ANNA@EXAMPLE.COM',
            'phone' => '+39 333 555 7788', 'province' => 'na',
        ])->assertOk()->assertJsonPath('data.email', 'anna@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'anna@example.com']);
        $this->assertDatabaseHas('customers', ['user_id' => $user->id, 'phone_normalized' => '393335557788', 'province' => 'NA']);
    }

    public function test_inactive_or_customerless_user_cannot_use_customer_api(): void
    {
        $inactive = User::factory()->create(['active' => false]);
        Customer::factory()->create(['user_id' => $inactive->id]);
        $this->actingAs($inactive, 'customer')->getJson('/api/v1/profile')->assertForbidden();

        $customerless = User::factory()->create(['active' => true]);
        $this->actingAs($customerless, 'customer')->getJson('/api/v1/profile')->assertForbidden();
    }
}
