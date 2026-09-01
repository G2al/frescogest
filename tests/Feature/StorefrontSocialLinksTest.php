<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorefrontSocialLinksTest extends TestCase
{
    public function test_shared_footer_contains_the_official_social_profiles(): void
    {
        $layout = file_get_contents(public_path('assets/js/ui.js'));

        $this->assertStringContainsString('https://www.instagram.com/il.paradiso.della.frutta/', $layout);
        $this->assertStringContainsString('https://www.tiktok.com/@il.paradiso.della.frutta', $layout);
        $this->assertStringContainsString('aria-label="Seguici su Instagram"', $layout);
        $this->assertStringContainsString('aria-label="Seguici su TikTok"', $layout);
        $this->assertStringContainsString('aria-label="Apri Instagram"', $layout);
        $this->assertStringContainsString('aria-label="Apri TikTok"', $layout);
        $this->assertSame(2, substr_count($layout, 'https://www.instagram.com/il.paradiso.della.frutta/'));
        $this->assertSame(2, substr_count($layout, 'https://www.tiktok.com/@il.paradiso.della.frutta'));
        $this->assertSame(4, substr_count($layout, 'rel="noopener noreferrer"'));
    }

    public function test_qr_code_page_uses_the_official_social_profiles(): void
    {
        $page = file_get_contents(public_path('qrcode.html'));

        $this->assertStringContainsString('href="https://www.instagram.com/il.paradiso.della.frutta/"', $page);
        $this->assertStringContainsString('href="https://www.tiktok.com/@il.paradiso.della.frutta"', $page);
        $this->assertStringContainsString('instagram: "https://www.instagram.com/il.paradiso.della.frutta/"', $page);
        $this->assertStringContainsString('tiktok: "https://www.tiktok.com/@il.paradiso.della.frutta"', $page);
    }
}
