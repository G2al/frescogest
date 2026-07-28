<?php

namespace Tests\Feature\Api;

use App\Enums\CustomerType;
use App\Enums\PromotionAudience;
use App\Enums\PromotionRule;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PromotionCode;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_featured_promotion_is_exposed_to_the_sticker(): void
    {
        $promotion = PromotionCode::query()->create([
            'name' => 'Sconto dello sticker',
            'code' => 'STICKER15',
            'discount_percentage' => 15,
            'audience' => PromotionAudience::Everyone,
            'rule' => PromotionRule::FirstOrder,
            'single_use_per_customer' => true,
            'active' => true,
            'featured_on_sticker' => true,
        ]);

        $this->getJson('/api/v1/promotions/sticker')
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertJsonPath('data.code', $promotion->code)
            ->assertJsonPath('data.name', $promotion->name)
            ->assertJsonPath('data.discount_percentage', '15.00');
    }

    public function test_sticker_hides_inactive_expired_or_deleted_promotions(): void
    {
        $promotion = PromotionCode::query()->create([
            'name' => 'Promozione scaduta',
            'code' => 'SCADUTA10',
            'discount_percentage' => 10,
            'audience' => PromotionAudience::Everyone,
            'rule' => PromotionRule::AnyOrder,
            'ends_at' => now()->subMinute(),
            'single_use_per_customer' => true,
            'active' => true,
            'featured_on_sticker' => true,
        ]);

        $this->getJson('/api/v1/promotions/sticker')
            ->assertOk()
            ->assertJsonPath('data', null);

        $promotion->update([
            'ends_at' => null,
            'active' => false,
        ]);

        $this->getJson('/api/v1/promotions/sticker')
            ->assertOk()
            ->assertJsonPath('data', null);

        $promotion->delete();

        $this->getJson('/api/v1/promotions/sticker')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_only_one_promotion_can_be_featured_on_the_sticker(): void
    {
        $first = PromotionCode::query()->create([
            'name' => 'Prima',
            'code' => 'PRIMA10',
            'discount_percentage' => 10,
            'audience' => PromotionAudience::Everyone,
            'rule' => PromotionRule::AnyOrder,
            'single_use_per_customer' => true,
            'active' => true,
            'featured_on_sticker' => true,
        ]);
        $second = PromotionCode::query()->create([
            'name' => 'Seconda',
            'code' => 'SECONDA20',
            'discount_percentage' => 20,
            'audience' => PromotionAudience::Everyone,
            'rule' => PromotionRule::AnyOrder,
            'single_use_per_customer' => true,
            'active' => true,
            'featured_on_sticker' => true,
        ]);

        $this->assertFalse($first->fresh()->featured_on_sticker);
        $this->assertTrue($second->fresh()->featured_on_sticker);
    }

    public function test_first_order_code_is_validated_applied_and_recorded_once(): void
    {
        [$user, $product] = $this->customerAndProduct();
        $promotion = PromotionCode::query()->create([
            'name' => 'Benvenuto',
            'code' => 'welcome10',
            'discount_percentage' => 10,
            'audience' => PromotionAudience::Everyone,
            'rule' => PromotionRule::FirstOrder,
            'single_use_per_customer' => true,
            'active' => true,
        ]);

        $this->actingAs($user, 'customer')
            ->postJson('/api/v1/promotions/validate', ['code' => ' welcome10 '])
            ->assertOk()
            ->assertJsonPath('data.code', 'WELCOME10')
            ->assertJsonPath('data.discount_percentage', '10.00');

        $response = $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'promotion_code' => 'welcome10',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        $orderId = $response->json('data.order.id');
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'promotion_code_id' => $promotion->id,
            'promotion_code_snapshot' => 'WELCOME10',
            'promotion_discount_percentage' => 10,
            'discount_percentage' => 10,
            'subtotal_net' => 8.40,
            'discount_amount_net' => 0.84,
            'total_net' => 7.56,
        ]);
        $this->assertDatabaseHas('promotion_code_usages', [
            'promotion_code_id' => $promotion->id,
            'customer_id' => $user->customer->id,
            'order_id' => $orderId,
        ]);

        $this->actingAs($user, 'customer')->postJson('/api/v1/orders', [
            'promotion_code' => 'WELCOME10',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('promotion_code');
    }

    public function test_code_respects_audience_state_and_validity_period(): void
    {
        [$user] = $this->customerAndProduct();

        PromotionCode::query()->create([
            'name' => 'Solo ristoratori',
            'code' => 'RISTO20',
            'discount_percentage' => 20,
            'audience' => PromotionAudience::Restaurants,
            'rule' => PromotionRule::AnyOrder,
            'single_use_per_customer' => true,
            'active' => true,
        ]);

        $this->actingAs($user, 'customer')
            ->postJson('/api/v1/promotions/validate', ['code' => 'RISTO20'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('promotion_code');

        $user->customer->update(['type' => CustomerType::Restaurant]);

        $this->actingAs($user, 'customer')
            ->postJson('/api/v1/promotions/validate', ['code' => 'RISTO20'])
            ->assertOk();

        PromotionCode::query()->where('code', 'RISTO20')->update(['ends_at' => now()->subMinute()]);

        $this->actingAs($user, 'customer')
            ->postJson('/api/v1/promotions/validate', ['code' => 'RISTO20'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('promotion_code');
    }

    private function customerAndProduct(): array
    {
        $user = User::factory()->create(['active' => true]);
        Customer::factory()->create([
            'user_id' => $user->id,
            'type' => CustomerType::Private,
        ]);
        $category = ProductCategory::query()->create([
            'name' => 'Verdura',
            'slug' => 'verdura',
            'active' => true,
            'is_public' => true,
        ]);
        $taxRate = TaxRate::query()->create([
            'name' => 'IVA 4%',
            'percentage' => 4,
            'active' => true,
        ]);
        $unit = UnitOfMeasure::query()->create([
            'name' => 'Chilogrammi',
            'symbol' => 'kg',
            'active' => true,
        ]);
        $product = Product::query()->create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Pomodori',
            'slug' => 'pomodori',
            'purchase_cost_per_unit' => 2.10,
            'markup_percentage' => 100,
            'active' => true,
        ]);

        return [$user->load('customer'), $product];
    }
}
