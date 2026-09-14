<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;

/**
 * Teleframe::routes()/DashboardRoutes::register() registration contract:
 * default prefix + middleware from config, overridable per call, named
 * routes, and a one-shot guard against double registration.
 */
final class DashboardRoutesTest extends TestCase
{
    public function test_default_registration_uses_config_prefix_and_middleware(): void
    {
        config()->set('teleframe.dashboard.prefix', 'teleframe');
        config()->set('teleframe.dashboard.middleware', ['web', 'auth']);

        DashboardRoutes::register();

        self::assertTrue(RouteFacade::has('teleframe.dashboard'));

        /** @var Route $route */
        $route = RouteFacade::getRoutes()->getByName('teleframe.dashboard');
        self::assertSame('teleframe', $route->uri());
        self::assertSame(['web', 'auth'], $route->gatherMiddleware());
    }

    public function test_override_prefix_and_middleware(): void
    {
        DashboardRoutes::register(prefix: 'api', middleware: ['auth:sanctum']);

        /** @var Route $route */
        $route = RouteFacade::getRoutes()->getByName('teleframe.apps.index');
        self::assertSame('api/apps', $route->uri());
        self::assertSame(['auth:sanctum'], $route->gatherMiddleware());
    }

    public function test_all_dashboard_routes_are_registered(): void
    {
        DashboardRoutes::register();

        foreach ([
            'teleframe.dashboard' => ['GET', 'teleframe'],
            'teleframe.apps.index' => ['GET', 'teleframe/apps'],
            'teleframe.apps.store' => ['POST', 'teleframe/apps'],
            'teleframe.apps.destroy' => ['DELETE', 'teleframe/apps/{id}'],
            'teleframe.accounts.index' => ['GET', 'teleframe/accounts'],
            'teleframe.accounts.destroy' => ['DELETE', 'teleframe/accounts/{id}'],
            'teleframe.telegram.start' => ['POST', 'teleframe/telegram/start'],
            'teleframe.telegram.verify' => ['POST', 'teleframe/telegram/verify'],
            'teleframe.telegram.password' => ['POST', 'teleframe/telegram/password'],
        ] as $name => [$method, $uri]) {
            self::assertTrue(RouteFacade::has($name), "missing route {$name}");
            /** @var Route $route */
            $route = RouteFacade::getRoutes()->getByName($name);
            self::assertSame($uri, $route->uri(), "uri mismatch for {$name}");
            self::assertSame($method, $route->methods()[0] ?? null, "method mismatch for {$name}");
        }
    }

    public function test_double_registration_is_guarded(): void
    {
        DashboardRoutes::register(prefix: 'a');
        DashboardRoutes::register(prefix: 'b');

        // First registration wins; second call is a no-op.
        /** @var Route $route */
        $route = RouteFacade::getRoutes()->getByName('teleframe.dashboard');
        self::assertSame('a', $route->uri());
        self::assertFalse(RouteFacade::has('a.dashboard'));
    }
}
