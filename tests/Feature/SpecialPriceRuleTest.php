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
use App\Services\Pricing\SpecialPriceRuleApplier;
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
        $this->assertSame('1.75', $product->base_price_per_unit);
        $this->assertSame('45.00', $product->restaurant_markup_percentage);
        $this->assertSame('1.45', $product->restaurant_price_per_unit);
        $this->assertSame('35.00', $product->partner_markup_percentage);
        $this->assertSame('1.35', $product->partner_price_per_unit);
    }

    public function test_applying_a_rule_writes_the_markup_directly_on_scoped_products_only(): void
    {
        $product = $this->product();
        $otherCategory = ProductCategory::create(['name' => 'Verdura', 'active' => true]);
        $otherProduct = $this->product($otherCategory, 'Zucchina');
        $restaurant = Customer::factory()->create(['type' => CustomerType::Restaurant]);

        $categoryRule = SpecialPriceRule::create([
            'name' => 'Frutta ristoratori',
            'audience' => SpecialPriceAudience::Restaurants,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'markup_percentage' => 80,
            'active' => true,
        ]);

        // La regola esiste ma non ha ancora effetto finché non viene applicata.
        $this->assertSame('45.00', $product->fresh()->restaurant_markup_percentage);

        $affected = app(SpecialPriceRuleApplier::class)->apply($categoryRule);

        $this->assertSame(1, $affected);
        $this->assertSame('80.00', $product->fresh()->restaurant_markup_percentage);
        $this->assertSame('1.80', $product->fresh()->restaurant_price_per_unit);
        // La categoria "Verdura" non è nell'ambito della regola: resta invariata.
        $this->assertSame('45.00', $otherProduct->fresh()->restaurant_markup_percentage);

        $categoryPrice = app(ProductPricingService::class)->details($product->fresh(), $restaurant);
        $this->assertSame('1.80', $categoryPrice['price']);

        $productRule = SpecialPriceRule::create([
            'name' => 'Ciliegino ristoratori',
            'audience' => SpecialPriceAudience::Restaurants,
            'scope_type' => SpecialPriceScope::Product,
            'product_id' => $product->id,
            'markup_percentage' => 90,
            'active' => true,
        ]);
        app(SpecialPriceRuleApplier::class)->apply($productRule);

        $productPrice = app(ProductPricingService::class)->details($product->fresh(), $restaurant);
        $this->assertSame('1.90', $productPrice['price']);
    }

    public function test_manual_product_edit_after_applying_a_rule_is_not_overwritten_again(): void
    {
        $product = $this->product();

        $rule = SpecialPriceRule::create([
            'name' => 'Frutta privati',
            'audience' => SpecialPriceAudience::PrivateCustomers,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'markup_percentage' => 80,
            'active' => true,
        ]);
        app(SpecialPriceRuleApplier::class)->apply($rule);
        $this->assertSame('80.00', $product->fresh()->markup_percentage);

        // L'amministratore personalizza a mano il singolo prodotto: nessuna "magia" lo riscrive.
        $product->fresh()->update(['markup_percentage' => 120]);
        $this->assertSame('120.00', $product->fresh()->markup_percentage);
    }

    public function test_explicit_customer_price_keeps_priority_over_the_products_list_price(): void
    {
        $product = $this->product();
        $customer = Customer::factory()->create(['type' => CustomerType::Private]);
        $customer->productPrices()->whereBelongsTo($product)->update(['custom_price_per_unit' => 2.25]);

        $details = app(ProductPricingService::class)->details($product->fresh(), $customer->fresh());

        $this->assertSame('2.25', $details['price']);
        $this->assertSame('product', $details['source']);
    }

    public function test_applying_a_global_rule_updates_every_active_product(): void
    {
        $product = $this->product();
        $otherCategory = ProductCategory::create(['name' => 'Verdura', 'active' => true]);
        $otherProduct = $this->product($otherCategory, 'Zucchina');

        $rule = SpecialPriceRule::create([
            'name' => 'Ricarico base privati',
            'audience' => SpecialPriceAudience::PrivateCustomers,
            'scope_type' => SpecialPriceScope::Global,
            'markup_percentage' => 80,
            'active' => true,
        ]);

        $affected = app(SpecialPriceRuleApplier::class)->apply($rule);

        $this->assertSame(2, $affected);
        $this->assertSame('80.00', $product->fresh()->markup_percentage);
        $this->assertSame('80.00', $otherProduct->fresh()->markup_percentage);
    }

    public function test_applying_a_partner_specific_rule_updates_only_that_partners_price_list(): void
    {
        $product = $this->product();
        $partner = Partner::create(['name' => 'Angela', 'active' => true]);
        $price = PartnerProductPrice::query()->whereBelongsTo($partner)->whereBelongsTo($product)->firstOrFail();

        $this->assertSame('1.3500', $price->purchase_price_net);
        $this->assertFalse($price->purchase_price_is_custom);

        $rule = SpecialPriceRule::create([
            'name' => 'Frutta Angela',
            'audience' => SpecialPriceAudience::Partners,
            'scope_type' => SpecialPriceScope::Category,
            'product_category_id' => $product->product_category_id,
            'partner_id' => $partner->id,
            'markup_percentage' => 40,
            'active' => true,
        ]);
        app(SpecialPriceRuleApplier::class)->apply($rule);

        $this->assertTrue($price->fresh()->purchase_price_is_custom);
        $this->assertSame('1.4000', $price->fresh()->purchase_price_net);
        $this->assertSame('1.4000', app(PartnerDeliveryDocumentPricingService::class)->product($partner, $product->id)['price']);

        // Il prodotto in sé (listino di default) non viene toccato: la regola era per un partner specifico.
        $this->assertSame('35.00', $product->fresh()->partner_markup_percentage);
    }

    public function test_admin_can_open_special_price_rule_resource(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::query()->where('panel_role', 'admin')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->get(SpecialPriceRuleResource::getUrl('index'))
            ->assertOk();
    }

    private function product(?ProductCategory $category = null, string $name = 'Ciliegino'): Product
    {
        $category ??= ProductCategory::create(['name' => 'Frutta', 'active' => true]);
        $taxRate = TaxRate::query()->firstOrCreate(['percentage' => 4], ['name' => 'IVA 4%', 'active' => true]);
        $unit = UnitOfMeasure::query()->firstOrCreate(['symbol' => 'kg'], ['name' => 'Chilogrammi', 'active' => true]);

        return Product::create([
            'product_category_id' => $category->id,
            'tax_rate_id' => $taxRate->id,
            'default_unit_of_measure_id' => $unit->id,
            'name' => $name,
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
