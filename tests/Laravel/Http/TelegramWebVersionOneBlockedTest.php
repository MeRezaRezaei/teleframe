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
 * The operator's directive, verbatim:
 *
 *   "store the state of telegram oficial view its not for version two and must
 *    not work in this verson blocking by a flag"
 *
 * WHY THIS FILE IS SEPARATE FROM TelegramWebControllerTest, and why that matters.
 *
 * TelegramWebControllerTest forces `telegram_web.enabled = true` for every one of
 * its seven cases and skips the WHOLE suite when the tgweb/dist bundle is absent
 * -- which it always is in CI, because the bundle is a gitignored npm build
 * artifact. So the version-one block had NO test at all: the only behaviour the
 * operator explicitly asked to be guaranteed was the one behaviour the suite was
 * structurally unable to exercise, because it is the case where the bundle does
 * not matter.
 *
 * That is why this is a separate file with no bundle dependency. The point of a
 * version boundary is that it holds in every environment, including a clean
 * checkout with nothing built -- so the test must run there too.
 *
 * The default is asserted as well as the explicit false, because the guarantee is
 * two-sided: a host that never sets the flag must get a 404, and a host that sets
 * it false must get a 404. Both are asserted against the SHIPPED default rather
 * than a value this test sets, so that changing the default in config/teleframe.php
 * to true would fail here.
 */
final class TelegramWebVersionOneBlockedTest extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Deliberately NOT touching teleframe.telegram_web.enabled. The shipped
        // default is the subject under test.
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The flag is checked BEFORE the bundle is resolved
        // (TelegramWebController::__invoke, the first statement), so no bundle is
        // needed and none is asserted. This test is meaningful in a clean CI
        // checkout for exactly that reason.
        //
        // The authorization guard is bound open so that a 404 here can only mean
        // the VERSION GATE refused, never that auth did. Otherwise this test would
        // keep passing if someone deleted the version-one check entirely and the
        // auth guard silently answered instead -- which is the failure mode that
        // would hide the regression.
        $this->app->bind(
            RequireTelegramUserSession::class,
            static fn (): RequireTelegramUserSession => new RequireTelegramUserSession(static fn (): bool => true),
        );

        Route::telegramWeb('tg');
    }

    /**
     * The SHIPPED default must block the official view. This is the version-one
     * guarantee: a host that does nothing gets no official Telegram Web client.
     */
    public function test_official_view_is_blocked_by_default(): void
    {
        // Assert the shipped default first, so a failure names the real cause
        // rather than presenting as a routing problem.
        self::assertFalse(
            (bool) config('teleframe.telegram_web.enabled', true),
            'telegram_web.enabled must default to FALSE: the official view is a version-two feature '
            .'and version one must not serve it. Change it only with a version-two decision.'
        );

        $this->dispatchRaw('/tg/')
            ->assertNotFound()
            ->assertSee('not enabled');
    }

    /**
     * Explicitly off is the same outcome, and is asserted separately because a
     * host may disable it in production without relying on a default.
     */
    public function test_official_view_is_blocked_when_explicitly_disabled(): void
    {
        config()->set('teleframe.telegram_web.enabled', false);

        $this->dispatchRaw('/tg/')
            ->assertNotFound()
            ->assertSee('not enabled');
    }

    /**
     * The gate must be checked BEFORE the bundle, so that a host which has
     * provisioned a real bundle but is still on version one is refused.
     *
     * This is the ordering that makes the flag a version boundary rather than a
     * "is the build present" switch. If the order were ever reversed, a built
     * bundle would start serving on version one, silently — and no test that only
     * ever ran without a bundle would notice.
     */
    public function test_gate_precedes_the_bundle_so_a_built_bundle_cannot_leak_into_version_one(): void
    {
        // A real directory with a real index.html, pointed at by config. If the
        // controller resolved the bundle first, this would serve 200 with content.
        $dist = sys_get_temp_dir().'/tf-v1-gate-'.bin2hex(random_bytes(6));
        mkdir($dist, 0o777, true);
        file_put_contents($dist.'/index.html', '<!doctype html><title>LEAKED</title>');
        $this->beforeApplicationDestroyed(static function () use ($dist): void {
            @unlink($dist.'/index.html');
            @rmdir($dist);
        });

        config()->set('teleframe.telegram_web.dist_path', $dist);
        config()->set('teleframe.telegram_web.enabled', false);

        $response = $this->dispatchRaw('/tg/');

        $response->assertNotFound();
        self::assertStringNotContainsString(
            'LEAKED',
            $response->getContent() ?: '',
            'a provisioned bundle must NOT be served while the version-one flag is off'
        );
    }

    /**
     * The Laravel test client normalizes URIs with trim(url($uri), '/'), stripping
     * the trailing slash. Raw-dispatch so the request behaves like production,
     * where $_SERVER['REQUEST_URI'] keeps it.
     */
    private function dispatchRaw(string $url): TestResponse
    {
        $request = Request::create($url);
        $response = $this->app->make(HttpKernel::class)->handle($request);

        return new TestResponse($response);
    }
}
