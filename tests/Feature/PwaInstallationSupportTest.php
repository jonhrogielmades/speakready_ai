<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class PwaInstallationSupportTest extends TestCase
{
    private const LEGACY_APP_ID = '/?sr_layout=mobile&sr_is_mobile=1&sr_pwa=1';

    private const FULL_WEB_SHELLS = [
        'resources/views/desktop/admin/mobile-locked.blade.php',
        'resources/views/desktop/layouts/admin.blade.php',
        'resources/views/desktop/layouts/app.blade.php',
        'resources/views/desktop/layouts/auth.blade.php',
        'resources/views/desktop/layouts/guest.blade.php',
        'resources/views/desktop/terms/accept.blade.php',
        'resources/views/errors/page.blade.php',
        'resources/views/legal/show.blade.php',
        'resources/views/mobile/admin/mobile-locked.blade.php',
        'resources/views/mobile/layouts/admin.blade.php',
        'resources/views/mobile/layouts/app.blade.php',
        'resources/views/mobile/layouts/auth.blade.php',
        'resources/views/mobile/layouts/guest.blade.php',
        'resources/views/mobile/terms/accept.blade.php',
    ];

    private const BLADE_LAYOUTS = [
        'resources/views/desktop/layouts/admin.blade.php',
        'resources/views/desktop/layouts/app.blade.php',
        'resources/views/desktop/layouts/auth.blade.php',
        'resources/views/desktop/layouts/guest.blade.php',
        'resources/views/mobile/layouts/admin.blade.php',
        'resources/views/mobile/layouts/app.blade.php',
        'resources/views/mobile/layouts/auth.blade.php',
        'resources/views/mobile/layouts/guest.blade.php',
    ];

    public function test_manifest_keeps_a_neutral_start_url_and_the_stable_legacy_identity(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('/?sr_pwa=1', $manifest['start_url'] ?? null);
        $this->assertSame(self::LEGACY_APP_ID, $manifest['id'] ?? null);
    }

    public function test_manifest_declares_the_required_scope_display_and_app_icon_purposes(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('/', $manifest['scope'] ?? null);
        $this->assertSame('standalone', $manifest['display'] ?? null);
        $this->assertSame('#f1f6fb', $manifest['background_color'] ?? null);
        $this->assertSame('#f1f6fb', $manifest['theme_color'] ?? null);

        $expectedIcons = [
            ['192x192', 'any', '/img/app-icon-192.png'],
            ['512x512', 'any', '/img/app-icon-512.png'],
            ['192x192', 'maskable', '/img/app-icon-maskable-192.png'],
            ['512x512', 'maskable', '/img/app-icon-maskable-512.png'],
        ];

        foreach ($expectedIcons as [$sizes, $purpose, $source]) {
            $icon = $this->findManifestIcon($manifest, $sizes, $purpose);

            $this->assertSame($source, $icon['src'] ?? null, "Unexpected {$purpose} icon for {$sizes}.");
            $this->assertSame('image/png', $icon['type'] ?? null, "The {$purpose} {$sizes} icon must be PNG.");
        }
    }

    public function test_every_manifest_icon_exists_and_matches_its_declared_dimensions(): void
    {
        $manifest = $this->manifest();
        $icons = $manifest['icons'] ?? null;

        $this->assertIsArray($icons);
        $this->assertNotEmpty($icons);

        foreach ($icons as $icon) {
            $source = $icon['src'] ?? null;
            $declaredSizes = $icon['sizes'] ?? null;

            $this->assertIsString($source);
            $this->assertStringStartsWith('/', $source);
            $this->assertIsString($declaredSizes);
            $this->assertMatchesRegularExpression('/^(\d+)x(\d+)$/', $declaredSizes);

            preg_match('/^(\d+)x(\d+)$/', $declaredSizes, $matches);
            $path = $this->projectPath('public/'.ltrim((string) parse_url($source, PHP_URL_PATH), '/'));

            $this->assertFileExists($path, "Manifest icon {$source} does not exist.");

            $actualSize = getimagesize($path);
            $this->assertNotFalse($actualSize, "Manifest icon {$source} is not a readable image.");
            $this->assertSame((int) $matches[1], $actualSize[0], "Manifest icon {$source} has the wrong width.");
            $this->assertSame((int) $matches[2], $actualSize[1], "Manifest icon {$source} has the wrong height.");
        }
    }

    public function test_install_icons_keep_a_single_logo_layer_with_transparent_outer_corners(): void
    {
        $icons = [
            'public/img/app-icon-192.png' => [192, 192],
            'public/img/app-icon-512.png' => [512, 512],
            'public/img/app-icon-maskable-192.png' => [192, 192],
            'public/img/app-icon-maskable-512.png' => [512, 512],
            'public/img/apple-touch-icon.png' => [180, 180],
        ];

        foreach ($icons as $relativePath => [$expectedWidth, $expectedHeight]) {
            $path = $this->projectPath($relativePath);

            $this->assertFileExists($path);

            $size = getimagesize($path);
            $this->assertNotFalse($size, "{$relativePath} is not a readable image.");
            $this->assertSame($expectedWidth, $size[0], "{$relativePath} has the wrong width.");
            $this->assertSame($expectedHeight, $size[1], "{$relativePath} has the wrong height.");

            $image = imagecreatefrompng($path);
            $this->assertNotFalse($image, "{$relativePath} is not a readable PNG.");

            $cornerAlpha = (imagecolorat($image, 0, 0) >> 24) & 0x7F;
            $this->assertGreaterThan(0, $cornerAlpha, "{$relativePath} must keep its outer corner transparent.");

            imagedestroy($image);
        }
    }

    public function test_shared_pwa_meta_exposes_cross_platform_metadata_and_the_controller(): void
    {
        $meta = $this->read('resources/views/shared/pwa-meta.blade.php');

        $this->assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $meta);
        $this->assertStringContainsString('<meta name="apple-mobile-web-app-capable" content="yes">', $meta);
        $this->assertStringContainsString("asset('manifest.json?v=12')", $meta);
        $this->assertStringContainsString('rel="apple-touch-icon" sizes="180x180"', $meta);
        $this->assertStringContainsString("asset('img/apple-touch-icon.png')", $meta);
        $this->assertStringContainsString("asset('js/pwa-install.js?v=2')", $meta);
        $this->assertStringContainsString('data-sr-pwa-controller', $meta);

        $controller = $this->read('public/js/pwa-install.js');
        $this->assertStringContainsString("const SERVICE_WORKER_URL = '/sw.js?v=50';", $controller);
        $this->assertStringContainsString("windowRef.addEventListener('beforeinstallprompt'", $controller);
        $this->assertStringContainsString("serviceWorker.register(SERVICE_WORKER_URL, { scope: '/' })", $controller);
        $this->assertStringNotContainsString('?.', $controller);
    }

    public function test_every_full_web_shell_uses_the_shared_meta_and_install_prompt(): void
    {
        foreach (self::FULL_WEB_SHELLS as $relativePath) {
            $shell = $this->read($relativePath);

            $this->assertStringContainsString(
                "@include('shared.pwa-meta",
                $shell,
                "{$relativePath} must include the shared PWA metadata."
            );
            $this->assertStringContainsString(
                "@include('shared.pwa-install-prompt",
                $shell,
                "{$relativePath} must include the shared install prompt."
            );
        }
    }

    public function test_blade_layouts_no_longer_embed_legacy_install_or_service_worker_handlers(): void
    {
        foreach (self::BLADE_LAYOUTS as $relativePath) {
            $layout = $this->read($relativePath);

            $this->assertDoesNotMatchRegularExpression(
                '/beforeinstallprompt|serviceWorker\s*\.\s*register/u',
                $layout,
                "{$relativePath} still contains a legacy inline PWA handler."
            );
        }
    }

    public function test_authenticated_user_app_layouts_expose_install_actions_and_suppress_interview_auto_prompting(): void
    {
        foreach (['desktop', 'mobile'] as $layout) {
            $relativePath = "resources/views/{$layout}/layouts/app.blade.php";
            $source = $this->read($relativePath);

            $this->assertStringContainsString('data-pwa-install-action', $source);
            $this->assertStringContainsString('data-pwa-suppress-auto-install=', $source);
            $this->assertStringContainsString("request()->routeIs('interview.session')", $source);
        }
    }

    public function test_service_worker_v50_scopes_cache_cleanup_and_precaches_install_assets(): void
    {
        $worker = $this->read('public/sw.js');

        $this->assertStringContainsString("const CACHE_PREFIX = 'speakready-pwa-';", $worker);
        $this->assertMatchesRegularExpression(
            "/const CACHE_NAME = (?:'speakready-pwa-v50'|`\\$\\{CACHE_PREFIX\\}v50`);/",
            $worker
        );
        $this->assertStringContainsString('cacheName.startsWith(CACHE_PREFIX) && cacheName !== CACHE_NAME', $worker);

        foreach ([
            '/manifest.json',
            '/img/app-icon-192.png',
            '/img/app-icon-512.png',
            '/img/app-icon-maskable-192.png',
            '/img/app-icon-maskable-512.png',
            '/img/apple-touch-icon.png',
        ] as $asset) {
            $this->assertStringContainsString("'{$asset}'", $worker, "The service worker must precache {$asset}.");
        }
    }

    public function test_service_worker_activation_does_not_refresh_or_navigate_open_clients(): void
    {
        $worker = $this->read('public/sw.js');
        $activationStart = strpos($worker, "self.addEventListener('activate'");
        $activationEnd = strpos($worker, "self.addEventListener('fetch'", $activationStart ?: 0);

        $this->assertNotFalse($activationStart);
        $this->assertNotFalse($activationEnd);

        $activation = substr($worker, $activationStart, $activationEnd - $activationStart);

        $this->assertStringNotContainsString('refreshControlledClients', $worker);
        $this->assertStringNotContainsString('refreshedClientUrl', $worker);
        $this->assertStringNotContainsString('CLIENT_REFRESH_PARAM', $worker);
        $this->assertStringNotContainsString('CLIENT_REFRESH_VALUE', $worker);
        $this->assertStringNotContainsString('.navigate(', $activation);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $manifest = json_decode($this->read('public/manifest.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($manifest);

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function findManifestIcon(array $manifest, string $sizes, string $purpose): array
    {
        $icons = $manifest['icons'] ?? null;
        $this->assertIsArray($icons);

        foreach ($icons as $icon) {
            if (! is_array($icon) || ($icon['sizes'] ?? null) !== $sizes) {
                continue;
            }

            $purposes = preg_split('/\s+/', trim((string) ($icon['purpose'] ?? ''))) ?: [];

            if (in_array($purpose, $purposes, true)) {
                return $icon;
            }
        }

        $this->fail("The manifest does not declare a {$purpose} {$sizes} icon.");
    }

    private function read(string $relativePath): string
    {
        $path = $this->projectPath($relativePath);

        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertNotFalse($contents, "Unable to read {$relativePath}.");

        return $contents;
    }

    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }
}
