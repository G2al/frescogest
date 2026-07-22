<?php

namespace Tests\Feature;

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
            'storefront.daily_closure.starts_at' => '10:00',
            'storefront.daily_closure.ends_at' => '11:30',
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
        $this->get('/index.html')->assertOk()->assertSee('Catalogo prodotti');
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
            ->assertSee('/assets/images/ilparadisodellafrutta-logo-primary.png', false)
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

        $this->get('/index.html')->assertOk()->assertSee('Catalogo prodotti');
        $this->getJson('/api/v1/store/status')
            ->assertOk()
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.closes_at', '2026-07-23T10:00:00+02:00');
    }
}
