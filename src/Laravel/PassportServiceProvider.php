<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\PassportCommand;
use MeRezaRezaei\Teleframe\Passport\AuthorizationExporter;
use MeRezaRezaei\Teleframe\Passport\AuthorizationFormService;
use MeRezaRezaei\Teleframe\Passport\AuthorizationImporter;
use MeRezaRezaei\Teleframe\Passport\BindingWriter;
use MeRezaRezaei\Teleframe\Passport\CacheNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\CachePendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\EloquentBindingWriter;
use MeRezaRezaei\Teleframe\Passport\FieldIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\IdentityResolver;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\NonAtomicNonceStoreException;
use MeRezaRezaei\Teleframe\Passport\NonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportProfileFetcher;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Passport\PassportWireLogin;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmer;
use MeRezaRezaei\Teleframe\Passport\PendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\RedisAtomicCacheStore;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\UserScopePassportWire;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * PassportServiceProvider — self-contained wiring for the Telegram Passport
 * login module (design §2/§7, NotifyServiceProvider/AppServiceProvider
 * pattern): config merge (teleframe.passport.*, its own config file), the
 * six fixed `teleclient.passport.*` seams (key loader, nonce registry,
 * identity resolver, binding writer, confirmer, and the composed service
 * the facade resolves), the host-auth ride closure, and the
 * `teleframe:passport` command. Registered via composer.json
 * extra.laravel.providers — deliberately NOT hooked into
 * TeleframeServiceProvider (parallel-session ownership, never-commit).
 *
 * INERT BY DEFAULT (teleframe.passport.enabled=false): every seam resolves
 * with memory/default fakes and the service constructs — login() throws loud
 * only when someone actually calls it (Notify posture). Config rows never
 * carry sessions, PEM bodies or tokens — only the PEM path (AGENTS.md).
 *
 * Host-auth ride (Cycle-8b): the package NEVER calls Auth::login itself; the
 * service's onLogin hook is the boundary, and its default is this provider's
 * closure — Auth::guard()->login($user) + session regenerate — installed
 * ONLY when a Laravel auth factory is actually bound (plain-PHP hosts ride
 * nothing).
 */
final class PassportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/passport.php', 'teleframe.passport');

        /** @var Repository $config */
        $config = $this->app->make('config');

        $this->registerKeys($config);
        $this->registerNonces($config);
        $this->registerIdentity($config);
        $this->app->singleton(PassportSupport::WRITER_KEY, static fn (): BindingWriter => new EloquentBindingWriter);
        $this->app->singleton(PassportSupport::CONFIRMER_KEY, static fn (): PasswordConfirmer => new PasswordConfirmer);
        $this->registerWire();
        $this->registerService($config);
        $this->registerAnonymousLogin($config);

        // Method injection for the HTTP surface: PassportController asks the
        // container for PassportLoginService by class name, and the composed
        // service lives under the fixed teleclient.passport.service key.
        $this->app->alias(PassportSupport::SERVICE_KEY, PassportLoginService::class);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/passport.php' => config_path('teleframe/passport.php'),
        ], 'teleframe-passport-config');

        $this->commands([
            PassportCommand::class,
        ]);
    }

    /**
     * teleclient.passport.keys — PassportKeyLoader from
     * teleframe.passport.private_key_path. The container/config only ever
     * holds the PATH — key() materializes and caches the PEM in-call.
     */
    private function registerKeys(Repository $config): void
    {
        $this->app->singleton(PassportSupport::KEY_LOADER_KEY, static function () use ($config): PassportKeyLoader {
            return new PassportKeyLoader(
                (string) ($config->get('teleframe.passport.private_key_path') ?? ''),
            );
        });
    }

    /**
     * teleclient.passport.nonces — NonceRegistry. teleframe.passport.nonce_store
     * non-empty binds CacheNonceRegistry over the host's PSR-16 cache seam
     * (durable — nonces must survive between the issuing web session and the
     * webhook); '' uses the in-process MemoryNonceRegistry (tests /
     * plain-PHP / unconfigured).
     *
     * ATOMIC ONE-TIME (the replay guard, closed rather than documented). A
     * PSR-16 store has no get-and-delete, so a plain read-then-delete consume
     * lets two overlapping requests both pass — a redelivered webhook, a
     * double submit, two logins from one nonce. When the host's store is
     * Redis, this provider wraps it in RedisAtomicCacheStore so consume is a
     * single server-side GET+DEL: same seam, same drivers, same Lua script
     * RelayTicketBroker uses for relay tickets, no new binding to configure.
     *
     * When the store CANNOT be atomic, the registry is still bound (the
     * shipped behaviour is unchanged) and simply reports isAtomic() === false.
     * A host that needs a hard guarantee opts into a loud refusal instead of
     * a quiet downgrade: teleframe.passport.require_atomic_nonce = true throws
     * NonAtomicNonceStoreException here, at resolve, naming the store class —
     * a failed boot is better than a double login. A host can also ask in
     * advance, without binding: any NonceRegistry answers isAtomic().
     */
    private function registerNonces(Repository $config): void
    {
        $this->app->singleton(PassportSupport::NONCES_KEY, static function ($app) use ($config): NonceRegistry {
            $storeKey = (string) ($config->get('teleframe.passport.nonce_store') ?? '');
            $ttl = (int) ($config->get('teleframe.passport.nonce_ttl') ?? 900);
            $requireAtomic = (bool) ($config->get('teleframe.passport.require_atomic_nonce') ?? false);

            if ($storeKey === '') {
                return new MemoryNonceRegistry($ttl);
            }

            $store = $app->make(CacheInterface::class);
            if (! $store instanceof CacheInterface) {
                throw new RuntimeException(
                    'teleframe.passport.nonce_store is set but '.CacheInterface::class
                    .' is not bound — bind a PSR-16 store (or clear nonce_store for the memory registry).'
                );
            }

            // Probe the store CLASSES, never a connection: this runs at
            // resolve time and must not dial anything.
            $registry = new CacheNonceRegistry(
                RedisAtomicCacheStore::supports($store) ? new RedisAtomicCacheStore($store) : $store,
                $ttl,
                'teleframe.passport.nonce.',
            );

            if ($requireAtomic && ! $registry->isAtomic()) {
                throw NonAtomicNonceStoreException::forStore($store::class);
            }

            return $registry;
        });
    }

    /**
     * teleclient.passport.identity — IdentityResolver. user_lookup=session
     * (the shipped default) resolves the Laravel user via the host's
     * auth.providers.users.model class; 'field' (email/phone lookup) and any
     * other value are refused loud — never a silent fallback.
     */
    private function registerIdentity(Repository $config): void
    {
        $this->app->singleton(PassportSupport::IDENTITY_KEY, static function () use ($config): IdentityResolver {
            $lookup = (string) ($config->get('teleframe.passport.user_lookup') ?? 'session');

            if ($lookup === 'session') {
                return new SessionIdentityResolver(
                    (string) ($config->get('auth.providers.users.model') ?? ''),
                );
            }

            if ($lookup === 'field') {
                // Field mode binds the login to a CLAIM inside the decrypted
                // payload, so the host must have verified it first. Gating on
                // an explicit acknowledgement keeps 'user_lookup=field' from
                // silently becoming "trust any email Telegram hands us".
                if (! (bool) $config->get('teleframe.passport.require_verified', false)) {
                    throw new RuntimeException(
                        'teleframe.passport.user_lookup=field binds a login to a claim inside the payload, '
                        .'so it requires teleframe.passport.require_verified=true — the host must have '
                        .'verified that email/phone itself (confirmation mail, phone OTP) before trusting it. '
                        .'Set it deliberately, or keep user_lookup=session (the shipped default).'
                    );
                }

                $userClass = (string) ($config->get('auth.providers.users.model') ?? '');

                // teleframe.passport.user_lookup_field used to be read into
                // $field HERE and then never passed on, so a host that set it to
                // `phone` silently got email matching and no warning. The
                // resolver does not take a field: it INFERS one from whichever
                // claim the decrypted payload actually carries (email, or
                // phone, and it refuses a payload carrying both), and each is
                // compared against its own configured column. The key is
                // therefore removed rather than wired, because inventing a
                // restriction the design never had would be a behaviour change
                // dressed as a fix.
                return new FieldIdentityResolver(
                    userClass: $userClass,
                    emailColumn: (string) ($config->get('teleframe.passport.email_column') ?? 'email'),
                    phoneColumn: (string) ($config->get('teleframe.passport.phone_column') ?? 'phone'),
                );
            }

            throw new RuntimeException(
                "unknown teleframe.passport.user_lookup \"{$lookup}\" — expected session|field."
            );
        });
    }

    /**
     * The WIRE seams (Cycle 18, 2026-09-25) — the passport calls that make a
     * login a real Telegram login instead of a decrypted blob: one injected
     * `call()` seam over the host's live `UserAccountScope`, the four
     * documented family services built on it, and the wire-login orchestrator.
     *
     * INERT BY DEFAULT, twice over: the module is off unless
     * teleframe.passport.enabled, and even enabled, no scope is resolved unless
     * the host binds `teleclient.passport.scope`. Nothing here dials at
     * register/boot; an unbound scope makes the wire seam throw loud on its
     * first call, never a silent no-op.
     */
    private function registerWire(): void
    {
        $this->app->singleton(PassportSupport::WIRE_KEY, function ($app): PassportWireContract {
            $scope = $app->bound(PassportSupport::SCOPE_KEY)
                ? $app->make(PassportSupport::SCOPE_KEY)
                : null;

            if ($scope !== null && ! $scope instanceof UserAccountScope && $scope instanceof \Closure) {
                // A host may bind a resolver closure instead of a live scope
                // (dialing at resolve time is the host's decision, never ours).
                $resolved = $scope();
                $scope = is_object($resolved) ? $resolved : null;
            }

            if ($scope !== null && ! $scope instanceof UserAccountScope) {
                throw new RuntimeException(
                    PassportSupport::SCOPE_KEY.' must bind a '
                    .UserAccountScope::class.' (or a closure returning one).'
                );
            }

            return new UserScopePassportWire(
                $scope,
                $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
            );
        });

        $this->app->singleton(PassportSupport::IMPORTER_KEY, static fn ($app): AuthorizationImporter => new AuthorizationImporter(
            $app->make(PassportSupport::WIRE_KEY),
            $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
        ));

        $this->app->singleton(PassportSupport::EXPORTER_KEY, static fn ($app): AuthorizationExporter => new AuthorizationExporter(
            $app->make(PassportSupport::WIRE_KEY),
            $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
        ));

        $this->app->singleton(PassportSupport::PROFILE_KEY, static fn ($app): PassportProfileFetcher => new PassportProfileFetcher(
            $app->make(PassportSupport::WIRE_KEY),
            $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
        ));

        $this->app->singleton(PassportSupport::FORM_KEY, static fn ($app): AuthorizationFormService => new AuthorizationFormService(
            $app->make(PassportSupport::WIRE_KEY),
            $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
        ));

        $this->app->singleton(PassportSupport::WIRE_LOGIN_KEY, function ($app): PassportWireLogin {
            /** @var Repository $config */
            $config = $app->make('config');

            return new PassportWireLogin(
                config: new PassportConfig((array) ($config->get('teleframe.passport') ?? [])),
                importer: $app->make(PassportSupport::IMPORTER_KEY),
                writer: $app->make(PassportSupport::WRITER_KEY),
                confirmer: $app->make(PassportSupport::CONFIRMER_KEY),
                logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                onLogin: self::hostLoginRide($app),
            );
        });
    }

    /**
     * The ANONYMOUS LOGIN capability — the only thing in this module that can
     * create an authenticated session out of nothing but a verified Telegram
     * field. Everything else here ATTACHES an identity to a user who is
     * already authenticated, which is why the shipped routes all sat behind
     * the host's `auth` guard and the callback refused 401 without a session.
     *
     * A DISTINCT CAPABILITY, OFF BY DEFAULT. `teleframe.passport.enabled`
     * stays exactly what it was: a host that turns it on to link Telegram
     * identities to accounts it already manages does NOT thereby let strangers
     * in. `teleframe.passport.anonymous_login` is a separate switch, false by
     * default, and both the service and the HTTP endpoints refuse before
     * touching any state while it is false.
     *
     * Bound BY CLASS NAME, not as a `teleclient.passport.*` seam: those keys
     * are the fixed operator-facing seam list (the CLI status table and the
     * standalone-smoke pin walk it), and this capability is a Laravel-HTTP
     * concern rather than a wire seam an operator is managing. Adding a key
     * to that list is a wire-contract change, not a wiring change.
     *
     * NOTHING DIALS AT RESOLVE. The pending-login store is a PSR-16 cache
     * like the nonce registry, and the key loader only materializes the PEM
     * inside a call.
     */
    private function registerAnonymousLogin(Repository $config): void
    {
        // The pending-login marker store — the anonymous path's session
        // binding. Same seam and same durability story as the nonce registry
        // (teleframe.passport.nonce_store: '' = process-local memory, a store
        // key = the host's PSR-16 cache, wrapped in RedisAtomicCacheStore when
        // it is Redis so a marker's consume is one indivisible GET+DEL).
        //
        // A DIFFERENT KEY PREFIX from the nonce registry, deliberately: the
        // two hold different state with different shapes, and a shared prefix
        // would let a host's flush of "nonces" silently unbind live logins.
        $this->app->singleton(PendingLoginRegistry::class, static function ($app) use ($config): PendingLoginRegistry {
            $storeKey = (string) ($config->get('teleframe.passport.nonce_store') ?? '');
            $ttl = (int) ($config->get('teleframe.passport.nonce_ttl') ?? 900);
            $requireAtomic = (bool) ($config->get('teleframe.passport.require_atomic_nonce') ?? false);

            if ($storeKey === '') {
                return new MemoryPendingLoginRegistry($ttl);
            }

            $store = $app->make(CacheInterface::class);
            if (! $store instanceof CacheInterface) {
                throw new RuntimeException(
                    'teleframe.passport.nonce_store is set but '.CacheInterface::class
                    .' is not bound — bind a PSR-16 store (or clear nonce_store for the memory registry).'
                );
            }

            // Probe the store CLASSES, never a connection.
            $registry = new CachePendingLoginRegistry(
                RedisAtomicCacheStore::supports($store) ? new RedisAtomicCacheStore($store) : $store,
                $ttl,
                'teleframe.passport.pending.',
            );

            if ($requireAtomic && ! $registry->isAtomic()) {
                throw NonAtomicNonceStoreException::forStore($store::class);
            }

            return $registry;
        });

        $this->app->singleton(PassportAnonymousLoginService::class, static function ($app) use ($config): PassportAnonymousLoginService {
            $pending = $app->make(PendingLoginRegistry::class);

            return new PassportAnonymousLoginService(
                config: new PassportConfig((array) ($config->get('teleframe.passport') ?? [])),
                keys: $app->make(PassportSupport::KEY_LOADER_KEY),
                pending: $pending,
                // The SAME writer the authenticated chain uses: one Eloquent
                // touchpoint in the module, so the tg identity lands in the
                // same place whichever flow produced it.
                writer: $app->make(PassportSupport::WRITER_KEY),
                // The verified field selects the user here — the one place in
                // this module where a claim may select rather than confirm.
                // The "must be in Telegram's vetted secure_data" rule and the
                // two compared columns live in the service and its
                // PassportConfig (the single reader of email_column /
                // phone_column); the host's own user class comes from
                // auth.providers.users.model, exactly as the field-mode
                // resolver reads it.
                userClass: (string) ($config->get('auth.providers.users.model') ?? ''),
                logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                // The SAME host-auth ride the authenticated chain uses:
                // Auth::guard()->login($user) + session regenerate, then the
                // host's optional success hook. Reusing it is also the
                // session-fixation defence — the id is rotated on the way in,
                // after the fingerprint comparison has already happened.
                onLogin: self::hostLoginRide($app),
            );
        });
    }

    /**
     * teleclient.passport.service — the PassportLoginService orchestrator
     * (facade accessor), composed from the five seams + the optional PSR-3
     * logger + the host-auth ride closure.
     */
    private function registerService(Repository $config): void
    {
        $this->app->singleton(PassportSupport::SERVICE_KEY, static function ($app) use ($config): PassportLoginService {
            return new PassportLoginService(
                config: new PassportConfig((array) ($config->get('teleframe.passport') ?? [])),
                keys: $app->make(PassportSupport::KEY_LOADER_KEY),
                nonces: $app->make(PassportSupport::NONCES_KEY),
                identity: $app->make(PassportSupport::IDENTITY_KEY),
                writer: $app->make(PassportSupport::WRITER_KEY),
                confirmer: $app->make(PassportSupport::CONFIRMER_KEY),
                logger: $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                onLogin: self::hostLoginRide($app),
            );
        });
    }

    /**
     * The default onLogin hook — the HOST-AUTH RIDE (Cycle-8b): a bare
     * Auth::guard()->login($user) + session regenerate, installed ONLY when a
     * Laravel auth factory is actually bound. Plain-PHP hosts get null and do
     * their own thing in their own onLogin.
     *
     * @param  Container  $app
     */
    private static function hostLoginRide($app): ?\Closure
    {
        if (! $app->bound('auth')) {
            return null;
        }

        $auth = $app->make('auth');
        if (! $auth instanceof AuthFactory) {
            return null;
        }

        return static function (Model $user) use ($app): void {
            $auth = $app->make('auth');
            if (! $auth instanceof AuthFactory) {
                return;
            }

            /** @var StatefulGuard $guard */
            $guard = $auth->guard();
            if ($user instanceof Authenticatable) {
                $guard->login($user);
            }

            if ($app->bound('session')) {
                $session = $app->make('session');
                if (is_object($session) && method_exists($session, 'regenerate')) {
                    $session->regenerate();
                }
            }

            // The host's OPTIONAL success hook (teleclient.passport.on-login)
            // rides last: the session is already the passport user's when it
            // runs, so a host closure can fire an event, sync an avatar or set
            // a remember-me cookie without racing the auth ride. Never
            // required — unbound is the default.
            if ($app->bound(PassportSupport::ON_LOGIN_KEY)) {
                $hook = $app->make(PassportSupport::ON_LOGIN_KEY);
                if ($hook instanceof \Closure) {
                    $hook($user);
                }
            }
        };
    }
}
