<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\RequireTelegramUserSession;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The Laravel-session-bound guard on the embedded Telegram client routes.
 *
 * Verbatim :11249 — "laravel session is not logied in and that page in this
 * situation should have a auth middleware" — and verbatim :6803 — "this user
 * of laravel should only have read only acces to this telgram account".
 *
 * tg.readonly (ScopeTelegramReadOnly) is NOT a guard: it only OPENS the
 * read-only frame when a session is already present, so a session-less
 * request was previously served the full client with the relay's read-only
 * enforcement switched OFF. This guard is the deny-by-default floor that
 * closes that silent allow: no authenticated Laravel user → refused, and
 * the controller is never reached.
 */
final class RequireTelegramUserSessionTest extends TestCase
{
    public function test_unauthenticated_request_is_refused_and_the_controller_is_never_reached(): void
    {
        $reached = false;
        $middleware = new RequireTelegramUserSession(static fn (): bool => false);

        $response = $middleware->handle(Request::create('/tg/'), static function () use (&$reached): Response {
            $reached = true;

            return new Response('SERVED', 200);
        });

        self::assertSame(401, $response->getStatusCode(), 'an unauthenticated request must be refused, never served');
        self::assertFalse($reached, 'the downstream controller must never run for an unauthenticated request');
    }

    public function test_authenticated_request_is_served_untouched(): void
    {
        $middleware = new RequireTelegramUserSession(static fn (): bool => true);

        $response = $middleware->handle(Request::create('/tg/'), static fn (): Response => new Response('SERVED', 200));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('SERVED', $response->getContent());
    }

    public function test_refusal_body_never_leaks_telegram_or_vault_detail(): void
    {
        $middleware = new RequireTelegramUserSession(static fn (): bool => false);

        $response = $middleware->handle(Request::create('/tg/'), static fn (): Response => new Response('SERVED'));

        $body = (string) $response->getContent();
        self::assertStringNotContainsStringIgnoringCase('session', $body);
        self::assertStringNotContainsStringIgnoringCase('account', $body);
        self::assertStringNotContainsStringIgnoringCase('telegram', $body);
    }

    public function test_a_failing_auth_resolver_fails_closed(): void
    {
        $middleware = new RequireTelegramUserSession(static function (): bool {
            throw new \RuntimeException('guard backend unreachable');
        });

        $reached = false;
        $response = $middleware->handle(Request::create('/tg/'), static function () use (&$reached): Response {
            $reached = true;

            return new Response('SERVED', 200);
        });

        self::assertSame(401, $response->getStatusCode(), 'when in doubt, DENY — a throwing resolver must not allow');
        self::assertFalse($reached);
    }

    public function test_refusal_does_not_leak_the_resolver_failure(): void
    {
        $middleware = new RequireTelegramUserSession(static function (): bool {
            throw new \RuntimeException('secret-guard-detail');
        });

        $response = $middleware->handle(Request::create('/tg/'), static fn (): Response => new Response('SERVED'));

        self::assertStringNotContainsString('secret-guard-detail', (string) $response->getContent());
    }

    public function test_the_embedded_client_route_carries_the_guard(): void
    {
        RouteFacade::telegramWeb('tg');

        // Fluent ->name() mutates the route after it lands in the collection,
        // so the name lookup table is stale until a request compiles routes —
        // the same pass DashboardRoutes::register() drives by hand.
        RouteFacade::getRoutes()->refreshNameLookups();

        self::assertTrue(RouteFacade::has('telegram.web'));

        /** @var Route $route */
        $route = RouteFacade::getRoutes()->getByName('telegram.web');
        $middleware = $route->gatherMiddleware();

        self::assertContains('tg.auth', $middleware, 'the embedded client must sit behind the session guard');
        self::assertContains('tg.readonly', $middleware, 'and keep the read-only scope frame');
    }

    public function test_the_guard_is_registered_as_the_tg_auth_alias(): void
    {
        $aliases = $this->app['router']->getMiddleware();

        self::assertSame(
            RequireTelegramUserSession::class,
            $aliases['tg.auth'] ?? null,
            'tg.auth must alias the session guard',
        );
    }

    /**
     * The last-mile proof, through the REAL middleware stack: an anonymous
     * request to the embedded client is refused at the guard, so the bundle
     * controller never runs. No tgweb bundle is needed — tg.auth runs before
     * the controller, so this holds on a clean checkout with no build.
     */
    public function test_an_anonymous_request_to_the_embedded_client_is_refused_end_to_end(): void
    {
        config()->set('teleframe.telegram_web.enabled', true);

        RouteFacade::telegramWeb('tg');

        $this->get('/tg/')->assertStatus(401);
    }

    /**
     * The guard is not merely present but ORDERED FIRST: the request must be
     * refused before ScopeTelegramReadOnly ever opens the read-only frame,
     * and long before the bundle controller runs.
     */
    public function test_the_guard_runs_before_the_read_only_frame(): void
    {
        config()->set('teleframe.telegram_web.enabled', true);

        RouteFacade::telegramWeb('tg');
        RouteFacade::getRoutes()->refreshNameLookups();

        /** @var Route $route */
        $route = RouteFacade::getRoutes()->getByName('telegram.web');
        $middleware = $route->gatherMiddleware();

        self::assertSame('tg.auth', $middleware[0] ?? null, 'the session guard must be the outermost teleframe middleware');
        self::assertSame(
            1,
            array_search('tg.readonly', $middleware, true),
            'the read-only frame opens only after the guard has admitted the caller',
        );
    }
}
