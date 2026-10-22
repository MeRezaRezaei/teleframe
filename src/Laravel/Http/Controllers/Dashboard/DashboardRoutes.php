<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;

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
 *
 * ONE EXCEPTION, IN ITS OWN GROUP: the anonymous passport LOGIN pair
 * (`passport/login/start`, `passport/login/callback`) is registered with
 * `web` + a throttle and WITHOUT the host's `auth` guard, because its entire
 * purpose is to authenticate someone who has no session yet. It is a separate
 * capability (`teleframe.passport.anonymous_login`, default false), not a
 * relaxation of the group above — see registerAnonymousPassportRoutes().
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
            // tg.readonly (Cycle 6) rides on the host's stack: when a Laravel
            // user session is present, the relay switch/presence + account
            // control routes open a read-only scope frame so the vault
            // terminal dispatch refuses state-changing RPCs. A session-less
            // host (harness, no guard) is untouched — full behaviour.
            'middleware' => array_values(array_unique([
                ...($middleware ?? Arr::wrap($cfg['middleware'] ?? ['web', 'auth'])),
                'tg.readonly',
            ])),
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

            // Control layer: per-account relay ticket (mint + single-use redeem).
            $router->post('accounts/{id}/ticket', [RelayTicketController::class, 'store'])->whereNumber('id')->name('teleframe.accounts.ticket');
            $router->post('relay/redeem', [RelayTicketController::class, 'redeem'])->name('teleframe.relay.redeem');

            // Control layer: per-account relay presence (+ manual heartbeat).
            $router->get('accounts/{id}/presence', [RelayPresenceController::class, 'show'])->whereNumber('id')->name('teleframe.accounts.presence');
            $router->post('accounts/{id}/presence/touch', [RelayPresenceController::class, 'touch'])->whereNumber('id')->name('teleframe.accounts.presence.touch');

            // Control layer: render one vault account ACTIVE for the relay
            // (account switch — the dashboard shell calls this, then re-dials
            // the terminal the switch returns).
            $router->post('accounts/{id}/switch', [RelaySwitchController::class, 'store'])->whereNumber('id')->name('teleframe.accounts.switch');

            // The `/tg` browser view: the relay-managed vault accounts as a chat list,
            // with the account switch (above) in the middle. Server-rendered, no build
            // step; the patched-client relay seam stays on `Route::telegramWeb()`.
            $router->get('tg', [TgController::class, 'show'])->name('teleframe.tg');

            // Operations: recent + failed work (ingest strike ledger,
            // dead-letter stream, stream backlog) as JSON — the Horizon-style
            // failed-jobs view the package's own supervisor feeds.
            $router->get('ops', [OpsController::class, 'show'])->name('teleframe.ops.index');

            // Telegram phone-login flow (stateful, parked in cache).
            $router->post('telegram/start', [TelegramLoginController::class, 'start'])->name('teleframe.telegram.start');
            $router->post('telegram/verify', [TelegramLoginController::class, 'verify'])->name('teleframe.telegram.verify');
            $router->post('telegram/password', [TelegramLoginController::class, 'password'])->name('teleframe.telegram.password');

            // Telegram Passport login (the HTTP half of the passport engine).
            // Same group middleware as everything else, so the host's own
            // `auth` guard applies: a passport login can only be STARTED from
            // an authenticated Laravel session, which is what the one-time
            // nonce is bound to. The callback stays in the group on purpose —
            // the credentials are tied to the session that started the trip.
            $router->get('passport', [PassportController::class, 'show'])->name('teleframe.passport.show');
            $router->match(['get', 'post'], 'passport/start', [PassportController::class, 'start'])->name('teleframe.passport.start');
            $router->match(['get', 'post'], 'passport/callback', [PassportController::class, 'callback'])->name('teleframe.passport.callback');
        });

        self::registerAnonymousPassportRoutes($router, $prefix, $cfg);

        // Fluent ->name() mutates the route AFTER it lands in the collection,
        // so the name lookup table is stale until a request compiles routes.
        // Refresh it now (the same pass toSymfonyRouteCollection() does at
        // request dispatch) so Route::has()/route() work immediately after
        // registration, e.g. in feature tests.
        $router->getRoutes()->refreshNameLookups();
    }

    /**
     * The ANONYMOUS passport login pair — the routes that can turn a visitor
     * with no Laravel session into a logged-in user, and therefore the only
     * routes in this file that sit OUTSIDE the host's `auth` guard.
     *
     * Registered in their own group for exactly that reason, and not as an
     * exception carved out of the group above: the authenticated passport
     * routes stay behind `auth` where they belong, and this pair is a
     * capability a host opts into (`teleframe.passport.anonymous_login`,
     * default FALSE) rather than one it inherits from enabling Passport. The
     * routes exist when the capability is off; the controller refuses 403
     * before issuing or consuming anything, which is easier to test and
     * harder to get wrong than conditional registration.
     *
     * MIDDLEWARE, and why each entry is here:
     *   `web` — the session. Not optional decoration: the login is bound to a
     *     fingerprint of the session that started it, so a request with no
     *     session has no binding and the controller refuses 401. A host
     *     mounting these on a group without `web` gets a refusal, not an
     *     unbound flow.
     *   `throttle:N,1` — an unauthenticated authentication endpoint is a
     *     credential-guessing surface, so it ships throttled rather than
     *     left to the host to remember. N is
     *     `teleframe.passport.login_rate_limit` (default 10/min), keyed by
     *     client IP, and it covers marker MINTING as well as credential
     *     presentation. The host's own global limiter still applies on top.
     *     The numeric form is used rather than a named limiter so a host that
     *     registers DashboardRoutes without this package's provider still gets
     *     a working throttle instead of an undefined-limiter 500.
     *
     * @param  array{prefix?: string, middleware?: list<string>}  $cfg
     */
    private static function registerAnonymousPassportRoutes(Router $router, ?string $prefix, array $cfg): void
    {
        // ONE formula for the ceiling, read through PassportConfig rather than
        // re-derived here: a second `?? 10` in this file would be a second
        // place for the default to drift, and the accessor is the same one a
        // plain-PHP host reads. `loginRateLimit()` also folds a negative value
        // to 0 (no package ceiling) and keeps 0 meaning the same thing.
        $passportConfig = new PassportConfig((array) (config('teleframe.passport') ?? []));
        $limit = $passportConfig->loginRateLimit();

        $middleware = ['web'];
        if ($limit > 0) {
            $middleware[] = 'throttle:'.$limit.',1';
        }

        $router->group([
            'prefix' => $prefix ?? (string) ($cfg['prefix'] ?? 'teleframe'),
            'middleware' => $middleware,
        ], static function (Router $router): void {
            $router->match(['get', 'post'], 'passport/login/start', [PassportController::class, 'loginStart'])
                ->name('teleframe.passport.login.start');
            $router->match(['get', 'post'], 'passport/login/callback', [PassportController::class, 'loginCallback'])
                ->name('teleframe.passport.login.callback');
        });
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
