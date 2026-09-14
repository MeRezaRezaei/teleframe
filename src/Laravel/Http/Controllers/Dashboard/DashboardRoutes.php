<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Routing\Router;
use Illuminate\Support\Arr;

/**
 * Horizon-style dashboard route registration.
 *
 * Call from your app's service provider boot():
 *
 *     use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
 *     DashboardRoutes::register();
 *
 * or through the Teleframe facade:
 *
 *     Teleframe::routes();
 *
 * Registers a single route group under config('teleframe.dashboard.prefix')
 * (default `teleframe`) protected by config('teleframe.dashboard.middleware')
 * (default `['web', 'auth']` — the HOST's own login, like Laravel Horizon).
 * Token hosts may override: Teleframe::routes(prefix: 'api', middleware: ['auth:sanctum']).
 *
 * The group serves both the server-rendered dashboard index and the JSON
 * API the page's fetch calls drive, so one registration covers a Blade
 * dashboard AND a headless SPA/API client.
 */
final class DashboardRoutes
{
    private static bool $registered = false;

    /**
     * @param  string|null  $prefix  route prefix (default: config teleframe.dashboard.prefix)
     * @param  list<string>|null  $middleware  guard stack (default: config teleframe.dashboard.middleware)
     */
    public static function register(?string $prefix = null, ?array $middleware = null): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        /** @var array{prefix?: string, middleware?: list<string>} $cfg */
        $cfg = config('teleframe.dashboard', ['prefix' => 'teleframe', 'middleware' => ['web', 'auth']]);

        $router = app(Router::class);
        $router->group([
            'prefix' => $prefix ?? (string) ($cfg['prefix'] ?? 'teleframe'),
            'middleware' => $middleware ?? Arr::wrap($cfg['middleware'] ?? ['web', 'auth']),
        ], static function (Router $router): void {
            // Dashboard index (server-rendered shell).
            $router->get('/', [DashboardController::class, 'index'])->name('teleframe.dashboard');

            // Apps (vault: api_id / api_hash).
            $router->get('apps', [AppsController::class, 'index'])->name('teleframe.apps.index');
            $router->post('apps', [AppsController::class, 'store'])->name('teleframe.apps.store');
            $router->delete('apps/{id}', [AppsController::class, 'destroy'])->whereNumber('id')->name('teleframe.apps.destroy');

            // Accounts (linked to an app + encrypted session).
            $router->get('accounts', [AccountsController::class, 'index'])->name('teleframe.accounts.index');
            $router->delete('accounts/{id}', [AccountsController::class, 'destroy'])->whereNumber('id')->name('teleframe.accounts.destroy');

            // Telegram phone-login flow (stateful, parked in cache).
            $router->post('telegram/start', [TelegramLoginController::class, 'start'])->name('teleframe.telegram.start');
            $router->post('telegram/verify', [TelegramLoginController::class, 'verify'])->name('teleframe.telegram.verify');
            $router->post('telegram/password', [TelegramLoginController::class, 'password'])->name('teleframe.telegram.password');
        });

        // Fluent ->name() mutates the route AFTER it lands in the collection,
        // so the name lookup table is stale until a request compiles routes.
        // Refresh it now (the same pass toSymfonyRouteCollection() does at
        // request dispatch) so Route::has()/route() work immediately after
        // registration, e.g. in feature tests.
        $router->getRoutes()->refreshNameLookups();
    }

    /** Test/diagnostic seam: forget the double-registration guard. */
    public static function reset(): void
    {
        self::$registered = false;
    }

    /** Test/diagnostic seam: current guard state. */
    public static function registered(): bool
    {
        return self::$registered;
    }
}
