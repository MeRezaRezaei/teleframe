<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\App\Scheduler\TemporalAppScheduler;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Laravel\Console\AppCommand;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use RuntimeException;

/**
 * AppServiceProvider — self-contained wiring for the bot/user app framework
 * (design §2/§3/§7): config merge (teleframe.app.*, its own config file),
 * the fixed `teleclient.app.*` seams (registry seeded from
 * teleframe.app.apps, transport callable, null schedule seam), the composed
 * AppRuntime service the facade resolves, and the `teleframe:app` command.
 * Registered via composer.json extra.laravel.providers — deliberately NOT
 * hooked into TeleframeServiceProvider (parallel-session ownership,
 * never-commit).
 *
 * Seeding is lazy inside the registry singleton closure (resolve-time, never
 * register-phase). Config rows map 1:1 onto AppDefinition::define(); an
 * invalid row throws loud at resolve time, never a silent drop (G6 posture).
 *
 * REACHABILITY (2026-09-26): boot() ALSO installs every seeded app's
 * triggers onto the SHIPPED shared HandlerRegistry. The triggers used to be
 * installed only by `teleframe:app register`, whose process exits and takes
 * the static registry with it — so the long-lived daemon that consumes
 * updates never had them and a declarative app never fired. boot() is the one
 * hook every process passes through; it reads config only (no DB, no Redis,
 * no credentials) and is a no-op for an unconfigured host. See
 * installTriggers().
 *
 * The transport seam binds a callable(AppMode): mixed — the default derives
 * from the TeleframeClient container singleton (bot() -> BotClient,
 * user() -> UserAccountScope); a host overrides teleframe.app.transport with
 * its own callable (vault/dc rules, fakes for tests). Unconfigured, the
 * first transport use throws loud through TeleframeClient (never
 * silent-drop).
 *
 * The service is constructible even unconfigured: an empty registry, the
 * null schedule seam ('skipped' posture), and the lazily-throwing transport
 * — so `teleframe:app list|calls` and pre-credential `register()` work
 * before any real credentials exist (TStore posture).
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * The AppRegistry seam (design §7: fixed `teleclient.*` convention; the
     * AppSupport anchor carries SERVICE_KEY/SCHEDULES_KEY/TRANSPORT_KEY, and
     * the registry key stays a literal here until AppSupport grows a
     * REGISTRY_KEY constant — the string is the contract).
     */
    private const REGISTRY_KEY = 'teleclient.app.registry';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/app.php', 'teleframe.app');

        /** @var Repository $config */
        $config = $this->app->make('config');

        $this->registerRegistry($config);
        $this->registerTransport($config);
        $this->registerSchedules($config);
        $this->registerScheduler();
        $this->registerService();
    }

    public function boot(): void
    {
        $this->installTriggers();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/app.php' => config_path('teleframe/app.php'),
        ], 'teleframe-app-config');

        $this->commands([
            AppCommand::class,
        ]);
    }

    /**
     * THE reachability step (2026-09-26 audit fix, BREAK 1).
     *
     * install() used to be reachable ONLY from `teleframe:app register` —
     * a short-lived artisan process whose HandlerRegistry::$instance static
     * dies on exit. The long-lived process that actually consumes updates
     * (teleframe:daemon, the standalone ingest loop) therefore never had the
     * handlers: a declarative app defined the way the docs say was
     * registered, listed, and never fired. A library, not a framework.
     *
     * Provider boot() is the one place EVERY process passes through, so the
     * triggers land on the shared hot table in the daemon, in a queue
     * worker, in a web request and in artisan alike.
     *
     * Cost posture — it must not break a process that has no bus and no
     * credentials:
     *  - the registry is seeded from `teleframe.app.apps` config only: a
     *    pure config read, no DB, no Redis, no dial, no credentials;
     *  - an unconfigured host has an empty app list and the loop body never
     *    runs — the registry is not even resolved;
     *  - registration touches nothing but the in-memory handler table, and
     *    the transport/schedule seams are never resolved here.
     *
     * A malformed config row is a CONFIG ERROR and stays loud: the registry
     * closure already throws, and the refusal is wrapped in the module's own
     * AppException naming the app — never a silent drop.
     *
     * A REFUSED INSTALL IS REPORTED, NOT FATAL (F16-5). install() throws when
     * a trigger could never win the first-match table (AppResponder's
     * refuseShadowed), and the app rows come from a config file a host edits
     * casually. Unhandled, ONE unreachable trigger threw out of boot() — which
     * takes down every Laravel process, `php artisan` included, before any
     * command could run or print why. So each app is installed under its own
     * guard: a refusal is recorded on the responder (AppResponder::refusals(),
     * an AppException naming the app and the trigger), the remaining apps still
     * install, and `teleframe:app list` shows the same sentence. The
     * alternative — swallowing it — is the failure this repo keeps paying to
     * remove, so it is neither silent nor fatal.
     */
    private function installTriggers(): void
    {
        $rows = $this->app->make('config')->get('teleframe.app.apps');
        if (! is_array($rows) || $rows === []) {
            return;
        }

        $runtime = $this->app->make(AppSupport::SERVICE_KEY);
        if (! $runtime instanceof AppRuntime) {
            return;
        }

        foreach ($runtime->registry()->all() as $app) {
            // Idempotent per name: `teleframe:app register` re-installs the
            // same apps in this same process and must not double-register.
            try {
                $runtime->responder()->install($app);
            } catch (InvalidArgumentException) {
                // Already recorded on the responder (AppResponder::refusals()).
                // One app's bad trigger row must not take down the process; the
                // refusal stays visible through `teleframe:app list`.
            }
        }
    }

    /**
     * teleclient.app.registry — AppRegistry seeded from teleframe.app.apps
     * (design §7/G6). Rows map 1:1 onto AppDefinition::define(); an invalid
     * row is a CONFIG ERROR and throws loud at resolve time — never a
     * silent drop. Seeded lazily so an unconfigured install resolves an
     * empty registry with zero cost.
     */
    private function registerRegistry(Repository $config): void
    {
        $this->app->singleton(self::REGISTRY_KEY, static function () use ($config): AppRegistry {
            $registry = new AppRegistry;
            foreach ((array) ($config->get('teleframe.app.apps') ?? []) as $row) {
                if (! is_array($row)) {
                    throw new RuntimeException('teleframe.app.apps rows must be arrays — each row maps 1:1 onto AppDefinition::define().');
                }

                $registry = $registry->define(self::appDefinition($row));
            }

            return $registry;
        });
    }

    /**
     * teleclient.app.transport — callable(AppMode): mixed. The provider
     * default derives from the TeleframeClient container singleton:
     * AppMode::Bot → TeleframeClient::bot() (BotClient), AppMode::User →
     * TeleframeClient::user() (UserAccountScope). A host override in
     * teleframe.app.transport wins verbatim (vault/dc rules per app farm,
     * fakes for tests). Unconfigured credentials throw loud on first use.
     */
    private function registerTransport(Repository $config): void
    {
        $this->app->singleton(AppSupport::TRANSPORT_KEY, static function ($app) use ($config): callable {
            $override = $config->get('teleframe.app.transport');
            if (is_callable($override)) {
                return $override;
            }

            return static function (AppMode $mode) use ($app): mixed {
                $client = $app->make(TeleframeClient::class);
                if (! $client instanceof TeleframeClient) {
                    throw new RuntimeException(AppSupport::TRANSPORT_KEY.' must resolve a '.TeleframeClient::class
                        .' — unconfigured transport throws loud on first use, never silent-drop.');
                }

                return $mode === AppMode::Bot
                    ? $client->bot()
                    : $client->user();
            };
        });
    }

    /**
     * teleclient.app.schedules — the SchedulePublisher seam (design §5/§7):
     * 'null' (the shipped default, probe-only — install() reports
     * 'skipped (null schedule driver)') or 'temporal' (TemporalSchedulePublisher
     * over the app schedules DSN).
     *
     * HONESTY (Cycle 19 audit, kept): 'temporal' prefers a host-bound client
     * verbatim, else the package's own optional SDK-backed client when
     * temporal/sdk is installed. With NEITHER it still refuses at resolve time
     * (naming the class to bind or the package to install) rather than throwing
     * from install() and rather than degrading to a null publisher that would
     * report 'skipped' while the host believes its calls recurred. The binding
     * stays deferred so no container/dial happens at boot (standalone-smoke
     * gate).
     */
    private function registerSchedules(Repository $config): void
    {
        $this->app->singleton(AppSupport::SCHEDULES_KEY, static function ($app) use ($config): SchedulePublisher {
            $driver = (string) ($config->get('teleframe.app.schedules') ?? 'null');

            if ($driver === 'null') {
                return new NullSchedulePublisher;
            }

            if ($driver === 'temporal') {
                // Read the DSN and task queue from the SAME config block as the
                // driver. They were being read from teleframe.tstore.* while the
                // driver itself comes from teleframe.app.*, so a host that set
                // TELEFRAME_APP_SCHEDULES=temporal got a publisher silently
                // pointed at a DSN it never configured - and
                // teleframe.tstore.schedules_task_queue was read but defined
                // nowhere, so it could only ever be the default. A schedule
                // driver that silently ignores its own configuration is worse
                // than one that refuses: the jobs would go somewhere the
                // operator never chose.
                //
                // Same posture as StoreBackupServiceProvider: a host-bound
                // client WINS verbatim (third-party implementors untouched),
                // else TemporalClientResolver supplies the package's own
                // SDK-backed client, and with neither the resolver refuses here
                // at resolve time by name. Deferred binding, no dial: the smoke
                // gate boots no container.
                $dsn = (string) ($config->get('teleframe.app.schedules_dsn') ?? '127.0.0.1:7233');

                // The RESOLVER is container-resolved when the host binds one, so a
                // host (or a test) controls SDK availability deterministically; the
                // default instance builds the SDK-backed client when temporal/sdk is
                // installed and refuses by name when it is not. Environment-shaped
                // behaviour must never decide what a test asserts.
                $resolver = $app->bound(TemporalClientResolver::class)
                    ? $app->make(TemporalClientResolver::class)
                    : new TemporalClientResolver;

                $client = $app->bound(TemporalScheduleClient::class)
                    ? $app->make(TemporalScheduleClient::class)
                    : $resolver->resolve($dsn);

                return new TemporalSchedulePublisher(
                    dsn: $dsn,
                    client: $client,
                    taskQueue: (string) ($config->get('teleframe.app.schedules_task_queue')
                        ?? TemporalSchedulePublisher::defaultTaskQueue()),
                );
            }

            throw new RuntimeException("unknown app schedule driver \"{$driver}\" — expected null|temporal.");
        });
    }

    /**
     * teleclient.app.scheduler — the TemporalAppScheduler thin adapter
     * (wave 3), composed over the EXISTING SchedulePublisher seam.
     */
    private function registerScheduler(): void
    {
        $this->app->singleton(AppSupport::SCHEDULER_KEY, static function ($app): TemporalAppScheduler {
            return new TemporalAppScheduler($app->make(AppSupport::SCHEDULES_KEY));
        });
    }

    /**
     * teleclient.app.service — the AppRuntime orchestrator (facade
     * accessor), composed from the three seams above; its transport is the
     * callable seam (AppRuntime also accepts PSR-3 logger + injected clock,
     * both defaulted). register() fans into the SHIPPED handler pipeline via
     * AppResponder; runCall() resolves the transport at call time.
     */
    private function registerService(): void
    {
        $this->app->singleton(AppSupport::SERVICE_KEY, static function ($app): AppRuntime {
            return new AppRuntime(
                registry: $app->make(self::REGISTRY_KEY),
                schedules: $app->make(AppSupport::SCHEDULES_KEY),
                transport: $app->make(AppSupport::TRANSPORT_KEY),
            );
        });
    }

    /**
     * One teleframe.app.apps row → AppDefinition (design §7: rows map 1:1
     * onto define()'s name/mode/triggers/calls). Trigger rows and call rows
     * are the same data rows the facade takes. Any malformed row throws
     * loud — the row IS a decision, a bad one is a config error.
     *
     * @param  array<string, mixed>  $row
     */
    private static function appDefinition(array $row): AppDefinition
    {
        $name = (string) ($row['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('teleframe.app.apps row is missing a name — each row maps 1:1 onto AppDefinition::define(name, mode, triggers, calls).');
        }

        $mode = AppMode::tryFrom((string) ($row['mode'] ?? ''));
        if ($mode === null) {
            throw new RuntimeException("teleframe.app.apps row \"{$name}\" has an invalid mode \"".(string) ($row['mode'] ?? '').'" — expected bot|user.');
        }

        $triggers = [];
        foreach ((array) ($row['triggers'] ?? []) as $triggerRow) {
            if (! is_array($triggerRow)) {
                throw new RuntimeException("teleframe.app.apps trigger row for \"{$name}\" must be an array (match/handler/priority/on_own).");
            }

            $handler = $triggerRow['handler'] ?? null;
            if (! is_string($handler) && ! is_array($handler) && ! $handler instanceof \Closure) {
                throw new RuntimeException("teleframe.app.apps trigger row for \"{$name}\" needs a handler — PSR-11 invokable string, [Class::class, 'method'], or closure (never a session string).");
            }

            $triggers[] = AppTrigger::on(
                (string) ($triggerRow['match'] ?? ''),
                $handler,
                (int) ($triggerRow['priority'] ?? 0),
                (bool) ($triggerRow['on_own'] ?? false),
            );
        }

        $calls = [];
        foreach ((array) ($row['calls'] ?? []) as $callRow) {
            if (! is_array($callRow)) {
                throw new RuntimeException("teleframe.app.apps call row for \"{$name}\" must be an array (name/interval_seconds/method/params).");
            }

            $calls[] = AppCall::every(
                (string) ($callRow['name'] ?? ''),
                (int) ($callRow['interval_seconds'] ?? 0),
                (string) ($callRow['method'] ?? ''),
                (array) ($callRow['params'] ?? []),
            );
        }

        return AppDefinition::define($name, $mode, $triggers, $calls);
    }
}
