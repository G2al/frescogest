<?php

namespace Tests\Feature;

use App\Enums\CustomerType;
use App\Enums\SpecialPriceAudience;
use App\Enums\SpecialPriceScope;
use App\Filament\Resources\SpecialPriceRules\SpecialPriceRuleResource;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\PartnerProductPrice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SpecialPriceRule;
use App\Models\TaxRate;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Services\Partners\PartnerDeliveryDocumentPricingService;
use App\Services\Pricing\ProductPricingService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialPriceRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_have_independent_default_prices_for_each_audience(): void
    {
        $product = $this->product();

        $this->assertSame('75.00', $product->markup_percentage);
        $this->assertSame('1.7500', $product->base_price_per_unit);
        $this->assertSame('45.00', $product->restaurant_markup_percentage);
        $this->assertSame('1.4500', $product->restaurant_price_per_unit);
        $this->assertSame('35.00', $product->partner_markup_percentage);
        $this->assertSame('1.3500', $product->partner_price_per_unit);
    }

    public function test_product_rule_overrides_category_rule_for_the_selected_audience(): void
    {
        $product = $this->product();
        $restaurant = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        SpecialPriceRule::create([
            'name' => 'Frutta ristoratori',
            'audience' => SpecialPriceAudience::Restaurants,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'markup_percentage' => 80,
            'active' => true,
        ]);

        $categoryPrice = app(ProductPricingService::class)->details($product->fresh(), $restaurant);

        $this->assertSame('1.80', $categoryPrice['price']);
        $this->assertSame('special_category', $categoryPrice['source']);

        SpecialPriceRule::create([
            'name' => 'Ciliegino ristoratori',
            'audience' => SpecialPriceAudience::Restaurants,
            'scope_type' => SpecialPriceScope::Product,
            'product_id' => $product->id,
            'markup_percentage' => 90,
            'active' => true,
        ]);

        $productPrice = app(ProductPricingService::class)->details($product->fresh(), $restaurant);

        $this->assertSame('1.90', $productPrice['price']);
        $this->assertSame('special_product', $productPrice['source']);
    }

    public function test_explicit_customer_price_keeps_priority_over_special_rules(): void
    {
        $product = $this->product();
        $customer = Customer::factory()->create(['type' => CustomerType::Private]);

        SpecialPriceRule::create([
            'name' => 'Frutta privati',
            'audience' => SpecialPriceAudience::PrivateCustomers,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'markup_percentage' => 80,
            'active' => true,
        ]);
        $customer->productPrices()->whereBelongsTo($product)->update(['custom_price_per_unit' => 2.25]);

        $details = app(ProductPricingService::class)->details($product->fresh(), $customer->fresh());

        $this->assertSame('2.25', $details['price']);
        $this->assertSame('product', $details['source']);
    }

    public function test_partner_rules_update_automatic_prices_but_preserve_manual_overrides(): void
    {
        $product = $this->product();
        $partner = Partner::create(['name' => 'Angela', 'active' => true]);
        $price = PartnerProductPrice::query()->whereBelongsTo($partner)->whereBelongsTo($product)->firstOrFail();

        $this->assertSame('1.3500', $price->purchase_price_net);
        $this->assertFalse($price->purchase_price_is_custom);

        SpecialPriceRule::create([
            'name' => 'Frutta Angela',
            'audience' => SpecialPriceAudience::Partners,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'partner_id' => $partner->id,
            'markup_percentage' => 40,
            'active' => true,
        ]);

        $this->assertSame('1.4000', $price->fresh()->purchase_price_net);
        $this->assertSame('1.4000', app(PartnerDeliveryDocumentPricingService::class)->product($partner, $product->id)['price']);

        $price->fresh()->update(['purchase_price_net' => 2.10]);

        SpecialPriceRule::create([
            'name' => 'Ciliegino Angela',
            'audience' => SpecialPriceAudience::Partners,
            'scope_type' => SpecialPriceScope::Product,
            'product_id' => $product->id,
            'partner_id' => $partner->id,
            'markup_percentage' => 60,
            'active' => true,
        ]);

        $this->assertTrue($price->fresh()->purchase_price_is_custom);
        $this->assertSame('2.1000', $price->fresh()->purchase_price_net);
        $this->assertSame('2.1000', app(PartnerDeliveryDocumentPricingService::class)->product($partner, $product->id)['price']);
    }

    public function test_admin_can_open_special_price_rule_resource(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(SpecialPriceRuleResource::getUrl('index'))
            ->assertOk();
    }

    private function product(): Product
    {
        $category = ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::create(['name' => 'IVA 4%', 'percentage' => 4, 'active' => true]);
        $unit = UnitOfMeasure::create(['name' => 'Chilogrammi', 'symbol' => 'kg', 'active' => true]);

        return Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => 'Ciliegino',
            'purchase_cost_per_unit' => 1,
            'markup_percentage' => 75,
            'restaurant_markup_percentage' => 45,
            'partner_markup_percentage' => 35,
            'base_minimum_quantity' => 1,
            'restaurant_minimum_quantity' => 5,
            'active' => true,
        ]);
    }
}
