<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Redis\RedisManager;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\EloquentCursorStore;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\TelegramVault;
use MeRezaRezaei\Teleframe\Backup\VaultInterface;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Subscriptions\UpdateStoredHandler;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\AccountRouteSeeder;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\RoutingSettingsObserver;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\BackupCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\CluesCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\DoctorCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestGateCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\LoginCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\PollCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RegenerateCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\ReindexSearchCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RelayCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\RoutingModeCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAccountCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultAddAppCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultListCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\VaultUseDefaultCommand;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramSessionRevoked;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\TelegramWebController;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\TelegramWebhookController;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\RequireTelegramUserSession;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\ScopeTelegramReadOnly;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\VerifyMiniAppInitData;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Realtime\UpdateStoredCentrifugoListener;
use MeRezaRezaei\Teleframe\Laravel\Realtime\UpdateStoredTstoreListener;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayReadOnlyGrants;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayVaultTerminalFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RoutingPeerResolver;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\Store\AwsSdkS3Gateway;
use MeRezaRezaei\Teleframe\Laravel\Store\MinioHotCache;
use MeRezaRezaei\Teleframe\Laravel\Store\S3DurableVault;
use MeRezaRezaei\Teleframe\Laravel\Store\S3GatewayContract;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\HttpCentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\PresenceFeed;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Store\Backup\PeerTarget;
use MeRezaRezaei\Teleframe\Store\Cache\HotCacheContract;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\EloquentStoreIndex;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Index\StoreIndexContract;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Store\TelegramStoreChannel;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;
use MeRezaRezaei\Teleframe\Vault\Vault;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

class TeleframeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/teleframe.php', 'teleframe');

        $this->app->singleton(AccountContext::class, static fn (): AccountContext => new AccountContext);
        $this->app->singleton(SchemaRegenerator::class);
        $this->app->singleton(UpdateIngestor::class, static fn ($app): UpdateIngestor => new UpdateIngestor(
            events: $app->make(Dispatcher::class),
        ));
        $this->app->singleton(EntityAggregator::class);

        $this->app->singleton(HandlerRegistry::class, static fn (): HandlerRegistry => HandlerRegistry::instance());

        // PSR-3 logging seam (gap #1 closed 2026-09-09): a host-registered
        // Psr\Log\LoggerInterface binding wins; otherwise honor the
        // teleframe.logging.logger FQCN from config; otherwise stay silent
        // with a NullLogger. The engine never requires a concrete logger.
        $this->app->singleton(LoggerInterface::class, static function ($app): LoggerInterface {
            /** @var ConfigRepository $config */
            $config = $app->make('config');
            $class = (string) $config->get('teleframe.logging.logger', '');

            if ($class !== '' && class_exists($class)) {
                $logger = new $class;

                if ($logger instanceof LoggerInterface) {
                    return $logger;
                }
            }

            return new NullLogger;
        });

        $this->app->singleton(UpdateDispatcher::class, static function ($app): UpdateDispatcher {
            $sends = $app->bound(CacheInterface::class)
                ? $app->make(CacheInterface::class)
                : new InMemoryCache;

            return new UpdateDispatcher(
                $app->make(HandlerRegistry::class),
                new Pipeline,
                $app,
                $sends,
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->singleton(Teleframe::class, static fn ($app): Teleframe => new Teleframe($app));
        $this->app->singleton(RoutingPeerResolver::class, static fn ($app): RoutingPeerResolver => new RoutingPeerResolver(
            $app->make('db')->connection(),
        ));

        // Routing settings observer (verbatim's settings-change event loop):
        // best-effort Redis-2 cache — a host with no working bus redis still
        // gets the observer (and the RoutingSettingsChanged event) and keeps
        // DB-backed routing correct; only the hot-path cache refresh is
        // skipped. Registered on the UpdateRoutingRule Eloquent lifecycle in
        // boot() so a default install never serves stale routing rules.
        $this->app->singleton(RoutingSettingsObserver::class, static function ($app): RoutingSettingsObserver {
            $cache = null;
            try {
                $cache = new UpdateRoutingCache(
                    $app->make(RedisConnectionContract::class),
                    $app->make('db')->connection(),
                );
            } catch (Throwable) {
                // Throwable, NOT RuntimeException: a host with no ext-redis
                // makes Laravel's PhpRedisConnector raise Error("Class Redis
                // not found"), which is an Error and sailed straight through
                // this guard. The degradation documented above - a host without
                // a working bus redis still gets a DB-backed path - therefore
                // never actually happened; it fatalled at resolve time instead.
                // Redis is optional by design, so an absent driver is an
                // ordinary configuration, not a fatal.
                $cache = null;
            }

            return new RoutingSettingsObserver($cache, $app->make(EventDispatcher::class));
        });

        // Daemon auto-registration seam (Cycle-5 item 2): the route-seeder
        // default pre-seeds the account-level default rule (peer 0/0,
        // store_only — same opaque semantics as the UpdateRouter default) and
        // refreshes the Redis-2 cache when the daemon seats a discovered
        // account; forget() drops the hot cache on unseat. Cache is
        // best-effort like RoutingSettingsObserver — a host without a working
        // bus redis still gets a correct DB-backed default route; only the
        // hot-path cache write is skipped. Hosts rebind ROUTE_SEEDER_KEY to
        // swap the routine.
        $this->app->singleton(AccountRouteSeeder::class, static function ($app): AccountRouteSeeder {
            $cache = null;
            try {
                $cache = new UpdateRoutingCache(
                    $app->make(RedisConnectionContract::class),
                    $app->make('db')->connection(),
                );
            } catch (Throwable) {
                // Throwable, NOT RuntimeException: a host with no ext-redis
                // makes Laravel's PhpRedisConnector raise Error("Class Redis
                // not found"), which is an Error and sailed straight through
                // this guard. The degradation documented above - a host without
                // a working bus redis still gets a DB-backed path - therefore
                // never actually happened; it fatalled at resolve time instead.
                // Redis is optional by design, so an absent driver is an
                // ordinary configuration, not a fatal.
                $cache = null;
            }

            return new AccountRouteSeeder($app->make('db')->connection(), $cache);
        });
        $this->app->bind(DaemonCommand::ROUTE_SEEDER_KEY, static fn ($app): AccountRouteSeeder => $app->make(AccountRouteSeeder::class));

        $this->app->bindIf(DaemonCommand::SESSION_RESOLVER_KEY, static function (): callable {
            return static function (string $sessionRef): ?SessionData {
                $accountId = self::vaultAccountId($sessionRef);

                if ($accountId === null) {
                    return null;
                }

                try {
                    $account = TelegramAccount::query()->find($accountId);
                } catch (Throwable) {
                    // An unmigrated vault table is an ordinary host state, not a
                    // daemon-fatal one: the daemon treats a missing registry
                    // table as "no accounts" (registryAccountConfigs is
                    // best-effort), so the resolver degrades the same way.
                    return null;
                }

                if (! $account instanceof TelegramAccount || $account->type !== TelegramAccount::TYPE_USER) {
                    return null;
                }

                $session = $account->getAttribute('session');

                if (! is_string($session) || $session === '') {
                    return null;
                }

                return SessionData::importString($session);
            };
        });

        $this->app->singleton(Vault::class);

        $this->app->bind(RedisConnectionContract::class, static function ($app): RedisConnectionContract {
            $manager = $app->bound('redis') ? $app->make('redis') : null;

            if ($manager instanceof RedisManager) {
                /** @var ConfigRepository $config */
                $config = $app->make('config');
                $connection = (string) $config->get('teleframe.bus.connection', 'default');

                return new LaravelRedisAdapter($manager->connection($connection));
            }

            // No illuminate redis service: fail loudly. Tests bind the
            // in-memory ArrayRedis double to this contract themselves; a
            // silent fallback here would hide a misconfigured host app.
            throw new RuntimeException(
                'teleframe bus requires the illuminate redis service (app("redis")); '
                .'install illuminate/redis (predis or phpredis driver) or bind '
                .RedisConnectionContract::class.' yourself.',
            );
        });

        $this->app->singleton(TeleframeClient::class, function ($app) {
            $config = $app['config']['teleframe'] ?? $app['config']['telegram'] ?? [];

            return new TeleframeClient(
                defaultApiId: (int) ($config['api_id'] ?? 0),
                defaultApiHash: (string) ($config['api_hash'] ?? ''),
                defaultBotToken: $config['bot_token'] ?? $config['default_bot_token'] ?? null,
                defaultProxyConfig: $config['proxy'] ?? null,
                defaultUserSession: $config['user_session'] ?? null,
                defaultBotSession: $config['bot_session'] ?? null,
                defaultDcId: (int) ($config['dc_id'] ?? 2),
                logger: $app->make(LoggerInterface::class),
            );
        });

        $this->app->bind(BackfillCommand::SCOPE_RESOLVER_KEY, static function ($app): callable {
            return static function (int $accountId) use ($app): UserAccountScope {
                /** @var ConfigRepository $config */
                $config = $app->make('config');

                foreach ((array) $config->get('teleframe.daemon.accounts', []) as $account) {
                    if ((int) ($account['account_id'] ?? 0) === $accountId) {
                        return AccountWorker::buildLiveScope($account);
                    }
                }

                throw new RuntimeException(
                    "no teleframe.daemon.accounts entry with account_id={$accountId} "
                    .'(the backfill command resolves its session through that registry)',
                );
            };
        });

        // Cycle 17: the durable per-peer cursor + attempt ledger. This is what
        // lets a throttled crawl RESUME and reach message id 1 (verbatim
        // 2026-09-16) — without it the cursor is a local array of one
        // invocation and every peer restarts at offset 0.
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): CursorStoreContract => new EloquentCursorStore,
        );

        // Default batch writer: each fetched page lands as a plain
        // messages.messages ingest under the account's tenancy (route
        // dedup is deliberately NOT used — see BackfillCommand).
        $this->app->bind(BackfillCommand::INGESTER_KEY, static function ($app): callable {
            return static function (int $accountId) use ($app): callable {
                $ingestor = $app->make(UpdateIngestor::class);

                return static function (array $messages) use ($ingestor, $accountId): array {
                    try {
                        $root = $ingestor->ingest([
                            '_' => 'messages.messages',
                            'messages' => $messages,
                            'chats' => [],
                            'users' => [],
                        ], $accountId);

                        return ['stored' => count($messages), 'root' => $root->getKey(), 'reason' => null];
                    } catch (Throwable $e) {
                        // The reason is REPORTED, not swallowed. This callable's
                        // return used to be discarded outright
                        // (BackfillCommand.php:275, `$ingester($batch);`), so a
                        // batch that wrote nothing produced the same silent run
                        // as a batch that wrote every row — and tf_messages
                        // stayed empty with no error anywhere. The command now
                        // reads this return and fails the run; it still does
                        // NOT let a failing batch kill the fetch loop, which is
                        // the behaviour the catch was originally there for.
                        return ['stored' => 0, 'root' => null, 'reason' => $e::class.': '.$e->getMessage()];
                    }
                };
            };
        });

        // The daemon's mirror observer (verbatim daily loop): the default
        // NF5 mirror-ingester callable seam the IngestConsumer drains into.
        // Hosts override by rebinding teleclient.ingest.mirror-ingester
        // themselves; the default wires the full pipeline (decompose →
        // write → classify → gated UpdateStored) from committed artifacts.
        $this->app->bind(DaemonCommand::MIRROR_INGESTER_KEY, static function ($app): callable {
            $sends = $app->bound(CacheInterface::class)
                ? $app->make(CacheInterface::class)
                : null;

            // Redis-2 hot-reload routing cache is best-effort at the seam:
            // hosts with a working bus redis get real-time listen/ignore
            // control; hosts without one fall back to DB-only routing (the
            // UpdateRouter cache-miss path -- behavior identical). A missing
            // or misconfigured redis must never break the ingest seam itself;
            // the bus surfaces misconfiguration loudly elsewhere.
            $routingCache = null;
            try {
                $routingCache = new UpdateRoutingCache(
                    $app->make(RedisConnectionContract::class),
                    $app->make('db')->connection(),
                );
            } catch (Throwable) {
                // Throwable, NOT RuntimeException: a host with no ext-redis
                // makes Laravel's PhpRedisConnector raise Error("Class Redis
                // not found"), which is an Error and sailed straight through
                // this guard. The degradation documented above - a host without
                // a working bus redis still gets a DB-backed path - therefore
                // never actually happened; it fatalled at resolve time instead.
                // Redis is optional by design, so an absent driver is an
                // ordinary configuration, not a fatal.
                $routingCache = null;
            }

            return MirrorIngesterFactory::build(
                $app->make(Dispatcher::class),
                $sends,
                $routingCache,
            );
        });

        // Backup vault factory (Phase 2, Task 6): callable(string $setId):
        // VaultInterface for teleframe:backup. Driver-aware — 'telegram'
        // (the DEFAULT) reuses the backfill SCOPE_RESOLVER_KEY seam (shared
        // daemon.accounts registry, AccountWorker::buildLiveScope) to build
        // the real channel-backed TelegramVault, 's3' adapts the resolved
        // S3GatewayContract into a durable S3 tier (Cycle 38 — Goal D), while
        // 'memory' shares one InMemoryVault per set for the process: an
        // OFFLINE TEST double that persists nothing. It is no longer the
        // shipped default — a backup that reports success and stores
        // nothing must never be what a default install gets; BackupCommand
        // refuses a `run` against it unless
        // teleframe.backup.allow_volatile_vault acknowledges it.
        $this->app->bind(BackupCommand::VAULT_FACTORY_KEY, static function ($app): callable {
            // ONE secret for ONE vault, resolved HERE — at bind time, before
            // any vault exists — so a disagreement between the two accepted
            // keys is a loud refusal instead of chunks written under one
            // secret and an index that cannot be read back under another.
            $passphrase = self::resolveBackupPassphrase($app->make('config'));

            return static function (string $setId) use ($app, $passphrase): VaultInterface {
                /** @var ConfigRepository $config */
                $config = $app->make('config');
                $driver = (string) $config->get('teleframe.backup.driver', 'telegram');

                if ($driver === 'memory') {
                    $key = 'teleframe.backup.vault.'.$setId;
                    if (! $app->bound($key)) {
                        $app->bind($key, static fn (): VaultInterface => new InMemoryVault, true);
                    }

                    /** @var VaultInterface */
                    return $app->make($key);
                }

                if ($driver === 's3') {
                    // The S3 OBJECT GATEWAY as a DURABLE backup tier (Cycle 38
                    // — Goal D, verbatim 2026-09-24:40 "THE S3 IS GOOD and it
                    // should treat telegram as a backup storage"). Until this
                    // branch existed the gateway's ONLY consumer in the whole
                    // package was the tstore HOT CACHE — which is evictable by
                    // definition and therefore not a backup — so the operator's
                    // S3 was unreachable from `teleframe:backup` however the
                    // class was written.
                    //
                    // The gateway comes from the CONTAINER, not from a private
                    // new: one resolved gateway per host (the same binding the
                    // hot cache uses), so `teleframe.tstore.cache=minio` and
                    // `teleframe.backup.driver=s3` address ONE bucket and the
                    // endpoint/region/bucket are configured in exactly one
                    // place (teleframe.tstore.s3).
                    //
                    // THE PASSPHRASE IS HANDED IN, NOT DISCARDED (Cycle 39 —
                    // Goal F). The closure captured `$passphrase` for the
                    // telegram branch and threw it away here, on the line
                    // above, leaving the manifest INDEX unsealed on the one
                    // tier where that is not merely a disclosure limit: the
                    // `generation` this tier ranks "latest" by lived as a
                    // bare int in plaintext JSON, so a manifest injected with
                    // generation=PHP_INT_MAX pinned the set for good. Null
                    // here stays meaningful — a host with no passphrase gets
                    // the old plaintext behaviour, not a throw — but an
                    // operator who HAS a secret now gets the same sealed
                    // index the Telegram tier has always had.
                    /** @var S3GatewayContract $gateway */
                    $gateway = $app->make(S3GatewayContract::class);

                    return new S3DurableVault($setId, $gateway, $passphrase);
                }

                if ($driver !== 'telegram') {
                    throw new RuntimeException("unknown backup driver \"{$driver}\" — expected memory|telegram|s3.");
                }

                $accountId = (int) ($config->get('teleframe.backup.account') ?? 0);
                if ($accountId <= 0) {
                    throw new RuntimeException(
                        'backup driver "telegram" needs teleframe.backup.account (a daemon.accounts account_id).',
                    );
                }

                $resolver = $app->make(BackfillCommand::SCOPE_RESOLVER_KEY);
                if (! is_callable($resolver) || is_string($resolver)) {
                    throw new RuntimeException(
                        BackfillCommand::SCOPE_RESOLVER_KEY.' must bind a callable(int): UserAccountScope',
                    );
                }

                // The manifest index is AEAD-sealed under a key derived from
                // the set PASSPHRASE, not from the channel access_hash: the
                // access hash is not a secret (Telegram issues it to every
                // account that can see the channel), so deriving from it meant
                // the manifest's own audience already held the key protecting
                // it. That is why the passphrase is threaded here rather than
                // left null — without it the vault refuses to read its own
                // index, loudly, by design.
                //
                // It is the SAME passphrase the command seals the chunks with
                // (TELEFRAME_BACKUP_PASSPHRASE), so the index and the chunks
                // it indexes are one sealed set.
                //
                // THE PER-SET DESTINATION (teleframe.backup.sets.<id>.
                // peer_type / .peer_id) is read HERE, from the set row, and
                // not from the block above: the destination belongs to the
                // SET, so one process can back a free account to a broadcast
                // channel and a premium account to a group. The vault already
                // took both as constructor arguments — nothing ever handed it
                // a value, so the two documented set-row keys were reachable
                // only by building the vault by hand, and declaring them here
                // without this would have made them config read by nothing.
                //
                // AN UNSET ROW LANDS ON THE VAULT'S OWN DEFAULTS, which is the
                // path this line took before the keys existed: PEER_CHANNEL,
                // peerId null, find-or-create by title. That is why declaring
                // them changed nothing for a host that does not set them.
                $setRow = $config->get('teleframe.backup.sets.'.$setId);
                $setRow = is_array($setRow) ? $setRow : [];

                // An unknown kind is NOT normalised here: TelegramVault's
                // constructor refuses it by name, which is the error an
                // operator who typed 'supergroup' needs to see.
                $peerType = (string) ($setRow['peer_type'] ?? TelegramVault::PEER_CHANNEL);

                // env() hands back a STRING and the vault takes ?int. 0 is not
                // "unset" either — casting null to 0 would name a dialog id
                // that cannot exist and turn the find-or-create fallback into
                // a hard "no such peer" failure on every run.
                $rawPeerId = $setRow['peer_id'] ?? null;
                $peerId = ($rawPeerId === null || $rawPeerId === '') ? null : (int) $rawPeerId;

                return TelegramVault::forScope(
                    $resolver($accountId),
                    $setId,
                    $passphrase,
                    $peerType,
                    $peerId,
                );
            };
        });

        // The resend budget is PSR-16 backed, so a web caller (controller,
        // Livewire action) cannot reset the cap to MAX_RESENDS_PER_LOGIN on
        // every request: the budget follows the login, not the process. A host
        // without a PSR-16 binding gets the process-local default (one process
        // = one login — the CLI case).
        $this->app->singleton(TeleframeAuthService::class, static function ($app): TeleframeAuthService {
            $budget = $app->bound(CacheInterface::class)
                ? $app->make(CacheInterface::class)
                : new InMemoryCache;

            return new TeleframeAuthService(resendBudget: $budget);
        });

        // Cycle-6 read-only scope carrier (Laravel-user session → read-only
        // access to the managed telegram/bot accounts). A process singleton:
        // RelayVaultTerminalFactory gets it injected and wraps its vault
        // dispatch in ReadOnlyVaultDispatch, and ScopeTelegramReadOnly opens a
        // request frame on it for every Laravel-user-session request that
        // reaches the /tg page or the relay switch/presence routes. With no
        // user session (and no operator-pinned resolver) isReadOnly() stays
        // false and the standalone client behaves unchanged.
        $this->app->singleton(ReadOnlyScope::class, static fn (): ReadOnlyScope => new ReadOnlyScope);

        // C5: the per-CONNECTION read-only registry the relay daemon arms its
        // guard from. A process singleton so the mint side (the ticket payload)
        // and the daemon's ticket validator (which records the redeemed grant)
        // and the vault terminal's dispatch (which consults it per RPC) all
        // share ONE registry — and a process singleton is exactly the right
        // lifetime here, because the state it holds is keyed by granted
        // identity, not by a request: two connections never share an entry.
        // Fails closed by default (an unknown grant is read-only).
        $this->app->singleton(RelayReadOnlyGrants::class, static function ($app): RelayReadOnlyGrants {
            $undetermined = (string) $app['config']->get('teleframe.relay.read_only.undetermined_grant', 'readonly');

            return new RelayReadOnlyGrants($undetermined !== 'allow');
        });

        // Relay vault /apiws terminal: builds the dispatch callable
        // VaultMtprotoAnswerer consumes (ACTIVE vault account scope, re-seated
        // on RelayActiveAccount generation bumps). RelayFactory /
        // RelayActiveAccount are auto-wired (RelaySessionResolver all-default;
        // the framework cache.store backs the PSR-16 ACTIVE context). The
        // shared ReadOnlyScope is injected so the terminal dispatch enforces
        // the read-only allow-list while a Laravel-user scope is active (Cycle
        // 6); the wrapper is a no-op when the scope is unmarked, so standalone
        // hosts keep byte-for-byte identical behaviour.
        // Host policy plane: the DISPATCH_MIDDLEWARE_KEY binding defaults to
        // an empty list — the returned dispatch stays the historical
        // ReadOnlyVaultDispatch terminal — and a host overrides the binding
        // with its own policy middlewares, which run around the terminal and
        // see the per-connection ticket-granted identity (account + user).
        $this->app->bind(
            RelayVaultTerminalFactory::DISPATCH_MIDDLEWARE_KEY,
            static fn (): array => [],
        );
        $this->app->singleton(RelayVaultTerminalFactory::class, static function ($app): RelayVaultTerminalFactory {
            $middlewares = $app->make(RelayVaultTerminalFactory::DISPATCH_MIDDLEWARE_KEY);
            if (! is_array($middlewares)) {
                $middlewares = [];
            }

            // C5 read-only arming. The console daemon has no HTTP request
            // frame, so the ReadOnlyScope above is never armed there — the
            // guard used to be a permanent pass-through in the one process
            // that dispatches RPCs. The per-CONNECTION ticket grant is the
            // source instead, resolved through the shared registry the
            // daemon's ticket validator records into. All three knobs default
            // to enforcing / deny-by-default.
            $undetermined = $app['config']->get('teleframe.relay.read_only.undetermined_grant', 'readonly');

            return new RelayVaultTerminalFactory(
                $app->make(RelayFactory::class),
                $app->make(RelayActiveAccount::class),
                $app->make(LoggerInterface::class),
                readOnlyScope: $app->make(ReadOnlyScope::class),
                dispatchMiddlewares: $middlewares,
                readOnlyGrants: $app->make(RelayReadOnlyGrants::class),
                enforceReadOnlyGrant: (bool) $app['config']->get('teleframe.relay.read_only.enforce', true),
                undeterminedGrantIsReadOnly: $undetermined !== 'allow',
            );
        });

        // Realtime projection (Phase 4): when teleframe.realtime.url is set,
        // every stored update fan-outs to the per-account Centrifugo
        // channels. Unconfigured → no binding; the listener below is not
        // registered and the mirror stays silent (no external side effects).
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');
        if ((string) $config->get('teleframe.realtime.url', '') !== '') {
            $this->app->singleton(CentrifugoBridge::class, static function ($app): CentrifugoBridge {
                /** @var ConfigRepository $config */
                $config = $app->make('config');

                return new HttpCentrifugoBridge(
                    (string) $config->get('teleframe.realtime.url', ''),
                    (string) $config->get('teleframe.realtime.apikey', ''),
                    (string) $config->get('teleframe.realtime.secret', ''),
                );
            });

            $this->app->singleton(PresenceFeed::class, static function ($app): PresenceFeed {
                $registry = $app->make(RelaySessionRegistry::class);
                if (! $registry instanceof RelaySessionRegistry) {
                    throw new RuntimeException('teleframe: RelaySessionRegistry must be bound for PresenceFeed.');
                }

                return new PresenceFeed(
                    $app->make(CentrifugoBridge::class),
                    $registry,
                );
            });
        }

        // ── TStore (S1–S11): six public container seams, defaulted from
        // teleframe.tstore config. Hosts rebind any key. The service is
        // resolvable even unconfigured (memory index + memory cache + sync
        // queue; the channel factory throws on first bucket use without a
        // scope resolver), so `buckets`/`ls` scaffolding works pre-creds.
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');

        // One TelegramStoreChannel per bucket for the life of this factory.
        // The channel is STATEFUL by design (it caches the resolved channel
        // id/access hash, and a configured peer target's membership gate
        // runs once, at construction). Building a fresh channel per call —
        // which this factory used to do — re-ran findChannel on every read
        // AND re-ran the peer membership join + post-rights probe on every
        // read: six extra RPCs for one put and three gets, in a module whose
        // entire posture is flood-wait handling. The memo is what makes the
        // gate "ensured before the first upload" rather than "before every
        // single call".
        //
        // A channel is only ever built after the account_id check below, so
        // the memo is not a way to skip that refusal: an unconfigured
        // install still throws on first use, every time.
        $channels = [];

        $this->app->bind(TstoreCommand::CHANNEL_FACTORY_KEY, static function ($app) use ($config, &$channels): callable {
            return static function (string $bucket) use ($app, $config, &$channels): TelegramStoreChannel {
                if (isset($channels[$bucket])) {
                    return $channels[$bucket];
                }

                $accountId = (int) ($config->get('teleframe.tstore.account_id') ?? 0);
                if ($accountId <= 0) {
                    throw new RuntimeException(
                        'tstore channel factory needs teleframe.tstore.account_id '
                        .'(a daemon.accounts account_id) — configure it or bind '.TstoreCommand::CHANNEL_FACTORY_KEY.' yourself.',
                    );
                }

                $resolver = $app->make(BackfillCommand::SCOPE_RESOLVER_KEY);
                if (! $resolver instanceof \Closure && ! is_string($resolver)) {
                    throw new RuntimeException(
                        BackfillCommand::SCOPE_RESOLVER_KEY.' must bind a callable(int): UserAccountScope',
                    );
                }

                $scope = $resolver($accountId);

                // A configured peer target IS HONOURED: TelegramStoreChannel
                // routes every write to it, and the PeerMembership seam is
                // composed from the SAME live scope so the account is joined
                // to the peer and its post rights are proven BEFORE the
                // first upload. A target with no access hash, or one the
                // account cannot post in, is refused there — up front, with
                // a reason, instead of failing opaquely mid-upload.
                $peerTarget = self::peerTargetRow($config->get('teleframe.tstore.peer_target'));
                if ($peerTarget !== []) {
                    $mode = (string) ($peerTarget['mode'] ?? ObjectManifest::MODE_SEALED);
                    $passphrase = $peerTarget['passphrase'] ?? null;

                    return $channels[$bucket] = TelegramStoreChannel::forScope(
                        $scope,
                        $bucket,
                        peerTarget: new PeerTarget(
                            bucket: (string) ($peerTarget['bucket'] ?? $bucket),
                            peerId: $peerTarget['peer_id'] ?? $peerTarget['peerId'] ?? 0,
                            accessHash: $peerTarget['access_hash'] ?? $peerTarget['accessHash'] ?? null,
                            peerType: (string) ($peerTarget['peer_type'] ?? $peerTarget['peerType'] ?? 'channel'),
                            mode: in_array($mode, [ObjectManifest::MODE_RAW, ObjectManifest::MODE_SEALED], true)
                                ? $mode
                                : ObjectManifest::MODE_SEALED,
                            passphrase: is_string($passphrase) && $passphrase !== '' ? $passphrase : null,
                            memberAccounts: array_values(
                                is_array($peerTarget['members'] ?? null) ? $peerTarget['members'] : [],
                            ),
                        ),
                    );
                }

                return $channels[$bucket] = TelegramStoreChannel::forScope($scope, $bucket);
            };
        });

        $this->app->bind(TstoreCommand::INDEX_KEY, static function (): StoreIndexContract {
            return class_exists('Illuminate\Support\Facades\Schema') && Schema::hasTable('tf_store_buckets')
                ? new EloquentStoreIndex
                : new MemoryStoreIndex;
        });

        $this->app->bind(TstoreCommand::HOT_CACHE_KEY, static function ($app) use ($config): HotCacheContract {
            $cache = (string) ($config->get('teleframe.tstore.cache') ?? 'memory');
            $ttl = (int) ($config->get('teleframe.tstore.cache_ttl_seconds') ?? 600);
            $maxBytes = (int) ($config->get('teleframe.tstore.cache_max_bytes') ?? 67_108_864);

            if ($cache === 'memory') {
                return new MemoryHotCache(ttlSeconds: $ttl, maxBytes: $maxBytes);
            }

            if ($cache === 'minio') {
                /** @var S3GatewayContract $gateway */
                $gateway = $app->make(S3GatewayContract::class);

                return new MinioHotCache($gateway, $ttl, $maxBytes);
            }

            throw new RuntimeException("unknown tstore cache driver \"{$cache}\" — expected memory|minio.");
        });

        $this->app->bind(S3GatewayContract::class, static function ($app): S3GatewayContract {
            /** @var ConfigRepository $config */
            $config = $app->make('config');

            // endpoint/region/bucket are CONFIGURATION, not literals
            // (Cycle 38 — Goal D). They used to be hardcoded here — endpoint
            // 'http://127.0.0.1:9010', bucket 'teleframe-tstore' — while only
            // the credentials were env-driven, so a host that read the
            // operator's "THE S3 IS GOOD" and pointed at real S3 got a client
            // that dialled LOCALHOST MinIO and failed at first use. The config
            // defaults are exactly those literals, so nothing changes for a
            // host that configures nothing.
            $s3 = $config->get('teleframe.tstore.s3');

            return new AwsSdkS3Gateway([
                'endpoint' => (string) ($s3['endpoint'] ?? 'http://127.0.0.1:9010'),
                'region' => (string) ($s3['region'] ?? 'us-east-1'),
                'bucket' => (string) ($s3['bucket'] ?? 'teleframe-tstore'),
                'key' => (string) ($s3['key'] ?? 'minioadmin'),
                'secret' => (string) ($s3['secret'] ?? 'minioadmin'),
                'use_path_style' => (bool) ($s3['use_path_style'] ?? true),
            ]);
        });

        $this->app->bind(TstoreCommand::QUEUE_KEY, static function () use ($config): UploadQueueContract {
            $driver = (string) ($config->get('teleframe.tstore.queue') ?? 'sync');

            if ($driver === 'sync') {
                return new SyncUploadQueue;
            }

            if ($driver === 'amqp') {
                return new AmqpUploadQueue([
                    'host' => (string) ($config->get('teleframe.tstore.amqp.host') ?? '127.0.0.1'),
                    'port' => (int) ($config->get('teleframe.tstore.amqp.port') ?? 5672),
                    'vhost' => (string) ($config->get('teleframe.tstore.amqp.vhost') ?? '/'),
                    'user' => (string) ($config->get('teleframe.tstore.amqp.user') ?? 'guest'),
                    'pass' => (string) ($config->get('teleframe.tstore.amqp.password') ?? 'guest'),
                ]);
            }

            throw new RuntimeException("unknown tstore queue driver \"{$driver}\" — expected sync|amqp.");
        });

        // Schedules seam: 'null' (the shipped default — probe-only) or
        // 'temporal', READ FROM teleframe.tstore.schedules. This used to be a
        // zero-arg `static fn () => new NullSchedulePublisher`, so the key (and
        // schedules_dsn / schedules_namespace beside it) was declared,
        // documented and read by nothing. Same defect Cycle 19 already fixed
        // for teleframe.app.schedules and
        // teleframe.store.backup.schedule.publisher; tstore and notify were the
        // rows left behind. Posture is identical to those two wirings: a
        // host-bound client WINS verbatim, else TemporalClientResolver hands
        // back the package's own SDK-backed client, and with neither the
        // resolver refuses HERE at resolve time by name — never a silent
        // degrade to NullSchedulePublisher, which would report 'skipped'
        // while the host believes its tstore jobs recurred.
        //
        // (Cycle 19 also removed these keys from NullSchedulePublisher's
        // refusal text rather than wiring them, which was right at the time.
        // The text is in Store\Schedules\ and is not this slice's to edit; what
        // is enforced from here is that it never names a key nothing reads.)
        //
        // DEFAULT STAYS 'null': arming recurring jobs the moment a package is
        // installed is a surprise, and that ruling is already in the ledger.
        $this->app->singleton(TstoreCommand::SCHEDULES_KEY, static function ($app) use ($config): SchedulePublisher {
            $driver = (string) ($config->get('teleframe.tstore.schedules') ?? 'null');

            if ($driver === 'null') {
                return new NullSchedulePublisher;
            }

            if ($driver === 'temporal') {
                $dsn = (string) ($config->get('teleframe.tstore.schedules_dsn') ?? '127.0.0.1:7233');
                $namespace = (string) ($config->get('teleframe.tstore.schedules_namespace') ?? 'default');

                // The RESOLVER is container-resolved when the host binds one, so
                // a host (or a test) controls SDK availability deterministically;
                // the default instance builds the SDK-backed client when
                // temporal/sdk is installed and refuses by name when it is not.
                // Environment-shaped behaviour must never decide what a test
                // asserts (CI has no ext-grpc).
                $resolver = $app->bound(TemporalClientResolver::class)
                    ? $app->make(TemporalClientResolver::class)
                    : new TemporalClientResolver;

                // The namespace is the resolver's OWN second argument — it
                // becomes the Temporal accounting namespace the SDK client is
                // built with. Handing it over here is what makes this key live;
                // passing the DSN alone is what left it dead.
                $client = $app->bound(TemporalScheduleClient::class)
                    ? $app->make(TemporalScheduleClient::class)
                    : $resolver->resolve($dsn, $namespace);

                return new TemporalSchedulePublisher(
                    dsn: $dsn,
                    client: $client,
                    // The fallback is the SHARED default, not a literal, so
                    // tstore and the other three schedules cannot end up on
                    // different queues while one worker serves them. A host
                    // needing another queue either sets
                    // TELEFRAME_TEMPORAL_TASK_QUEUE or binds its own
                    // TemporalScheduleClient, which wins verbatim above.
                    taskQueue: TemporalSchedulePublisher::defaultTaskQueue(),
                );
            }

            throw new RuntimeException(
                "unknown tstore schedule driver \"{$driver}\" — expected null|temporal."
            );
        });

        $this->app->singleton(TstoreCommand::SERVICE_KEY, static function ($app) use ($config): StoreService {
            // The RECLAIM POSTURE, read once here and handed to the ctor so
            // every reclaimLocal() and every read path inherits it.
            // ReferencePolicy::fromString() is the single place the three
            // postures are named, and it refuses an unrecognised value LOUDLY
            // (InvalidArgumentException naming the valid three) — never a
            // silent fall back to retain-local, which would hide a typo from an
            // operator who believes it is reclaiming disk. Unset/blank means
            // retain-local, so the shipped default is the inert one.
            $referencePolicy = ReferencePolicy::fromString(
                $config->get('teleframe.tstore.reference_policy')
            );

            return new StoreService(
                accountId: (int) ($config->get('teleframe.tstore.account_id') ?? 0),
                channelFactory: $app->make(TstoreCommand::CHANNEL_FACTORY_KEY),
                index: $app->make(TstoreCommand::INDEX_KEY),
                cache: $app->make(TstoreCommand::HOT_CACHE_KEY),
                queue: $app->make(TstoreCommand::QUEUE_KEY),
                allowCachedPlaintext: false,
                // premium: false (the shipped default) => FREE_CAP =
                // 2097152000 bytes (1.953125 GiB); true => PREMIUM_CAP =
                // 4194304000 bytes (3.90625 GiB). Not a 4 GiB cap.
                premium: (bool) ($config->get('teleframe.tstore.premium') ?? false),
                referencePolicy: $referencePolicy,
            );
        });
    }

    public function boot(): void
    {
        // Horizon-style dashboard shell (teleframe::dashboard) — resolved in
        // web AND console so queue workers can render it too if a host does.
        $this->loadViewsFrom(dirname(__DIR__).'/resources/views', 'teleframe');

        // The verbatim's settings-change loop: tg_update_routing changes
        // refresh the Redis-2 hot-reload cache and emit RoutingSettingsChanged.
        // Registered for every app (web + console) so a default install never
        // serves stale routing rules from the cache-first UpdateRouter.
        UpdateRoutingRule::observe(RoutingSettingsObserver::class);

        if ($this->app->runningInConsole()) {
            // THE UMBRELLA TAG. `vendor:publish --tag=teleframe-config` must land
            // EVERY config file this package ships, not just one of them: the
            // per-file tags below (and in the other five providers) are what a
            // host finds by reading each provider, and a host that published
            // only the obvious tag used to silently get four configs fewer than
            // it asked for — no error, just defaults nobody chose.
            //
            // This map is the ONE home for the package's config publish surface.
            // It deliberately lists files owned by the other providers (Notify,
            // Passport, App, StoreBackup) because the TAG is shared state: any
            // provider may contribute to a publish group, and keeping the whole
            // map here means there is exactly one place to count, and exactly one
            // place a new config file has to be added to be published at all.
            //
            // The per-file tags each provider registers still work unchanged;
            // publishing the umbrella is strictly additive.
            //
            // bin/prove-host-install.php asserts this map covers every file in
            // src/Laravel/config/ and that no config file is unreferenced by ANY
            // tag, so a config file added without landing here turns the gate red.
            $this->publishes([
                __DIR__.'/../config/teleframe.php' => config_path('teleframe.php'),
                __DIR__.'/../config/notify.php' => config_path('teleframe/notify.php'),
                __DIR__.'/../config/passport.php' => config_path('teleframe/passport.php'),
                __DIR__.'/../config/app.php' => config_path('teleframe/app.php'),
                __DIR__.'/../config/store-backup.php' => config_path('teleframe/store/backup.php'),
            ], 'teleframe-config');

            $this->publishes([
                __DIR__.'/../resources/views/dashboard.blade.php' => resource_path('views/vendor/teleframe/dashboard.blade.php'),
            ], 'teleframe-dashboard');

            $this->publishes([
                __DIR__.'/../Stubs/miniapp' => base_path(),
            ], 'teleframe-miniapp');

            $this->commands([
                LoginCommand::class,
                PollCommand::class,
                DoctorCommand::class,
                SchemaAuditCommand::class,
                SchemaUpdateCommand::class,
                VaultAddAppCommand::class,
                VaultAddAccountCommand::class,
                VaultListCommand::class,
                VaultUseDefaultCommand::class,
                RegenerateCommand::class,
                IngestCommand::class,
                IngestGateCommand::class,
                BackfillCommand::class,
                CluesCommand::class,
                BackupCommand::class,
                DaemonCommand::class,
                RelayCommand::class,
                TstoreCommand::class,
                RoutingModeCommand::class,
                // The remediation step for the NFC token normalisation:
                // MessageSearchIndexer::reindexAccount() had no caller at all
                // until this command, so tokens written before normalisation
                // could never be rewritten. See ReindexSearchCommand.
                ReindexSearchCommand::class,
            ]);

            $this->loadMigrationsFrom(dirname(__DIR__).'/Migrations');

            // The backup schedule, OPT-IN. `teleframe:backup status` fails on a
            // stale manifest (teleframe.backup.max_age_hours), but that only
            // catches a missed run on a host that ALREADY wired cron — so this
            // registers the run itself. Gated on teleframe.backup.scheduled
            // (default false): with an empty sets.default.paths list `run`
            // refuses, so a default-on schedule would be a red cron on every
            // host that merely installed the package. Registered on
            // $app->booted so the Schedule is resolvable by then, and daily
            // at 03:17 rather than midnight so a host's other midnight jobs do
            // not pile onto it.
            if ((bool) $this->app['config']->get('teleframe.backup.scheduled', false)) {
                $this->app->booted(function ($app): void {
                    $app->make(Schedule::class)
                        ->command('teleframe:backup run --set=default')
                        ->dailyAt('03:17')->withoutOverlapping()->onOneServer();
                });
            }
        }

        if (isset($this->app['router'])) {
            /** @var Router $router */
            $router = $this->app['router'];
            $router->aliasMiddleware('tg.miniapp', VerifyMiniAppInitData::class);

            // Cycle-6 host-boundary binding: when the current request carries
            // a Laravel user session, every /tg page request + relay
            // switch/presence request opens a read-only scope frame on the
            // shared ReadOnlyScope, so the vault terminal dispatch refuses
            // state-changing RPCs. No user session → no frame → the standalone
            // client keeps its full default behaviour. Register AFTER the
            // host's session/auth middleware (it only reacts to Auth::check()).
            $router->aliasMiddleware('tg.readonly', ScopeTelegramReadOnly::class);

            // Cycle-8a authorization floor: tg.readonly only OPENS the
            // read-only frame when a session is already present, so it is not
            // a guard — a session-less request used to be served the embedded
            // client with the read-only frame CLOSED (a silent allow into full
            // write access). tg.auth is the deny-by-default companion: no
            // authenticated Laravel user → 401, the controller never runs.
            // It fails CLOSED if the guard backend itself throws.
            $router->aliasMiddleware('tg.auth', RequireTelegramUserSession::class);

            // Register Route Macro for simple Webhook endpoint declaration
            $router->macro('telegramWebhook', function (string $uri = 'telegram/webhook') use ($router) {
                return $router->post($uri, TelegramWebhookController::class);
            });

            // Register Route Macro for the embedded Telegram Web client
            // (telegram-tt / Web A bundle served from tgweb/dist).
            // GET /tg/{path?} with SPA fallback.
            //
            // Two teleframe-owned middlewares ride along, and the ORDER is the
            // posture: tg.auth runs FIRST and refuses an unauthenticated
            // request (401) before the controller is ever reached; tg.readonly
            // then opens the read-only scope frame for the authenticated user
            // this route is actually a read-only view of (verbatim :6803). The
            // host's own stack (web / auth / tg.miniapp / ...) still layers on
            // top; a standalone session-less deployment can explicitly opt out
            // of tg.auth with ->withoutMiddleware('tg.auth').
            $router->macro('telegramWeb', function (string $uri = 'tg') use ($router) {
                $uri = trim($uri, '/');

                return $router->get($uri.'/{path?}', TelegramWebController::class)
                    ->middleware(['tg.auth', 'tg.readonly'])
                    ->where('path', '.*')
                    ->name('telegram.web');
            });
        }

        // The event intake row funnels into the handler pipeline (friction
        // I.1): stored updates reach the registered onMessage surface with
        // the same registry + onion the bus path uses.
        if (isset($this->app['events']) && class_exists(UpdateStored::class)) {
            /** @var EventDispatcher $events */
            $events = $this->app['events'];
            $events->listen(UpdateStored::class, UpdateStoredHandler::class);

            // Realtime projection (Phase 4): per-account Centrifugo fan-out,
            // registered only when the realtime url is configured so an
            // unconfigured mirror stays silent (no external side effects).
            if ((string) $this->app->make('config')->get('teleframe.realtime.url', '') !== '') {
                $events->listen(UpdateStored::class, UpdateStoredCentrifugoListener::class);
            }

            // TStore live media indexing (W9): opt-in via
            // teleframe.tstore.listen. Presence rows land immediately for
            // declared data channels; identity is reconciled by the walk.
            if ((bool) $this->app->make('config')->get('teleframe.tstore.listen', false)
                && ($this->app->bound(UpdateStoredTstoreListener::class) || $this->app->make('config')->get('teleframe.tstore.account_id', 0) > 0)) {
                $events->listen(UpdateStored::class, UpdateStoredTstoreListener::class);
            }

            // Session revocation (2026-09-27): the poller announces a rejected
            // auth key (TelegramSessionRevoked) but the poller is not the
            // truth — the vault row is. Stamp telegram_accounts.revoked_at
            // here so the revocation outlives the process, and is visible to
            // `teleframe:vault-list` and to resolveVaultAccount(), which
            // refuses a revoked account instead of dialing a dead key.
            // markRevokedFor() returns the account only on the FIRST stamp, so
            // this is idempotent: a host that keeps polling a dead key records
            // the revocation once and re-listens to nothing.
            $events->listen(TelegramSessionRevoked::class, static function (TelegramSessionRevoked $event): void {
                TelegramAccount::markRevokedFor($event->accountId);
            });
        }
    }

    /**
     * The ONE vault secret, read from teleframe.backup.passphrase
     * (TELEFRAME_BACKUP_PASSPHRASE) — the same value teleframe:backup seals
     * the chunks with, so the manifest index and the chunks it indexes are
     * one sealed set.
     *
     * teleframe.tstore.passphrase is accepted as a LEGACY ALIAS only when the
     * real key is empty. It used to be the only key consulted, which is the
     * defect: chunks sealed under TELEFRAME_BACKUP_PASSPHRASE, index sealed
     * under TELEFRAME_TSTORE_PASSPHRASE, so a set written by `run` could not
     * be verified, restored or pruned by the same command. Two keys holding
     * two different secrets is therefore refused HERE — at bind time, naming
     * both — rather than surfacing later as an unreadable manifest.
     *
     * The VALUE is never logged, echoed or included in the message.
     */
    /**
     * The teleframe.tstore.peer_target row, normalised from whatever shape
     * config gives us. It arrives as an ARRAY when the host sets it in
     * config/teleframe.php, and as a STRING when it comes from
     * TELEFRAME_TSTORE_PEER_TARGET (an env var is always a string, so the
     * row is a JSON object). Both are accepted; anything else — and any
     * malformed JSON — is a config error refused loudly, never silently
     * treated as "no target": a host that believes it configured a group
     * destination must not quietly get its bucket channel instead.
     *
     * @return array<string, mixed>
     */
    private static function peerTargetRow(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(
                'teleframe.tstore.peer_target must be a JSON object (env var) or an array (config), '
                .'e.g. {"peer_id":-1001234567890,"access_hash":123,"peer_type":"group","mode":"sealed"}. '
                .'Unset it to store to the bucket\'s own channel.',
            );
        }

        return $decoded;
    }

    /**
     * The vault account id a tf_accounts.session_ref points at, or null when
     * the ref is opaque to us.
     *
     * session_ref is a host-owned opaque string; the shipped default only
     * understands the forms it can name a row by — a bare positive integer,
     * or that integer behind a scheme prefix ("vault:12"). Anything else
     * returns null rather than guessing, so a host that mints richer refs
     * keeps full control and simply wins by binding its own resolver.
     */
    private static function vaultAccountId(string $sessionRef): ?int
    {
        $tail = $sessionRef;
        $colon = strrpos($tail, ':');
        if ($colon !== false) {
            $tail = substr($tail, $colon + 1);
        }

        return ctype_digit($tail) && (int) $tail > 0 ? (int) $tail : null;
    }

    private static function resolveBackupPassphrase(ConfigRepository $config): ?string
    {
        $primary = (string) ($config->get('teleframe.backup.passphrase') ?: '');
        $legacy = (string) ($config->get('teleframe.tstore.passphrase') ?: '');

        // The two-key refusal is about the LEGACY KEY still being able to
        // disagree, and that is a property of the telegram tier alone: there
        // the vault is built by scope and the manifest index is sealed under
        // this resolver's return value while `teleframe.tstore.passphrase` is
        // the OTHER accepted source of the same secret. On driver=s3 the
        // factory passes this same return value straight into
        // S3DurableVault and tstore.passphrase is never consulted by the
        // backup path, so a disagreement there describes no split-key vault
        // that could exist — refusing it would be refusing a coherent host.
        if ((string) ($config->get('teleframe.backup.driver') ?: 'telegram') === 'telegram'
            && $primary !== '' && $legacy !== '' && ! hash_equals($primary, $legacy)) {
            throw new RuntimeException(
                'refusing to build a backup vault: teleframe.backup.passphrase (TELEFRAME_BACKUP_PASSPHRASE) and its '
                .'legacy alias teleframe.tstore.passphrase (TELEFRAME_TSTORE_PASSPHRASE) are both set to DIFFERENT '
                .'secrets. One vault has one secret: the chunks are sealed with the first and the manifest index with the '
                .'second, so neither could read the other back. Set both to the same value, or clear '
                .'teleframe.tstore.passphrase and keep only teleframe.backup.passphrase.',
            );
        }

        $secret = $primary !== '' ? $primary : $legacy;

        return $secret !== '' ? $secret : null;
    }
}
