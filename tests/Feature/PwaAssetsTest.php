<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    public function test_storefront_manifest_has_installable_icons_and_scope(): void
    {
        $manifest = $this->manifest('manifest.webmanifest');

        $this->assertSame('/', $manifest['id']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#ffffff', $manifest['background_color']);
        $this->assertIconSetExists($manifest['icons']);
    }

    public function test_admin_manifest_is_separate_and_uses_admin_scope(): void
    {
        $manifest = $this->manifest('admin-manifest.webmanifest');

        $this->assertSame('/admin/', $manifest['id']);
        $this->assertSame('/admin', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#18181b', $manifest['background_color']);
        $this->assertIconSetExists($manifest['icons']);
    }

    public function test_storefront_pages_load_the_manifest_and_registration_script(): void
    {
        $pages = [
            'index.html',
            'cart.html',
            'login.html',
            'register.html',
            'orders.html',
            'product.html',
            'profile.html',
            'forgot-password.html',
            'reset-password.html',
            'qrcode.html',
            'whatsapp.html',
        ];

        foreach ($pages as $page) {
            $content = file_get_contents(public_path($page));

            $this->assertStringContainsString('/manifest.webmanifest', $content, $page);
            $this->assertStringContainsString('/assets/js/pwa.js', $content, $page);
            $this->assertStringContainsString('user-scalable=no', $content, $page);
        }
    }

    public function test_service_workers_and_offline_pages_exist(): void
    {
        foreach ([
            'service-worker.js',
            'admin-service-worker.js',
            'offline.html',
            'admin-offline.html',
        ] as $file) {
            $this->assertFileExists(public_path($file));
        }
    }

    public function test_admin_pwa_respects_mobile_safe_areas(): void
    {
        $head = file_get_contents(resource_path('views/filament/admin-pwa.blade.php'));
        $script = file_get_contents(public_path('assets/js/admin-pwa.js'));

        $this->assertStringContainsString('env(safe-area-inset-top, 0px)', $head);
        $this->assertStringContainsString('env(safe-area-inset-bottom, 0px)', $head);
        $this->assertStringContainsString('fi-admin-pwa-standalone', $script);
        $this->assertStringContainsString("window.navigator.standalone === true", $script);
    }

    private function manifest(string $file): array
    {
        $manifest = json_decode(file_get_contents(public_path($file)), true, flags: JSON_THROW_ON_ERROR);

        $this->assertIsArray($manifest);

        return $manifest;
    }

    private function assertIconSetExists(array $icons): void
    {
        $this->assertCount(4, $icons);

        foreach ($icons as $icon) {
            $path = parse_url($icon['src'], PHP_URL_PATH);

            $this->assertFileExists(public_path(ltrim($path, '/')));
        }
    }
}
