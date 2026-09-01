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
        $this->assertStringContainsString("document.body.classList.toggle('wine-catalog-active', showWineNotice)", $script);
    }

    public function test_wine_images_use_the_dedicated_portrait_layout(): void
    {
        $catalog = file_get_contents(public_path('assets/js/catalog.js'));
        $product = file_get_contents(public_path('assets/js/product.js'));
        $ui = file_get_contents(public_path('assets/js/ui.js'));
        $styles = file_get_contents(public_path('assets/css/app.css'));

        $this->assertStringContainsString("isWineCategory(product.category) ? ' wine-product'", $catalog);
        $this->assertStringContainsString("? ' wine-product' : ''", $product);
        $this->assertStringContainsString('const wineClass = isWine', $ui);
        $this->assertStringContainsString('.product-media.wine-product > img:not(.quality-seal)', $styles);
        $this->assertStringContainsString('.catalog-page .catalog-grid .product-media.wine-product { height: auto; aspect-ratio: 4 / 5;', $styles);
        $this->assertStringContainsString('aspect-ratio: 3 / 5', $styles);
        $this->assertStringContainsString('object-fit: contain', $styles);
        $this->assertStringContainsString('.wine-catalog-active .catalog-grid { grid-template-columns: repeat(3', $styles);
    }
}
