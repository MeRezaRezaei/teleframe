<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\RequireTelegramUserSession;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase as BaseTestCase;

/**
 * Serves the embedded Telegram Web client (tgweb/dist) at /tg behind a
 * trailing-slash SPA fallback: the client is HASH-routed yet references its
 * assets via relative URLs, so the app must live on a URL that ends in `/`
 * or every ./assets/... reference resolves against the site root and 404s.
 */
final class TelegramWebControllerTest extends BaseTestCase
{
    private string $distPath = '';

    protected function getEnvironmentSetUp($app): void
    {
        $this->distPath = (string) realpath(dirname(__DIR__, 3).'/tgweb/dist');

        $app['config']->set('teleframe.telegram_web.enabled', true);
        $app['config']->set('teleframe.telegram_web.dist_path', $this->distPath);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // W-05: tgweb/dist is a gitignored build artifact (npm output is not
        // portable; rebuild via bin/build-tgweb.sh). A clean CI checkout has
        // no bundle, so skip the /tg suite outright instead of failing the
        // pipe — same pattern as HotReloadLiveTest. The bundle is required
        // only when the tests are actually exercised (local build present).
        if (realpath(dirname(__DIR__, 3).'/tgweb/dist') === false) {
            self::markTestSkipped('tgweb bundle not built (rebuild via bin/build-tgweb.sh)');
        }

        self::assertNotSame('', $this->distPath, 'tgweb/dist bundle must exist');

        // /tg sits behind tg.auth (RequireTelegramUserSession) — the
        // deny-by-default Laravel-session guard. This suite's subject is
        // BUNDLE SERVING, not authorization, so bind the guard to an
        // authenticated caller; the refusal path itself is covered by
        // RequireTelegramUserSessionTest.
        $this->app->bind(
            RequireTelegramUserSession::class,
            static fn (): RequireTelegramUserSession => new RequireTelegramUserSession(static fn (): bool => true),
        );

        Route::telegramWeb('tg');
    }

    public function test_root_without_trailing_slash_redirects_301_preserving_query(): void
    {
        $response = $this->get('/tg?theme=dark');

        $response->assertStatus(301);
        self::assertStringEndsWith('/tg/?theme=dark', (string) $response->headers->get('Location'));
    }

    public function test_root_with_trailing_slash_serves_index(): void
    {
        $this->dispatchRaw('http://localhost/tg/')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function test_slashless_spa_path_redirects_to_trailing_slash(): void
    {
        $response = $this->get('/tg/some/path');

        $response->assertStatus(301);
        self::assertStringEndsWith('/tg/some/path/', (string) $response->headers->get('Location'));
    }

    public function test_spa_path_with_trailing_slash_falls_back_to_index(): void
    {
        $this->dispatchRaw('http://localhost/tg/some/path/')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function test_asset_file_is_served_directly_with_mime(): void
    {
        $files = glob($this->distPath.'/assets/*.js', GLOB_NOSORT);
        self::assertNotEmpty($files, 'dist must contain assets/*.js files');

        $this->get('/tg/assets/'.basename($files[0]))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    }

    public function test_root_file_is_served_directly_with_mime(): void
    {
        $this->get('/tg/redirect.js')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
    }

    public function test_directory_traversal_is_rejected(): void
    {
        $this->get('/tg/../x')->assertNotFound();
    }

    /**
     * The Laravel test client normalizes every URI with
     * `trim(url($uri), '/')`, stripping the trailing slash before the request
     * is built. Raw-dispatch a Request so it behaves like production, where
     * $_SERVER['REQUEST_URI'] keeps the client's trailing slash.
     */
    private function dispatchRaw(string $url): TestResponse
    {
        $request = Request::create($url);
        $response = $this->app->make(HttpKernel::class)->handle($request);

        return new TestResponse($response);
    }
}
