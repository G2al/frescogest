<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontWineNoticeTest extends TestCase
{
    public function test_catalog_contains_the_wine_advance_order_notice(): void
    {
        $catalog = file_get_contents(public_path('index.html'));
        $script = file_get_contents(public_path('assets/js/catalog.js'));

        $this->assertStringContainsString('id="wine-order-notice"', $catalog);
        $this->assertStringContainsString('Ordina i vini con almeno 2 giorni di anticipo', $catalog);
        $this->assertStringContainsString('Per riceverli il 5 settembre, invia l’ordine entro il 3 settembre.', $catalog);
        $this->assertStringContainsString('function isWineCategory(category)', $script);
        $this->assertStringContainsString("wineNoticeRoot?.classList.toggle('hidden', !showWineNotice)", $script);
    }
}
