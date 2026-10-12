<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MiniApp;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Phase 5g Q12(a) presence + shape proof for the publishable mini-app stub
 * tree. No real Vue/Vite toolchain runs in CI — these files are checked for
 * existence and the exact surface the docs promise (bridge API, theme var
 * set, Blade host hooks, Vite wiring, peer deps).
 */
class StubPresenceTest extends TestCase
{
    private const STUB_ROOT = __DIR__ . '/../../src/Laravel/Stubs/miniapp';

    /**
     * @return list<array{string}>
     */
    public static function expectedFiles(): array
    {
        return [
            ['resources/views/telegram/app.blade.php'],
            ['resources/js/telegram-web-app.js'],
            ['resources/js/examples/miniapp.vue'],
            ['resources/css/telegram-theme.css'],
            ['vite.config.js'],
            ['package.json'],
        ];
    }

    #[DataProvider('expectedFiles')]
    public function testStubFileExists(string $relativePath): void
    {
        self::assertFileExists(self::STUB_ROOT . '/' . $relativePath);
    }

    public function testBladeHostDeclaresHtmlWithDataTheme(): void
    {
        $blade = $this->read('resources/views/telegram/app.blade.php');

        self::assertStringContainsString('<html lang="en" data-theme=', $blade);
        self::assertStringContainsString('$theme ?? \'light\'', $blade);
    }

    public function testBladeHostLoadsBridgeAndMountsApp(): void
    {
        $blade = $this->read('resources/views/telegram/app.blade.php');

        self::assertStringContainsString('telegram-web-app.js', $blade);
        self::assertStringContainsString('<div id="app"', $blade);
        self::assertStringContainsString('build/miniapp.js', $blade);
    }

    public function testBladeHostIncludesInitDataBootstrapAndThemeCss(): void
    {
        $blade = $this->read('resources/views/telegram/app.blade.php');

        self::assertStringContainsString('TELEFRAME_MINIAPP_BOOTSTRAP', $blade);
        self::assertStringContainsString('initData', $blade);
        self::assertStringContainsString('telegram:init-data', $blade);
        self::assertStringContainsString('telegram-theme.css', $blade);
        self::assertStringContainsString('$nonce', $blade);
    }

    public function testBridgeExposesRuledSurface(): void
    {
        $bridge = $this->read('resources/js/telegram-web-app.js');

        self::assertStringContainsString('() => ({ initData, user })', $bridge);
        self::assertStringContainsString('export default bridge', $bridge);
        self::assertStringContainsString('window.TeleFrameBridge', $bridge);
        self::assertStringContainsString('function sendData', $bridge);
        self::assertStringContainsString('function postMessage', $bridge);
        self::assertStringContainsString('function onThemeChange', $bridge);
        self::assertStringContainsString('function readInitData', $bridge);
        self::assertStringContainsString('function readUser', $bridge);
    }

    public function testExampleVueAppUsesBridgeAsImportAndAuthHeader(): void
    {
        $vue = $this->read('resources/js/examples/miniapp.vue');

        self::assertStringContainsString("import telegramWebApp from '../telegram-web-app.js'", $vue);
        self::assertStringContainsString("const { initData, user } = telegramWebApp()", $vue);
        self::assertStringContainsString('X-Telegram-Init-Data', $vue);
        self::assertStringContainsString('/api/telegram/me', $vue);
    }

    public function testThemeCssMapsEveryDocumentedVarLightAndDark(): void
    {
        $css = $this->read('resources/css/telegram-theme.css');

        foreach (self::themeVarNames() as $var) {
            self::assertStringContainsString('--tg-theme-' . $var, $css);
        }

        self::assertStringContainsString(":root[data-theme='light']", $css);
        self::assertStringContainsString(":root[data-theme='dark']", $css);
    }

    public function testVitePresetWiresExampleAppAndDeterministicOutput(): void
    {
        $vite = $this->read('vite.config.js');

        self::assertStringContainsString("from 'vite'", $vite);
        self::assertStringContainsString('@vitejs/plugin-vue', $vite);
        self::assertStringContainsString('examples/miniapp.vue', $vite);
        self::assertStringContainsString("entryFileNames: 'miniapp.js'", $vite);
        self::assertStringContainsString("outDir: 'public/build'", $vite);
    }

    public function testPackageJsonDeclaresViteAndVueAsPeers(): void
    {
        $decoded = json_decode($this->read('package.json'), true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('peerDependencies', $decoded);
        self::assertArrayHasKey('vite', $decoded['peerDependencies']);
        self::assertArrayHasKey('vue', $decoded['peerDependencies']);
        self::assertSame('teleframe-miniapp', $decoded['name']);
        self::assertTrue($decoded['private']);
    }

    /**
     * Telegram themeParams palette (docs/miniapp.md theme table).
     *
     * @return list<string>
     */
    private static function themeVarNames(): array
    {
        return [
            'bg-color',
            'text-color',
            'hint-color',
            'button-color',
            'button-text-color',
            'secondary-bg-color',
            'header-bg-color',
            'link-color',
            'bottom-bar-bg-color',
            'destructive-text-color',
        ];
    }

    private function read(string $relativePath): string
    {
        $contents = file_get_contents(self::STUB_ROOT . '/' . $relativePath);

        self::assertIsString($contents);

        return $contents;
    }
}