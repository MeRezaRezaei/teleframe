<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\FileWatchAlertSink;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunIndexContract;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseBackupService;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpResult;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use MeRezaRezaei\Teleframe\Store\Backup\EloquentBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Backup\MysqlDumper;
use MeRezaRezaei\Teleframe\Store\Backup\PostgresDumper;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\StoreService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

/**
 * StoreBackupServiceProvider — self-contained wiring for the store backup
 * core (design §2/§5/§7): config merge (teleframe.store.backup.*, its own
 * config file), the seven fixed `teleclient.store.backup.*` seams (five
 * wave-1 + TARGETS_KEY/SCHEDULER_KEY wave-2-D), the boot-time
 * backup-heartbeat watchdog registration, and the `backup` / `schedule`
 * actions on teleframe:tstore. Registered via composer.json
 * extra.laravel.providers — deliberately NOT hooked into
 * TeleframeServiceProvider (parallel-session ownership, never-commit).
 *
 * Wave-2-D decision (recorded): peer_type surfacing needs NO
 * StoreService/ChannelRegistry edits here — buckets()/run rows already
 * carry peer_type (the column shipped with
 * migration 2026_09_24_000100; EloquentBackupRunIndex reads it) and the
 * run ledger stores what the orchestrator hands in. The design de-scoped
 * the TFREGISTRY1 registry-payload peer_type gain; this provider does not
 * re-open it.
 *
 * Wave-2-D decision (recorded): PeerMembership is deliberately NOT a
 * container seam — the design §7 bind-key block lists no member key, and
 * a UserAccountScope is a LIVE per-account object (a session-backed MTProto
 * client); resolving one at provider boot would materialize credentials,
 * which the package never does at wiring time. Callers compose
 * PeerMembership at the call site with the scope they already hold (the
 * RelayTerminal bound-client idiom); the class's ctor accepts a NULL scope
 * and throws BackupException at FIRST USE, never at construction, so a
 * future null-safe binding remains possible without ever fataling bind
 * time (PeerMembership.php RULING).
 *
 * The service is constructible even unconfigured: the dumper seam binds a
 * dumper that THROWS loud at dump() time when no database driver is
 * configured (no silent no-op), the run ledger falls back to memory, the
 * watchdog stays null-safe, schedules default to null — so `schedule`
 * scaffolding and `backup() -> BackupException` work before any real
 * credentials exist (TStore posture). TARGETS_KEY binds null until the
 * wave-2-A sibling (PeerTargetRegistry) lands — the key is fixed so the
 * registry lights up the moment the class ships (the binding closure runs
 * a class_exists probe, never fatals on the absent class).
 */
final class StoreBackupServiceProvider extends ServiceProvider
{
    /**
     * The package-owned dump trigger's default time of day. Deliberately NOT
     * the file-vault schedule's 03:17 (TeleframeServiceProvider:932-935) so a
     * host running both gets two dumps in two separate minutes, not two full
     * database dumps on the same one.
     */
    private const DEFAULT_DUMP_TIME = '03:47';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/store-backup.php', 'teleframe.store.backup');

        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        $this->registerDumper($config);
        $this->registerRunIndex();
        $this->registerWatchdog($config);
        $this->registerUploadQueue($config);
        $this->registerSchedules($config);
        $this->registerTargets($config);
        $this->registerScheduler($config);
        $this->registerService($config);
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/config/store-backup.php' => config_path('teleframe/store/backup.php'),
        ], 'teleframe-store-backup-config');

        $this->commands([
            TstoreCommand::class,
        ]);

        $this->scheduleBackupWatchdogCheck();
        $this->scheduleBackupDump();
    }

    /**
     * teleclient.store.backup.dumper — the DatabaseDumper for the
     * configured driver. RULING: driver '' (or unknown) binds a tiny
     * dumper that throws BackupException at dump() time — the service
     * still constructs (schedule status, ledger) and backup() fails loud,
     * never a silent no-op.
     */
    private function registerDumper(ConfigRepository $config): void
    {
        $this->app->bind(BackupSupport::DUMPER_KEY, static function () use ($config): DatabaseDumper {
            $driver = (string) ($config->get('teleframe.store.backup.database.driver') ?? '');

            if ($driver === 'mysql') {
                return new MysqlDumper;
            }

            if ($driver === 'pgsql') {
                return new PostgresDumper;
            }

            return new class implements DatabaseDumper
            {
                public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
                {
                    throw new BackupException(
                        'no database driver configured — set teleframe.store.backup.database.driver to mysql|pgsql '
                        .'(or env TELEFRAME_STORE_BACKUP_DB_DRIVER) before running a backup.',
                    );
                }
            };
        });
    }

    /**
     * teleclient.store.backup.index — the BackupRunIndexContract behind
     * the run ledger. Deferred closure: the table probe runs at resolve
     * time, so a boot without the migrated tf_store_backup_runs table (or
     * without any DB seam) falls back to the memory index, never fatals.
     */
    private function registerRunIndex(): void
    {
        $this->app->bind(BackupSupport::INDEX_KEY, static function (): BackupRunIndexContract {
            try {
                if (class_exists('Illuminate\Support\Facades\Schema') && Schema::hasTable('tf_store_backup_runs')) {
                    return new EloquentBackupRunIndex;
                }
            } catch (Throwable) {
                // No DB seam / probe failed — memory ledger, never fatal.
            }

            return new MemoryBackupRunIndex;
        });
    }

    /**
     * teleclient.store.backup.watchdog — ?NoShowWatchdog (heartbeat on the
     * 'backup' topic after every successful dump; a null watchdog = no-op,
     * unconfigured-safe). Built from WatchState over a shared PSR-16 store:
     * the container's CacheInterface binding when present, else the host's
     * own cache store, else a fresh InMemoryCache.
     *
     * THE ALERT SEAMS ARE NOT OPTIONAL (the defect this fixes). This binding
     * used to be `new NoShowWatchdog(new WatchState($cache, …))` — no notify
     * queue, no logger, no registration — which made emit() return before it
     * ever reached enqueue(). A watchdog that cannot report its own silence
     * is decoration: the heartbeat was recorded durably on every successful
     * dump and NOTHING could ever alert on it. Each seam is now resolved
     * defensively and independently, so one missing binding degrades a single
     * capability instead of nulling the whole watchdog (a null watchdog would
     * stop the heartbeat itself).
     *
     * THE REGISTRATION IS DERIVED, NOT HELD (process boundary). The 'backup'
     * row comes from a source closure re-read on every check() — backed by
     * the same teleframe.store.backup.schedule.* spec the schedule install
     * reads — so a check in a different process than the dump watches exactly
     * what the schedule says it watches. A second durable copy of the
     * expectation (a persisted registration list) would only be able to
     * disagree with the schedule; deriving cannot.
     */
    private function registerWatchdog(ConfigRepository $config): void
    {
        $this->app->singleton(BackupSupport::WATCHDOG_KEY, static function ($app) use ($config): ?NoShowWatchdog {
            try {
                $cache = self::watchStateStore($app);

                $notify = null;
                try {
                    $queue = $app->bound(FacadeSupport::QUEUE_KEY) ? $app->make(FacadeSupport::QUEUE_KEY) : null;
                    $notify = $queue instanceof NotificationQueueContract ? $queue : null;
                } catch (Throwable) {
                    // No notify provider installed / unknown driver: the
                    // watchdog still watches and still logs, it just cannot
                    // enqueue (emit() is null-safe by design).
                }

                $logger = null;
                try {
                    $resolved = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;
                    $logger = $resolved instanceof LoggerInterface ? $resolved : new NullLogger;
                } catch (Throwable) {
                    $logger = new NullLogger;
                }

                return new NoShowWatchdog(
                    state: new WatchState($cache, 'teleframe.notify.watch.'),
                    notify: $notify,
                    logger: $logger,
                    registrationSource: self::backupWatchSource($config),
                    alertSink: new FileWatchAlertSink(self::alertLogPath($config, $app)),
                );
            } catch (Throwable) {
                return null;
            }
        });

        // ONE watchdog, two names: producers and checkers must meet on the
        // same object over the same durable state.
        $this->app->alias(BackupSupport::WATCHDOG_KEY, FacadeSupport::WATCHDOG_KEY);
    }

    /**
     * Where the durable no-show record is written (F8).
     *
     * '' resolves to storage/teleframe/watchdog-alerts.jsonl under the host
     * application, and only when there is no application base path (a bare
     * console, the standalone gate) to the system temp directory. Resolution
     * happens per RESOLVE of the watchdog singleton, so a host that changes the
     * key and re-resolves gets the new path — nothing is captured at boot.
     */
    private static function alertLogPath(ConfigRepository $config, mixed $app): string
    {
        $configured = trim((string) ($config->get('teleframe.store.backup.watchdog.alert_log') ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        if (is_object($app) && method_exists($app, 'basePath')) {
            try {
                return $app->basePath('storage'.DIRECTORY_SEPARATOR.'teleframe')
                    .DIRECTORY_SEPARATOR.'watchdog-alerts.jsonl';
            } catch (Throwable) {
                // Fall through to the temp-directory posture.
            }
        }

        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'teleframe-watchdog-alerts.jsonl';
    }

    /**
     * The PSR-16 store the heartbeat survives in. Laravel binds
     * Psr\SimpleCache\CacheInterface on most installs; a host that binds only
     * the cache repository is still served (its store() is a PSR-16 store);
     * with neither, an InMemoryCache keeps the module constructible — the
     * heartbeat then lives only for the process, which is the documented
     * unconfigured posture, never a fatal.
     */
    private static function watchStateStore(mixed $app): CacheInterface
    {
        if ($app->bound(CacheInterface::class)) {
            $cache = $app->make(CacheInterface::class);
            if ($cache instanceof CacheInterface) {
                return $cache;
            }
        }

        if ($app->bound('cache')) {
            try {
                $repository = $app->make('cache');
                if ($repository instanceof Repository && method_exists($repository, 'store')) {
                    $store = $repository->store();
                    if ($store instanceof CacheInterface) {
                        return $store;
                    }
                }
            } catch (Throwable) {
                // Fall through to the in-memory posture.
            }
        }

        return new InMemoryCache;
    }

    /**
     * THE MASTER SWITCH, READ ONCE, HONESTLY.
     *
     * teleframe.store.backup.enabled used to be read at two sites and folded
     * into `enabled = master || schedule.enabled`; the callers then gated on
     * `! enabled || ! schedule.enabled` (backupWatchSource) and on
     * `! enabled` (scheduleBackupWatchdogCheck). `!(A||B) || !B` is `!B`, so
     * the master term was ABSORBED: with the shipped schedule.enabled=true,
     * TELEFRAME_STORE_BACKUP_ENABLED=false skipped nothing. The key WAS read,
     * which is why grep called it healthy — the class a grep cannot see.
     *
     * There is now ONE reader of this key, it returns the key's own value, and
     * both call sites gate on it. It stays AND, never OR: `false` must be able
     * to turn the module's watchdog OFF, and `A || B` is the shape that cannot
     * do that.
     */
    private static function masterEnabled(ConfigRepository $config): bool
    {
        return (bool) ($config->get('teleframe.store.backup.enabled') ?? false);
    }

    /**
     * The DERIVED 'backup' registration: topic ⇒ expect-every, read fresh on
     * every check() (design §5). Gated by the master switch AND the schedule
     * switch, and the expect window is interval ×
     * watchdog_expect_multiplier with a 3600 s floor (fresh-install grace) —
     * at the shipped multiplier of 2 this is exactly
     * BackupScheduleSpec::watchdogInterval(), which is the same window the
     * backup-watchdog-check schedule polls at.
     *
     * @return \Closure(): array<string, int>
     */
    private static function backupWatchSource(ConfigRepository $config): \Closure
    {
        return static function () use ($config): array {
            $scheduled = (bool) ($config->get('teleframe.store.backup.schedule.enabled') ?? false);

            if (! self::masterEnabled($config) || ! $scheduled) {
                return [];
            }

            $interval = (int) ($config->get('teleframe.store.backup.schedule.interval_seconds') ?? 86400);
            $multiplier = (int) ($config->get('teleframe.store.backup.schedule.watchdog_expect_multiplier') ?? 2);

            return ['backup' => max(3600, $interval * max(1, $multiplier))];
        };
    }

    /**
     * teleframe.store.backup.queue — the upload-queue driver the BACKUP runs
     * on, wiring the key that shipped declared-and-unread (Cycle 19: "the
     * queue driver is dead config too").
     *
     * THE UPLOAD QUEUE IS ONE SEAM, NOT TWO. A backup dump is uploaded by the
     * very StoreService the tstore seam builds (registerService() makes
     * TstoreCommand::SERVICE_KEY), and StoreService takes the
     * UploadQueueContract in its constructor — so the backup module cannot
     * own a private queue: there is exactly one queue, and the key decides
     * which driver builds it.
     *
     * PRECEDENCE (so a host's existing tstore config never changes by
     * accident):
     *   - '' | 'sync' — the shipped default: DEFER. teleframe.tstore.queue
     *     keeps deciding the driver, exactly as before this key was read.
     *   - 'amqp' — the backup module names a driver: rebind
     *     teleclient.tstore.queue so this driver builds the shared queue
     *     (broker options stay the tstore ones — teleframe.tstore.amqp.*).
     *   - anything else — LOUD RuntimeException, at RESOLVE time (never at
     *     register: the binding is deferred, so a boot without the seam ever
     *     being made is unaffected). Silently degrading to sync would leave
     *     the host believing backups are async while a dump uploads inline
     *     on the web request that triggered it.
     */
    private function registerUploadQueue(ConfigRepository $config): void
    {
        $driver = strtolower(trim((string) ($config->get('teleframe.store.backup.queue') ?? 'sync')));

        if ($driver === '' || $driver === 'sync') {
            return;
        }

        $this->app->bind(TstoreCommand::QUEUE_KEY, static function () use ($config, $driver): UploadQueueContract {
            if ($driver === 'amqp') {
                return new AmqpUploadQueue([
                    'host' => (string) ($config->get('teleframe.tstore.amqp.host') ?? '127.0.0.1'),
                    'port' => (int) ($config->get('teleframe.tstore.amqp.port') ?? 5672),
                    'vhost' => (string) ($config->get('teleframe.tstore.amqp.vhost') ?? '/'),
                    'user' => (string) ($config->get('teleframe.tstore.amqp.user') ?? 'guest'),
                    'pass' => (string) ($config->get('teleframe.tstore.amqp.password') ?? 'guest'),
                ]);
            }

            throw new RuntimeException(
                "unknown teleframe.store.backup.queue driver \"{$driver}\" - expected sync|amqp."
            );
        });
    }

    /**
     * teleclient.store.backup.schedules — SchedulePublisher seam, null by
     * default (design §5): the one-line cron posture. 'temporal' uses a
     * host-bound client when there is one, else the package's own optional
     * SDK-backed client.
     *
     * HONESTY (Cycle 19 audit, kept): with NO host binding and NO temporal/sdk
     * installed, 'temporal' REFUSES at resolve time naming the class to bind or
     * the package to install. It never silently reports success, and it never
     * degrades to NullSchedulePublisher — a silently disabled backup schedule
     * while the host believes it is armed is the worst available failure.
     * Binding stays deferred: no container, no dial at boot (standalone-smoke
     * gate).
     */
    private function registerSchedules(ConfigRepository $config): void
    {
        $this->app->singleton(BackupSupport::SCHEDULES_KEY, static function ($app) use ($config): SchedulePublisher {
            // Cycle 19: this key was declared in config since it shipped
            // ('null' (off) or 'temporal') and read by NOTHING, while the
            // binding hardcoded NullSchedulePublisher. So install() could only
            // ever report 'skipped (null schedule driver)' and the recurring
            // database dump never fired itself - the config promised a schedule
            // the code could not produce. The App module received exactly this
            // wiring first (AppServiceProvider reads teleframe.app.schedules),
            // so the asymmetry was arbitrary rather than a design ruling.
            $driver = (string) ($config->get('teleframe.store.backup.schedule.publisher') ?? 'null');

            if ($driver === 'null') {
                return new NullSchedulePublisher;
            }

            if ($driver === 'temporal') {
                // REFUSE AT CONFIG TIME, NOT DEEP INSIDE install(). The Cycle 19
                // claim — "client injected and lazy so the smoke gate still
                // constructs it" — described the class, never this wiring: this
                // branch built a publisher with NO client, the publisher never
                // builds one either, and the only TemporalScheduleClient
                // implementor in the tree was an anonymous class in a test. So
                // driver 'temporal' had exactly one reachable outcome: the
                // no-client throw, and the recurring dump never fired itself.
                //
                // NOW: the client comes from TemporalClientResolver — a
                // host-bound client WINS verbatim (every existing third-party
                // implementor keeps working untouched), and with no host
                // binding the resolver hands back the package's own
                // SDK-backed client when temporal/sdk is installed. With
                // neither, the resolver throws naming both — the refusal
                // STAYS at resolve time, the earliest point where the host's
                // bindings are visible. Nothing dials: the binding remains
                // deferred and the standalone-smoke gate constructs every
                // module with no container booted.
                $dsn = (string) ($config->get('teleframe.store.backup.schedule.dsn') ?? '127.0.0.1:7233');

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
                    taskQueue: (string) ($config->get('teleframe.store.backup.schedule.task_queue')
                        ?? TemporalSchedulePublisher::defaultTaskQueue()),
                );
            }

            // Unknown driver: LOUD. Silently falling back to 'null' would
            // disable the backup schedule while the host believes it is armed -
            // the worst outcome for a backup, because nothing fails.
            throw new RuntimeException(
                "unknown teleframe.store.backup.schedule.publisher \"{$driver}\" - expected null|temporal."
            );
        });
    }

    /**
     * teleclient.store.backup.targets — ?PeerTargetRegistry built from
     * teleframe.store.backup.targets (wave-2-D config rows; design §6),
     * falling back to the singular teleframe.store.backup.target block
     * when the list is empty (back-compat: the 'default' row). Deferred
     * class_exists probe: the wave-2-A sibling (PeerTargetRegistry) is not
     * guaranteed to be on disk yet, so until it lands the seam binds null
     * and the key stays FIXED — the registry lights up the moment the
     * class ships.
     *
     * ADAPTED TO THE REAL CTOR (peer-id gate): the wave-2-A
     * PeerTargetRegistry throws BackupException on ANY row without a peer
     * id (its documented config-error ruling, PeerTargetRegistry.php:
     * "a row without a bucket or a peer id ... is a CONFIG ERROR").
     * The owned-channel default — the singular 'default' row WITHOUT
     * peer_id, the channel acquired via createChannel — is representable
     * by DatabaseBackupService (bucket + passphrase), NOT by a registry
     * row, so the fallback only engages when the singular block carries a
     * peer identity; otherwise the registry binds empty (no configured
     * target rows, owned-channel posture governs). targetRows() therefore
     * returns exactly the rows the registry can map.
     *
     * @return list<array<string, mixed>>
     */
    private static function targetRows(ConfigRepository $config): array
    {
        $rows = $config->get('teleframe.store.backup.targets');
        if (is_array($rows) && $rows !== []) {
            return array_values($rows);
        }

        // Fall back to the singular target block (the wave-1 'default'
        // row: bucket/peer_type/passphrase/mode/members) — only when it
        // carries a peer identity, so the mapped row is valid by the
        // wave-2-A contract (see docblock).
        $singular = $config->get('teleframe.store.backup.target');
        if (! is_array($singular) || $singular === []) {
            return [];
        }

        $peerId = $singular['peer_id'] ?? $singular['peerId'] ?? null;
        if ($peerId === null) {
            return [];
        }

        return [$singular];
    }

    private function registerTargets(ConfigRepository $config): void
    {
        $this->app->singleton(BackupSupport::TARGETS_KEY, static function () use ($config): mixed {
            $rows = self::targetRows($config);
            if ($rows === []) {
                return null;
            }

            // String literal on purpose: the class may not be loadable
            // yet (wave-2-A sibling) — class_exists probes, never fatals.
            // ctor shape: PeerTargetRegistry(array $rows, ?callable
            // $classify) — matches the real class exactly.
            $registryClass = 'MeRezaRezaei\\Teleframe\\Store\\Backup\\PeerTargetRegistry';
            if (! class_exists($registryClass)) {
                return null;
            }

            return new $registryClass($rows);
        });
    }

    /**
     * teleclient.store.backup.scheduler — the TemporalBackupScheduler thin
     * adapter (wave-2-D), composed over the EXISTING SchedulePublisher
     * seam + the config-built BackupScheduleSpec: install() hands the
     * spec's {backup-dump, backup-watchdog-check} payload straight through
     * the publisher (null default → 'skipped (null schedule driver)'), so
     * a host enables Temporal simply by rebinding
     * teleclient.store.backup.schedules to its TemporalSchedulePublisher.
     */
    private function registerScheduler(ConfigRepository $config): void
    {
        $this->app->singleton(BackupSupport::SCHEDULER_KEY, static function ($app) use ($config): TemporalBackupScheduler {
            $interval = (int) ($config->get('teleframe.store.backup.schedule.interval_seconds') ?? 86400);
            $dsn = (string) ($config->get('teleframe.store.backup.schedule.dsn') ?? '');

            return new TemporalBackupScheduler(
                $app->make(BackupSupport::SCHEDULES_KEY),
                new BackupScheduleSpec($interval, $dsn),
            );
        });
    }

    /**
     * teleclient.store.backup.service — the DatabaseBackupService
     * orchestrator, built from the four seams above + the tstore store
     * seam + config (accountId, bucket, passphrase). The config-built
     * default DatabaseTarget (teleframe.store.backup.database.*) is bound
     * under its own class name: the core stays config-free (B3 RULING:
     * backup(null) throws — "the Laravel provider builds the default
     * target from config and hands it in"), so the CLI command resolves
     * the same target to pass into backup().
     */
    private function registerService(ConfigRepository $config): void
    {
        $this->app->bind(DatabaseTarget::class, static function () use ($config): DatabaseTarget {
            $dbConfig = (array) ($config->get('teleframe.store.backup.database') ?? []);

            return new DatabaseTarget(
                driver: (string) ($dbConfig['driver'] ?? ''),
                database: (string) ($dbConfig['database'] ?? ''),
                host: (string) ($dbConfig['host'] ?? '127.0.0.1'),
                port: (int) ($dbConfig['port'] ?? 0),
                user: (string) ($dbConfig['user'] ?? ''),
                socket: (string) ($dbConfig['socket'] ?? ''),
                tables: isset($dbConfig['tables']) && is_array($dbConfig['tables']) ? $dbConfig['tables'] : null,
            );
        });

        $this->app->singleton(BackupSupport::SERVICE_KEY, static function ($app) use ($config): DatabaseBackupService {
            $store = $app->make(TstoreCommand::SERVICE_KEY);
            if (! $store instanceof StoreService) {
                throw new RuntimeException(TstoreCommand::SERVICE_KEY.' must bind a StoreService — the store backup service builds on the tstore seam.');
            }

            $targetConfig = (array) ($config->get('teleframe.store.backup.target') ?? []);

            $accountId = (int) ($targetConfig['account_id'] ?? 0);
            if ($accountId <= 0) {
                $accountId = (int) ($config->get('teleframe.tstore.account_id') ?? 0);
            }

            $bucket = ChannelRegistry::PREFIX_BACKUP.(string) ($targetConfig['bucket'] ?? 'default');

            return new DatabaseBackupService(
                accountId: $accountId,
                dumper: $app->make(BackupSupport::DUMPER_KEY),
                store: $store,
                runs: $app->make(BackupSupport::INDEX_KEY),
                watchdog: $app->make(BackupSupport::WATCHDOG_KEY),
                passphrase: (string) ($targetConfig['passphrase'] ?? ''),
                bucket: $bucket,
                spoolDir: (string) ($config->get('teleframe.store.backup.spool_dir') ?? ''),
            );
        });
    }

    /**
     * Schedule the no-show CHECK — the registration itself is derived now
     * (see backupWatchSource()), so all that is left is deciding WHEN the
     * check runs.
     *
     * It used to be register()'d here, in a CONSOLE-ONLY boot, into an
     * in-memory array. That is a registration no other process could ever
     * see, which is why the owner's own example — "a database backup did
     * not come in time" — could not fire. The check now runs on Laravel's
     * scheduler, hourly: the cadence BackupScheduleSpec's
     * backup-watchdog-check schedule uses (min(interval × 2, 3600), i.e. at
     * least hourly) so an overdue heartbeat is caught inside its own window
     * for a daily dump, and never polled faster than hourly.
     *
     * Deliberately gated on the MASTER switch alone, not on
     * `master || schedule.enabled`: the check is what notices a MISSING dump,
     * and a host whose publisher is 'null' dumps from cron (the shipped
     * one-line-cron posture) while still wanting the no-show alert — so
     * schedule.enabled must not decide this. The master switch does, and
     * `false` now genuinely silences it (it did not before: see
     * masterEnabled()). A host WITH Temporal maps the
     * `teleclient.notify.check-no-show` activity onto the same command; a
     * host without any scheduler runs `php artisan teleframe:notify watch
     * --check` from cron. Registered as the command STRING (not a closure),
     * so `schedule:run` forks one process per tick instead of running a
     * watchdog check inside the scheduler's own process.
     */
    private function scheduleBackupWatchdogCheck(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        if (! self::masterEnabled($config)) {
            return;
        }

        if (! $this->app->bound(Schedule::class)) {
            return; // no scheduler in this process (a bare console, a worker)
        }

        try {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('teleframe:notify watch --check')->hourly()->withoutOverlapping();
        } catch (Throwable) {
            // A host without the scheduling surface still gets the check from
            // Temporal, from cron, or by hand — never a boot failure.
        }
    }

    /**
     * THE PACKAGE-OWNED DUMP TRIGGER (F7, 2026-10-13).
     *
     * Before this, the DB dump was the ONE capability in the package with no
     * trigger the package owned: `schedule.publisher` defaults to 'null', so on
     * a default install nothing in the source tree ever fired
     * `teleframe:tstore backup`, the documented posture was a hand-written cron
     * line, and meanwhile the no-show watchdog — armed by default — reported
     * that run missing, forever, on exactly the hosts that had followed the
     * shipped instructions. The operator named both halves: "so it can simply
     * dump the laravel database and make a backup from it in schedules" and "it
     * can notify if something did not come into a log channel".
     *
     * With this flag on, the package registers the dump on Laravel's own
     * scheduler, so a host runs ONE package-owned command — `php artisan
     * schedule:run`, which it already needs for the hourly no-show check
     * registered above — and writes no cron at all.
     *
     * IT IS DEFAULT-OFF, and it has to be. The two possible triggers cannot see
     * each other: the shipped one-line cron posture is invisible to the Laravel
     * scheduler, and no config key can report it, so the package cannot prove
     * that defaulting this on cannot double-fire against a crontab a host
     * already has. Two dumps a day into the same bucket with two run-ledger
     * rows each is a real cost, and a package that guesses about it is worse
     * than one that asks the host to delete a line. `withoutOverlapping()` and
     * `onOneServer()` harden the trigger against the package's own scheduler
     * and a second app server; neither can see cron. The key's config comment
     * carries the same ruling, because a comment is what the next reader reads.
     *
     * NOT GATED ON THE MASTER SWITCH, on purpose: `enabled` is documented as
     * governing the WATCHDOG and explicitly not the dump ("WHAT false DOES NOT
     * DO … it does not disable the dump"), so gating the dump on it would
     * repurpose a switch whose meaning is pinned by tests. This trigger reads
     * its own key and nothing else.
     *
     * Registered as a command STRING, like the check above, so `schedule:run`
     * forks one process per tick instead of dumping inside the scheduler's own
     * process; and at 03:47 rather than the file-vault sibling's 03:17 so a
     * host running both does not stack two database dumps on one minute.
     */
    private function scheduleBackupDump(): void
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        if (! (bool) ($config->get('teleframe.store.backup.schedule.package_schedule.enabled') ?? false)) {
            return;
        }

        if (! $this->app->bound(Schedule::class)) {
            return; // no scheduler in this process (a bare console, a worker)
        }

        // Validated OUTSIDE the catch below, on purpose. The catch exists so a
        // host without the scheduling surface is never a boot failure; a
        // misspelled time must NOT be swallowed into "registered at some other
        // time" or "not registered at all" — both are a backup the host
        // believes is armed and is not. It throws by name, at console boot,
        // where the operator is running a command and sees the error.
        $dailyAt = self::dailyAtOrThrow($config);

        try {
            $this->app->make(Schedule::class)
                ->command('teleframe:tstore backup')
                ->dailyAt($dailyAt)
                ->withoutOverlapping()
                ->onOneServer();
        } catch (Throwable) {
            // No scheduling surface in this process: the dump is then whatever
            // the host already runs — Temporal, cron, or by hand. Never a boot
            // failure, exactly like the no-show check above.
        }
    }

    /**
     * teleframe.store.backup.schedule.package_schedule.daily_at as an H:i
     * string, or a named throw. Zero regex: length, the separator position and
     * ctype_digit() are the whole grammar, and the ranges are checked rather
     * than assumed (so '99:99' is refused too).
     *
     * @throws InvalidArgumentException When the value is not a 24-hour H:i time.
     */
    private static function dailyAtOrThrow(ConfigRepository $config): string
    {
        $value = trim((string) ($config->get('teleframe.store.backup.schedule.package_schedule.daily_at') ?? ''));

        if ($value === '') {
            return self::DEFAULT_DUMP_TIME;
        }

        $valid = strlen($value) === 5
            && $value[2] === ':'
            && ctype_digit(substr($value, 0, 2))
            && ctype_digit(substr($value, 3, 2))
            && (int) substr($value, 0, 2) <= 23
            && (int) substr($value, 3, 2) <= 59;

        if (! $valid) {
            throw new InvalidArgumentException(
                'teleframe.store.backup.schedule.package_schedule.daily_at must be a 24-hour "H:i" time'
                .' (00:00-23:59), got "'.$value.'". The package-owned dump trigger refuses to register'
                .' at a time you did not ask for.',
            );
        }

        return $value;
    }
}
