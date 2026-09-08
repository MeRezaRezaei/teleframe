<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MiniApp;

/**
 * Phase 5g Blade host render proof: the stub `telegram.app` view renders on a
 * real Testbench container, declares `<html lang="en" data-theme>`, links the
 * theme CSS, loads the zero-build bridge asset, includes the mini-app mount,
 * and carries the init-data bootstrap — both light and dark, with and without
 * a CSP nonce.
 */
class BladeHostTest extends TestCase
{
    private const VIEW_PATH = __DIR__ . '/../../src/Laravel/Stubs/miniapp/resources/views';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->getFinder()->addLocation(self::VIEW_PATH);
    }

    public function testHostRendersHtmlWithDataThemeLight(): void
    {
        $html = $this->render(['theme' => 'light']);

        self::assertStringContainsString('<html lang="en" data-theme="light">', $html);
    }

    public function testHostRendersHtmlWithDataThemeDark(): void
    {
        $html = $this->render(['theme' => 'dark']);

        self::assertStringContainsString('<html lang="en" data-theme="dark">', $html);
    }

    public function testHostLoadsThemeCssAndBridgeAsset(): void
    {
        $html = $this->render();

        self::assertStringContainsString('telegram-theme.css', $html);
        self::assertStringContainsString('telegram-web-app.js', $html);
        self::assertStringContainsString('build/miniapp.js', $html);
    }

    public function testHostMountsTheMiniApp(): void
    {
        $html = $this->render();

        self::assertStringContainsString('<div id="app"', $html);
        self::assertStringContainsString('data-role="telegram-miniapp"', $html);
    }

    public function testHostIncludesInitDataBootstrap(): void
    {
        $html = $this->render();

        self::assertStringContainsString('TELEFRAME_MINIAPP_BOOTSTRAP', $html);
        self::assertStringContainsString('initData', $html);
        self::assertStringContainsString('window.__teleframeInitData', $html);
        self::assertStringContainsString('telegram:init-data', $html);
    }

    public function testHostAppliesCspNonceWhenProvided(): void
    {
        $html = $this->render(['nonce' => 'abc-nonce-123']);

        self::assertStringContainsString('nonce="abc-nonce-123"', $html);

        $plain = $this->render();
        self::assertStringNotContainsString('nonce=', $plain);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data = []): string
    {
        return (string) view('telegram.app', $data)->render();
    }
}