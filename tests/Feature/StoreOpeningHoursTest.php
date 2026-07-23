<?php

namespace Tests\Feature;

use App\Enums\StoreClosureType;
use App\Models\StoreClosureSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'storefront.daily_closure.enabled' => true,
            'storefront.daily_closure.timezone' => 'Europe/Rome',
        ]);

        StoreClosureSchedule::query()->create([
            'name' => 'Aggiornamento quotidiano',
            'type' => StoreClosureType::Recurring,
            'weekdays' => array_keys(StoreClosureSchedule::weekdayOptions()),
            'starts_at' => '10:00',
            'ends_at' => '11:30',
            'active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_storefront_is_available_before_the_daily_closure(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-22 09:59:59', 'Europe/Rome'));

        $this->get('/')->assertRedirect('/index.html');
        $this->get('/index.html')->assertOk()->assertSee('The Cerino');
    }

    public function test_storefront_and_public_api_are_closed_during_the_configured_period(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-22 10:00:00', 'Europe/Rome'));

        $this->get('/')->assertServiceUnavailable();

        $this->get('/index.html')
            ->assertServiceUnavailable()
            ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private')
            ->assertSee('Il negozio è temporaneamente chiuso')
            ->assertSee('11:30')
            ->assertSee('/assets/images/cerino-logo-primary.png', false)
            ->assertDontSee('__REOPENING_AT__')
            ->assertDontSee('__SERVER_TIME__');

        $this->getJson('/api/v1/catalog/products')
            ->assertServiceUnavailable()
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonPath('message', 'Il negozio è temporaneamente chiuso per l’aggiornamento quotidiano di prezzi e disponibilità.');
    }

    public function test_store_status_and_admin_remain_available_during_the_daily_closure(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-22 10:45:00', 'Europe/Rome'));

        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonPath('data.reopens_at', '2026-07-22T11:30:00+02:00');

        $this->get('/admin/login')->assertOk();
    }

    public function test_storefront_reopens_at_the_exact_configured_time(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-22 11:30:00', 'Europe/Rome'));

        $this->get('/index.html')->assertOk()->assertSee('The Cerino');
        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.closes_at', '2026-07-23T10:00:00+02:00');
    }

    public function test_a_specific_date_closure_is_applied_without_changing_configuration(): void
    {
        StoreClosureSchedule::query()->where('type', StoreClosureType::Recurring)->update(['active' => false]);
        StoreClosureSchedule::query()->create([
            'name' => 'Chiusura straordinaria',
            'type' => StoreClosureType::SpecificDate,
            'closure_date' => '2026-07-24',
            'starts_at' => '15:00',
            'ends_at' => '16:00',
            'message' => 'Chiusura straordinaria del negozio.',
            'active' => true,
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-24 15:30:00', 'Europe/Rome'));

        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonPath('data.reopens_at', '2026-07-24T16:00:00+02:00')
            ->assertJsonPath('data.message', 'Chiusura straordinaria del negozio.');
    }

    public function test_a_disabled_schedule_does_not_close_the_storefront(): void
    {
        StoreClosureSchedule::query()->update(['active' => false]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-22 10:30:00', 'Europe/Rome'));

        $this->get('/index.html')->assertOk();
        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.closes_at', null);
    }

    public function test_a_recurring_closure_can_continue_after_midnight(): void
    {
        StoreClosureSchedule::query()->delete();
        StoreClosureSchedule::query()->create([
            'name' => 'Chiusura notturna',
            'type' => StoreClosureType::Recurring,
            'weekdays' => [3],
            'starts_at' => '23:30',
            'ends_at' => '01:00',
            'active' => true,
        ]);
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-23 00:30:00', 'Europe/Rome'));

        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', true)
            ->assertJsonPath('data.reopens_at', '2026-07-23T01:00:00+02:00');
    }
}
