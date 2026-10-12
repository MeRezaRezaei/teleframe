#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Phase 2 Task 7: plain-PHP proof across EVERY merged module. Runs with
 * PRODUCTION dependencies only — no testbench, no laravel/framework boot.
 * Exit 0 = the whole teleframe module surface is standalone-constructible.
 * (Run from the repo root after `composer install`.)
 *
 * Keep tests/Standalone/PlainPhpLoadTest.php in lockstep with the checks
 * below so the phpunit Standalone suite mirrors this proof.
 */

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MeRezaRezaei\Teleframe\App\AppCall;
use MeRezaRezaei\Teleframe\App\AppCallSpec;
use MeRezaRezaei\Teleframe\App\AppDefinition;
use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppMode;
use MeRezaRezaei\Teleframe\App\AppRegistry;
use MeRezaRezaei\Teleframe\App\AppResponder;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\App\AppTransportResolver;
use MeRezaRezaei\Teleframe\App\AppTrigger;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backfill\CursorStoreContract;
use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Backup\BackupRunner;
use MeRezaRezaei\Teleframe\Backup\Chunker;
use MeRezaRezaei\Teleframe\Backup\InMemoryVault;
use MeRezaRezaei\Teleframe\Backup\Pruner;
use MeRezaRezaei\Teleframe\Backup\Restorer;
use MeRezaRezaei\Teleframe\Backup\VaultCrypto;
use MeRezaRezaei\Teleframe\Backup\Verifier;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Control\RelayMessage;
use MeRezaRezaei\Teleframe\Control\RelayTerminal;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\RelayFrameDialBridge;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketFrameCodec;
use MeRezaRezaei\Teleframe\Core\MTProto\Transport\WebSocketRelayServer;
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Handler\HandlerMatcher;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\IdentityLock;
use MeRezaRezaei\Teleframe\Ingest\PayloadWalker;
use MeRezaRezaei\Teleframe\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\RequireTelegramUserSession;
use MeRezaRezaei\Teleframe\Laravel\Http\Middleware\ScopeTelegramReadOnly;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyVaultDispatch;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayFactory;
use MeRezaRezaei\Teleframe\Laravel\Services\RelaySessionResolver;
use MeRezaRezaei\Teleframe\Laravel\Store\S3DurableVault;
use MeRezaRezaei\Teleframe\Laravel\Store\S3GatewayContract;
use MeRezaRezaei\Teleframe\Passport\AuthorizationExporter;
use MeRezaRezaei\Teleframe\Passport\AuthorizationFormService;
use MeRezaRezaei\Teleframe\Passport\AuthorizationImporter;
use MeRezaRezaei\Teleframe\Passport\CachePendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\ImportedAuthorization;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryNonceRegistry;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\MemoryPendingLoginRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportAnonymousLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportAuthorizationForm;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportFieldError;
use MeRezaRezaei\Teleframe\Passport\PassportFieldErrorsException;
use MeRezaRezaei\Teleframe\Passport\PassportKeyLoader;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportProfile;
use MeRezaRezaei\Teleframe\Passport\PassportProfileFetcher;
use MeRezaRezaei\Teleframe\Passport\PassportRefusal;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Passport\PassportWireLogin;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmer;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmerContract;
use MeRezaRezaei\Teleframe\Passport\PasswordRequiredException;
use MeRezaRezaei\Teleframe\Passport\PendingLoginBinding;
use MeRezaRezaei\Teleframe\Passport\RedisAtomicCacheStore;
use MeRezaRezaei\Teleframe\Passport\SessionFingerprint;
use MeRezaRezaei\Teleframe\Passport\SessionIdentityResolver;
use MeRezaRezaei\Teleframe\Passport\UserScopePassportWire;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Realtime\HttpCentrifugoBridge;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupRunReport;
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
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\MediaReference;
use MeRezaRezaei\Teleframe\Store\ObjectManifest;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\ReclaimOutcome;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SdkTemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\StoreMaintenanceScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreBackupDumpActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\WorkflowTypeCatalog;
use MeRezaRezaei\Teleframe\Store\StoreService;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessHandle;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpawnerContract;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Supervisor\Supervisor;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use MeRezaRezaei\Teleframe\Supervisor\UnknownProcessException;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Testing\FakeDispatcher;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Response;

/** Minimal in-memory RedisConnectionContract double (no laravel redis). */
final class SmokeRedis implements RedisConnectionContract
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $streams = [];

    /** @var array<string, array<string, string>> */
    private array $hashes = [];

    /** @var array<string, array<string, true>> */
    private array $groups = [];

    /** @var array<string, array<string, array<string, string>>> */
    private array $pending = [];

    public int $received = 0;

    public function xadd(string $stream, string $id, array $fields, array $options = []): string
    {
        $entryId = ($id === '*') ? (string) (count($this->streams[$stream] ?? []) + 1) : $id;
        $this->streams[$stream][$entryId] = $fields;

        $maxlen = (int) ($options['maxlen'] ?? 0);
        if ($maxlen > 0 && isset($this->streams[$stream]) && count($this->streams[$stream]) > $maxlen) {
            $ids = array_keys($this->streams[$stream]);
            foreach (array_slice($ids, 0, count($ids) - $maxlen) as $stale) {
                unset($this->streams[$stream][$stale]);
            }
        }

        return $entryId;
    }

    /** Minimal XGROUP CREATE support: the drain path must be able to arm itself. */
    public function command(string $command, array $arguments = []): mixed
    {
        if (strtolower($command) !== 'xgroup') {
            throw new \RuntimeException('SmokeRedis does not implement '.$command);
        }

        // XGROUP CREATE <stream> <group> <id> [MKSTREAM] — arguments[0] is
        // the literal 'CREATE', the stream is arguments[1].
        $stream = (string) ($arguments[1] ?? '');
        $group = (string) ($arguments[2] ?? '');

        if (isset($this->groups[$group][$stream])) {
            throw new \RuntimeException('BUSYGROUP Consumer Group name already exists');
        }

        $this->groups[$group][$stream] = true;
        $this->streams[$stream] ??= [];

        return 'OK';
    }

    public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
    {
        $out = [];
        foreach ($this->pending[$group][$stream] ?? [] as $id => $owner) {
            $out[] = ['name' => $owner, 'pending' => 1, 'min' => $id, 'max' => $id];
        }

        return $out;
    }

    public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
    {
        return ['0-0', [], []];
    }

    public function xrange(string $stream, string $start, string $end, int $count = 100): array
    {
        return $this->streams[$stream] ?? [];
    }

    public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
    {
        $out = [];
        $ids = array_keys($this->streams[$stream] ?? []);
        rsort($ids);

        foreach ($ids as $id) {
            if ($count > 0 && count($out) >= $count) {
                break;
            }
            $out[$id] = $this->streams[$stream][$id];
        }

        return $out;
    }

    public function xdel(string $stream, array $ids): int
    {
        $removed = 0;
        foreach ($ids as $id) {
            if (isset($this->streams[$stream][$id])) {
                unset($this->streams[$stream][$id]);
                $removed++;
            }
        }

        return $removed;
    }

    public function hdel(string $key, string ...$fields): int
    {
        $removed = 0;
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->hashes[$key] ?? [])) {
                unset($this->hashes[$key][$field]);
                $removed++;
            }
        }

        return $removed;
    }

    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
    {
        $out = [];
        foreach ($streams as $name => $count) {
            foreach ($this->streams[$name] ?? [] as $id => $fields) {
                if ($minId !== '>' && (int) $id <= (int) $minId) {
                    continue;
                }
                $out[$name][$id] = $fields;
                if (count($out[$name]) >= $count) {
                    break;
                }
            }
        }

        return $out;
    }

    public function xack(string $stream, string $group, array $ids): int
    {
        return count($ids);
    }

    public function hgetall(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }

    public function hset(string $key, string $field, string $value): int
    {
        $existed = isset($this->hashes[$key][$field]);
        $this->hashes[$key][$field] = $value;

        return $existed ? 0 : 1;
    }

    public function publish(string $channel, string $payload): int
    {
        $this->received++;

        return 1;
    }

    public function subscribe(string $channel, callable $handler): void
    {
        // Loopback subscribe semantics are refused by the real adapter; the
        // double keeps the contract satisfiable without a live socket.
    }

    public function del(string ...$keys): int
    {
        $removed = 0;
        foreach ($keys as $key) {
            foreach (['streams', 'hashes'] as $bucket) {
                if (isset($this->{$bucket}[$key])) {
                    unset($this->{$bucket}[$key]);
                    $removed++;
                }
            }
        }

        return $removed;
    }

    public function llen(string $key): int
    {
        return count($this->streams[$key] ?? []);
    }

    public function hget(string $key, string $field): ?string
    {
        return $this->hashes[$key][$field] ?? null;
    }

    public function expire(string $key, int $seconds): bool
    {
        return true;
    }
}

/**
 * Framework-free PSR-11 + PSR-16 double for the Phase 3 handler stack:
 * resolves the singleton registry/dispatcher and shares ONE sends cache
 * between the facade's send path and the echo eliminator.
 */
final class StackContainer implements ContainerInterface
{
    private ?UpdateDispatcher $dispatcher = null;

    public function __construct(
        private readonly HandlerRegistry $registry,
        private readonly InMemoryCache $sends,
    ) {}

    public function get(string $id): mixed
    {
        if ($id === HandlerRegistry::class) {
            return $this->registry;
        }
        if ($id === CacheInterface::class) {
            return $this->sends;
        }
        if ($id === UpdateDispatcher::class) {
            return $this->dispatcher ??= new UpdateDispatcher($this->registry, new Pipeline, $this, $this->sends);
        }

        throw new RuntimeException('smoke stack: '.$id.' not bound');
    }

    public function has(string $id): bool
    {
        return $id === HandlerRegistry::class
            || $id === CacheInterface::class
            || $id === UpdateDispatcher::class;
    }
}

/** Framework-free stand-in for Illuminate\Contracts\Events\Dispatcher. */
final class SmokeDispatcher implements EventsDispatcher
{
    /** @var list<array{0: object, 1: array}> */
    public array $fired = [];

    public function listen($events, $listener = null): void {}

    public function hasListeners($eventName): bool
    {
        return false;
    }

    public function subscribe($subscriber): void {}

    public function until($event, $payload = [])
    {
        $this->dispatch($event, $payload);

        return null;
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        $this->fired[] = [$event, $payload];

        return $halt ? $event : null;
    }

    public function getListeners($eventName): array
    {
        return [];
    }

    public function push($event, $payload = []): void {}

    public function flush($event): void {}

    public function forget($event): void {}

    public function forgetPushed(): void {}
}

/** Minimal in-memory ProcessSpawnerContract double (no real process, ever). */
final class SmokeSpawner implements ProcessSpawnerContract
{
    public int $spawns = 0;

    /** @var array<int, array{name: string, running: bool, exitCode: int|null}> */
    private array $handles = [];

    private int $nextId = 1;

    public function spawn(ProcessSpec $spec): ProcessHandle
    {
        $id = $this->nextId++;
        $this->handles[$id] = ['name' => $spec->name, 'running' => true, 'exitCode' => null];
        $this->spawns++;

        return new ProcessHandle($id, $spec->name, 4000 + $id);
    }

    public function isAlive(ProcessHandle $handle): bool
    {
        return $this->handles[$handle->id]['running'] ?? false;
    }

    public function kill(ProcessHandle $handle): void
    {
        $this->handles[$handle->id]['running'] = false;
    }

    public function exitCode(ProcessHandle $handle): ?int
    {
        return $this->handles[$handle->id]['exitCode'] ?? null;
    }

    public function crash(string $name, int $exitCode): void
    {
        foreach ($this->handles as $id => $entry) {
            if ($entry['name'] === $name) {
                $this->handles[$id]['running'] = false;
                $this->handles[$id]['exitCode'] = $exitCode;
            }
        }
    }
}

/**
 * Minimal in-memory S3GatewayContract double — the same flat key => bytes
 * contract tests/Laravel/Store/FakeS3Gateway.php ships (a test-only class the
 * production-deps-only smoke harness does not autoload), so the durable vault
 * can be proved offline with no MinIO, no AWS credentials and no network.
 */
final class SmokeS3Gateway implements S3GatewayContract
{
    /** @var array<string, string> */
    private array $objects = [];

    public function get(string $key): string|false
    {
        return $this->objects[$key] ?? false;
    }

    public function put(string $key, string $bytes): void
    {
        $this->objects[$key] = $bytes;
    }

    public function delete(string $key): void
    {
        unset($this->objects[$key]);
    }

    public function list(): array
    {
        $found = [];
        foreach ($this->objects as $key => $bytes) {
            $found[] = ['key' => $key, 'size' => strlen($bytes)];
        }

        return $found;
    }

    /**
     * @return array<string, string>
     */
    public function listAsMap(): array
    {
        return $this->objects;
    }
}

$ingestor = new UpdateIngestor(events: new SmokeDispatcher);
$entities = new EntityAggregator;

$checks = [
    // Ingest
    'Ingest\UpdateStored loads' => static fn (): bool => class_exists(UpdateStored::class),
    'Ingest\UpdateStored is a plain DTO' => static fn (): bool => class_uses(UpdateStored::class) === [],
    'Ingest\RouteIdempotency constructs' => static fn (): bool => new RouteIdempotency instanceof RouteIdempotency,
    'Ingest\UpdateIngestor constructs (fake dispatcher)' => static fn (): bool => $ingestor instanceof UpdateIngestor,
    'Ingest\EntityAggregator constructs' => static fn (): bool => $entities instanceof EntityAggregator,
    'Ingest\IdentityLock loads' => static fn (): bool => class_exists(IdentityLock::class),
    'Ingest\PayloadWalker loads' => static fn (): bool => class_exists(PayloadWalker::class),

    // Public face
    'Teleclient constructs (two ingestor singletons)' => static fn (): bool => new Teleclient($ingestor, $entities) instanceof Teleclient,

    // Bus
    'Bus\StreamSchema loads' => static fn (): bool => class_exists(StreamSchema::class),
    'Bus\StreamSchema encode/decode round-trip' => static function (): bool {
        $entry = ['account_id' => 42, 'update' => ['_' => 'updateNewMessage', 'seq' => 7], 'ts' => 1720000000];

        return StreamSchema::decode(StreamSchema::encode($entry)) === $entry;
    },

    // Supervisor (Cycle 20): the package-owned process supervisor and its ops
    // read-model must construct with NO container booted and NO process spawned.
    'Supervisor\ProcessCatalog constructs (no container)' => static fn (): bool => (new ProcessCatalog)->names() === ['daemon', 'ingest', 'relay', 'tstore-work'],
    'Supervisor\ProcessSpec builds argv, no process' => static fn (): bool => (new ProcessSpec(name: 'ingest', command: ['php', 'artisan', 'teleframe:ingest']))->command === ['php', 'artisan', 'teleframe:ingest'],
    'Supervisor\Supervisor constructs over the real spawner without spawning' => static fn (): bool => (new Supervisor(new SystemProcessSpawner))->report() === [],
    'Supervisor\Supervisor restarts a dead process from an injected seam' => static function (): bool {
        $spawner = new SmokeSpawner;
        $now = 1700000000;
        $supervisor = new Supervisor($spawner, clock: static function () use (&$now): int {
            return $now;
        }, sleeper: static function (int $s): void {});

        $supervisor->adopt(new ProcessSpec(name: 'ingest', command: ['php', 'artisan', 'teleframe:ingest'], restartDelaySeconds: 5));
        $spawner->crash('ingest', 137);
        $supervisor->tick();
        if ($spawner->spawns !== 1 || $supervisor->state('ingest')?->status !== 'backoff') {
            return false;
        }

        $now += 6;
        $supervisor->tick();

        return $spawner->spawns === 2 && $supervisor->state('ingest')?->status === 'running';
    },
    'Supervisor refuses an unknown process name loudly' => static function (): bool {
        try {
            (new ProcessCatalog)->specFor('ingst');
        } catch (UnknownProcessException) {
            return true;
        }

        return false;
    },
    'Supervisor\OpsReadModel snapshots the failed-work ledger (redis double)' => static function (): bool {
        $redis = new SmokeRedis;
        $redis->hset(StreamSchema::STRIKES_KEY, '1-0', '2');
        $snapshot = (new OpsReadModel($redis))->snapshot();

        return $snapshot->failingIngest === [['entry_id' => '1-0', 'attempts' => 2, 'failing' => true]];
    },
    'Supervisor\OpsReadModel constructs with no bus at all' => static fn (): bool => (new OpsReadModel)->snapshot()->bus === false,

    'Bus\RouteTable constructs (redis double)' => static fn (): bool => new RouteTable(new SmokeRedis) instanceof RouteTable,
    'Bus\RedisStreamSink constructs (redis double)' => static fn (): bool => new RedisStreamSink(new SmokeRedis, 42) instanceof RedisStreamSink,
    'Bus\RedisStreamSink writes to the stream' => static function (): bool {
        $received = 0;
        $redis = new SmokeRedis;
        $sink = new RedisStreamSink($redis, 42);

        $sink->handle(['_' => 'updateNewMessage', 'seq' => 9]);

        return $redis->llen(StreamSchema::STREAM) === 1;
    },

    // Backfill
    'Backfill\FetchQueue constructs' => static fn (): bool => new FetchQueue instanceof FetchQueue,
    'Backfill\MemoryCursorStore is plain-PHP and never rewinds a DESC cursor' => static function (): bool {
        $store = new MemoryCursorStore(now: static fn (): int => 1700000000);
        $store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 100]);
        // History walks DESC, so a deeper page is a SMALLER id and the cursor
        // must move down — never back up to a re-crawl from the top.
        $store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 90]);
        $row = $store->findCursor(7, 'chat');
        if ($row === null || $row['offset_id'] !== 90) {
            return false;
        }
        $store->upsertCursor(7, ['peer_key' => 'chat', 'offset_id' => 0, 'status' => CursorStoreContract::STATUS_COMPLETE]);

        return $store->terminalPeers(7) === ['chat' => CursorStoreContract::STATUS_COMPLETE];
    },
    'Backfill\FetchQueue::seed restores cross-run attempts and dead-letters the exhausted' => static function (): bool {
        $queue = new FetchQueue;
        $queue->push('chat');
        $queue->seed('chat', 3);
        if ($queue->attemptsFor('chat') !== 3) {
            return false;
        }
        $queue->seed('chat', FetchQueue::MAX_ATTEMPTS, 'flood_wait');

        return in_array('chat', $queue->deadLetters(), true) && $queue->pop() === null;
    },
    'Backfill\BackfillWorker constructs (scripted sleep)' => static fn (): bool => new BackfillWorker(10, 3600, static fn (int $s): bool => true) instanceof BackfillWorker,

    // Backup
    'Backup\VaultCrypto constructs' => static fn (): bool => new VaultCrypto instanceof VaultCrypto,
    'Backup\Chunker constructs' => static fn (): bool => new Chunker instanceof Chunker,
    'Backup\InMemoryVault constructs' => static fn (): bool => new InMemoryVault instanceof InMemoryVault,
    'Backup\BackupRunner loads' => static fn (): bool => class_exists(BackupRunner::class),
    'Backup\Pruner loads' => static fn (): bool => class_exists(Pruner::class),
    'Backup\Restorer loads' => static fn (): bool => class_exists(Restorer::class),
    'Backup\Verifier loads' => static fn (): bool => class_exists(Verifier::class),

    // TStore (W1-W9): every Store module plain-PHP constructible
    'Store\MediaReference builds a Telegram document reference offline' => static function (): bool {
        $reference = MediaReference::fromIndexRow([
            'media_type' => 'document',
            'doc_id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'size' => 4096,
            'mime' => 'image/jpeg',
        ]);

        return $reference->toInputDocumentFileLocation() === [
            '_' => 'inputDocumentFileLocation',
            'id' => 777,
            'access_hash' => 123456789,
            'file_reference' => 'AQAD-file-reference-bytes',
            'thumb_size' => '',
        ];
    },
    'Store\ReferencePolicy defaults to never freeing a local file' => static function (): bool {
        // The safe default must be a no-op, and an unknown mode must be loud
        // rather than silently degrade to the safe one.
        if (ReferencePolicy::retainLocal()->reclaims()) {
            return false;
        }
        if (ReferencePolicy::dropLocalAfterIndex()->permitsReclaim(false)) {
            return false;
        }
        if (! ReferencePolicy::dropLocalAfterIndex()->permitsReclaim(true)) {
            return false;
        }
        if (ReferencePolicy::fromString(null)->mode() !== ReferencePolicy::RETAIN_LOCAL) {
            return false;
        }

        try {
            ReferencePolicy::of('delete-everything');
        } catch (InvalidArgumentException) {
            return (new ReclaimOutcome(ReclaimOutcome::REFUSED, ReclaimOutcome::REASON_NO_INDEX_ROW))->isRefused();
        }

        return false;
    },
    'Store\ObjectManifest builds+validates a v2 manifest offline' => static function (): bool {
        $manifest = ObjectManifest::new('smoke.bin', 3, str_repeat('a', 64), ObjectManifest::MODE_RAW, 'application/octet-stream', null, []);
        $reparsed = ObjectManifest::fromArray($manifest->toArray());

        return $reparsed->name() === 'smoke.bin' && $reparsed->mode() === ObjectManifest::MODE_RAW;
    },
    'Store\MemoryStoreIndex upserts + finds + lists' => static function (): bool {
        $index = new MemoryStoreIndex;
        $index->upsertBucket(1, 'data:x', ['kind' => 'data', 'channel_id' => 5]);

        return $index->findBucket(1, 'data:x') !== null && count($index->listBuckets(1, 'data')) === 1;
    },
    'Store\MemoryHotCache constructs + remembers' => static function (): bool {
        $cache = new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024);
        $cache->put('smoke', str_repeat('a', 64), 0, 'bytes');

        return $cache->get('smoke', str_repeat('a', 64), 0) === 'bytes';
    },
    'Store\SyncUploadQueue enqueues single jobs (no wire)' => static function (): bool {
        // A REAL file: UploadJob confines source_path to a registered workspace
        // root, so a payload naming a path that resolves to nothing is refused
        // before any open (that confinement is UploadJobTest's coverage).
        $source = tempnam(sys_get_temp_dir(), 'tf-smoke-');
        file_put_contents((string) $source, 'abc');

        try {
            $queue = new SyncUploadQueue;
            $queue->enqueue([
                'account_id' => 1,
                'bucket' => 'b',
                'name' => 'n',
                'mode' => 'raw',
                'source_path' => $source,
                'size' => 3,
            ]);

            return ! $queue->isAsync();
        } finally {
            @unlink((string) $source);
        }
    },
    'Store\NullSchedulePublisher describes a skipped implementation' => static function (): bool {
        $publisher = new NullSchedulePublisher;

        return str_contains($publisher->describe(), 'Schedule driver: null');
    },
    'Store\TemporalClientResolver constructs and resolves with the SDK-absent path (no Temporal server)' => static function (): bool {
        // The resolver is the one class that could break plain-PHP construction
        // for a host with no temporal/sdk at all: it names SDK symbols. It must
        // load and construct with the SDK absent, and the refusal it hands back
        // must be the NAMED one — a null, a no-op or a different message would be
        // the silent-success this seam exists to kill.
        //
        // The factory seam is the point: a null-returning factory models "the
        // optional SDK is not usable here" without needing ext-grpc, a network,
        // or a Temporal server, so this check runs identically in CI and here.
        $refused = null;

        try {
            (new TemporalClientResolver(null, static fn (): ?RecurringScheduleClient => null))
                ->resolve('127.0.0.1:7233');
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        return $refused === SdkTemporalScheduleClient::sdkMissingMessage();
    },
    'Store\SdkTemporalScheduleClient constructs + refuses by name (SDK-absent, no dial)' => static function (): bool {
        // Construction must touch no socket — a client that dialled in its
        // constructor would open a queue connection during a Laravel boot — and
        // this file must LOAD with the SDK absent (no SDK class is named in a
        // signature or property). A bare stdClass stands in for the SDK object so
        // the check needs no Temporal server; duck typing then has to fail LOUDLY
        // rather than pretend the schedule was armed.
        $client = new SdkTemporalScheduleClient(new stdClass, 'default');

        $refused = null;
        try {
            $client->createOrUpdateSchedule('tstore-backup', 'teleframe:tstore backup', 900, 'tstore');
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        return $client instanceof SdkTemporalScheduleClient
            && $client->namespace() === 'default'
            && $refused !== null
            && str_contains($refused, 'createSchedule()+getHandle()');
    },
    'Store\StoreMaintenanceScheduleSpec constructs plain-PHP and emits DETERMINISTIC ids' => static function (): bool {
        // The tstore maintenance spec is what `teleframe:tstore schedules
        // --install` arms. Two properties matter here and neither is visible
        // without the smoke gate: it constructs with NO arguments and NO
        // container, and its schedule ids are deterministic — a non-deterministic
        // id would make a second arming STACK a second schedule instead of
        // refreshing the one, which is the failure mode the upsert's idempotence
        // exists to prevent.
        $spec = new StoreMaintenanceScheduleSpec(3600, 900);

        $first = $spec->installSpec();
        $second = $spec->installSpec();

        return array_keys($first) === array_keys($second)
            && $first === $second
            && $first !== []
            && array_key_exists('tstore-hot-cache-prune', $first)
            && array_key_exists('tstore-registry-sweep', $first);
    },
    'Store\TemporalSchedulePublisher constructs, refuses without a client, and describes honestly' => static function (): bool {
        // The temporal driver's no-client arm, which is the one a host with
        // neither a binding nor the SDK actually reaches.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233', null, 'tstore');
        $described = $publisher->describe();

        $refused = null;
        try {
            $publisher->install(['tstore-backup' => ['activity' => 'teleframe:tstore backup', 'interval_seconds' => 900]]);
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        return $refused === TemporalSchedulePublisher::missingClientMessage(1)
            && str_contains($described, 'Schedule driver: temporal')
            && str_contains($described, 'No Temporal client is bound')
            // describe() must not claim a schedule is armed before install() ran.
            && ! str_contains(strtolower($described), 'enabled');
    },
    'Store\Schedules\Worker\TemporalWorker constructs plain-PHP, serves the shipped types, and refuses by name without the SDK' => static function (): bool {
        // THE WORKER SIDE OF THE TEMPORAL SEAM. Before this class the package
        // armed real server-side schedules whose workflow type — a raw string
        // handed to StartWorkflowAction::new() — NOTHING implemented, and the
        // publisher said "armed … recurring every …" about it. The gate for that
        // is construction with no container and no server, plus the refusal
        // being LOUD and NAMED when the optional SDK is absent.
        $worker = new TemporalWorker('tstore', [['echo', 'health']]);
        $described = $worker->describe();

        // The two fixed types the backup/notify specs emit, plus one app-call
        // type per declared pair.
        $types = $worker->servedTypes();

        // SDK-absent: the probe stands in for `class_exists`, because in this
        // checkout temporal/sdk IS installed (require-DEV) and the guard could
        // otherwise never be exercised. The refusal must name the install AND
        // the entry point, and must fire before anything is registered.
        $absent = new TemporalWorker('tstore', [], new ContainerResolver, new WorkflowTypeCatalog, static fn (): bool => false);

        $refused = null;
        try {
            $absent->register(new stdClass);
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        $runRefused = null;
        try {
            $absent->run(new class
            {
                public function newWorker(string $queue = 'default'): object
                {
                    throw new RuntimeException('the SDK factory was reached, which it must not be');
                }
            });
        } catch (RuntimeException $e) {
            $runRefused = $e->getMessage();
        }

        // The EXACT list, not a count. This file and
        // tests/Standalone/PlainPhpLoadTest.php both state the served set, and
        // that duplication has drifted twice — most recently when the tstore
        // maintenance workflows were added, where only CI noticed. A count would
        // hide WHICH type went missing; a list says it.
        //
        // The duplication is not solved here, and pretending otherwise would be
        // its own false green: both files run in the gate, and CI is the
        // detector. What changed is that both now state the same list, so a
        // drift names the type instead of moving a number.
        $expected = [
            'teleclient.notify.check-no-show',
            'teleframe.notify.check-no-show',
            'teleframe:tstore backup',
            'teleframe:tstore prune',
            'teleframe:tstore sync',
            'telegram.app.run-call echo health',
        ];
        sort($expected);
        $served = $types;
        sort($served);

        return $served === $expected
            && in_array('teleframe:tstore backup', $types, true)
            // BOTH no-show spellings: the notify seam and the backup seam
            // disagree on the string for the same work, so the worker serves
            // both. Serving one would leave the other seam's schedule dead.
            && in_array('teleclient.notify.check-no-show', $types, true)
            && in_array('teleframe.notify.check-no-show', $types, true)
            // The two tstore maintenance types, which were KNOWN GAPs until a
            // worker served them. A count that let them back out would make
            // this gate pass over a schedule that ticks into nothing.
            && in_array('teleframe:tstore prune', $types, true)
            && in_array('teleframe:tstore sync', $types, true)
            && in_array('telegram.app.run-call echo health', $types, true)
            && count(TemporalWorker::servedActivities()) === 5
            // No container, no server, no seam resolved.
            && $worker->resolver()->seams() === []
            // describe() must never read as proof that a tick fires.
            && str_contains($described, 'no live Temporal server')
            && $refused !== null
            && str_contains($refused, 'composer require temporal/sdk')
            && str_contains($refused, 'php bin/teleframe-worker')
            && $runRefused !== null
            && str_contains($runRefused, 'composer require temporal/sdk');
    },
    'Store\Schedules\Worker\ContainerResolver refuses a container-free activity by seam name' => static function (): bool {
        // The activity half of the same honesty: with nothing bound, a tick must
        // FAIL naming the seam. Returning an empty result would make an unarmed
        // backup look like a clean one — the false green, one layer down.
        $refused = null;
        try {
            (new StoreBackupDumpActivity(ContainerResolver::refusing()))->dump([]);
        } catch (RuntimeException $e) {
            $refused = $e->getMessage();
        }

        return $refused !== null
            && str_contains($refused, StoreBackupDumpActivity::SERVICE_SEAM);
    },
    'Store\StoreService constructs with offline ports only' => static function (): bool {
        $service = new StoreService(
            accountId: 1,
            channelFactory: static function (string $bucket): never {
                throw new RuntimeException('channel factory invoked — smoke constructs only');
            },
            index: new MemoryStoreIndex,
            cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024),
            queue: new SyncUploadQueue,
        );

        return $service instanceof StoreService;
    },

    // Store\Backup (2026-09-24 design wave 1): every class plain-PHP
    // constructible — no container anywhere.
    'Store\Backup\DatabaseTarget constructs (no password field)' => static fn (): bool => new DatabaseTarget('mysql', 'db') instanceof DatabaseTarget,
    'Store\Backup\DatabaseTarget::argc/env carry no password key' => static function (): bool {
        $argv = (new DatabaseTarget('pgsql', 'db', user: 'u', port: 5432))->argc();
        $env = (new DatabaseTarget('mysql', 'db'))->env();
        $blob = implode(' ', $argv).' '.implode(' ', array_keys($env)).' '.implode(' ', array_values($env));

        return stripos($blob, 'PWD') === false && stripos($blob, 'PASSWORD') === false;
    },
    'Store\Backup\DumpWorkspace constructs (spool dir seam)' => static function (): bool {
        return new DumpWorkspace(sys_get_temp_dir()) instanceof DumpWorkspace;
    },
    'Store\Backup\DumpWorkspace spool/release lifecycle' => static function (): bool {
        $dir = sys_get_temp_dir().'/tf-smoke-ws-'.bin2hex(random_bytes(4));
        $ws = new DumpWorkspace($dir);
        $spoolA = $ws->spool();
        $ws->release(keepQueued: true);
        $queuedKept = file_exists($spoolA['path']);
        @unlink($spoolA['path']); // the queue worker's unlink job (putFromJob)

        $spoolB = $ws->spool();
        $ws->release(keepQueued: false);
        $inlineUnlinked = ! file_exists($spoolB['path']);
        @rmdir($dir);

        return $queuedKept && $inlineUnlinked;
    },
    'Store\Backup\DumpResult constructs + objectName shape' => static function (): bool {
        $result = new DumpResult('/tmp/x.sql', 42, 'mysql', 'app', 1_700_000_000, 1_700_000_010, 0, '');
        $name = $result->objectName();

        return $result->toArray()['bytes'] === 42
            && $name === 'app-'.gmdate('Y-m-d-His', 1_700_000_000).'.sql';
    },
    'Store\Backup\BackupException is a RuntimeException' => static fn (): bool => new BackupException('x') instanceof RuntimeException,
    'Store\Backup\BackupSupport carries the five fixed seams' => static function (): bool {
        return BackupSupport::SERVICE_KEY === 'teleclient.store.backup.service'
            && BackupSupport::DUMPER_KEY === 'teleclient.store.backup.dumper'
            && BackupSupport::INDEX_KEY === 'teleclient.store.backup.index'
            && BackupSupport::WATCHDOG_KEY === 'teleclient.store.backup.watchdog'
            && BackupSupport::SCHEDULES_KEY === 'teleclient.store.backup.schedules'
            // wave-2-D additive consts (safe: no existing key changed)
            && BackupSupport::TARGETS_KEY === 'teleclient.store.backup.targets'
            && BackupSupport::SCHEDULER_KEY === 'teleclient.store.backup.scheduler';
    },
    'Store\Backup\MysqlDumper constructs (injectable proc runner)' => static fn (): bool => new MysqlDumper instanceof MysqlDumper,
    'Store\Backup\PostgresDumper constructs (injectable proc runner)' => static fn (): bool => new PostgresDumper instanceof PostgresDumper,
    'Store\Backup\EloquentBackupRunIndex constructs (no DB at ctor)' => static fn (): bool => new EloquentBackupRunIndex instanceof EloquentBackupRunIndex,
    'Store\Backup\BackupRunReport constructs + toArray' => static fn (): bool => (new BackupRunReport('b', 'n', 'mysql', 'db', 1, 2, 'h', 'sealed', 123, false))->toArray()['mode'] === 'sealed',
    'Store\Backup\BackupScheduleSpec constructs' => static fn (): bool => new BackupScheduleSpec(86400) instanceof BackupScheduleSpec,
    'Store\Backup\MemoryBackupRunIndex constructs' => static fn (): bool => new MemoryBackupRunIndex instanceof MemoryBackupRunIndex,
    'Store\Backup\DatabaseBackupService constructs plain-PHP (stub dumper + memory ledger)' => static function (): bool {
        $dumper = new class implements DatabaseDumper
        {
            public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
            {
                throw new RuntimeException('smoke — stub dumper never runs');
            }
        };

        $service = new DatabaseBackupService(
            accountId: 1,
            dumper: $dumper,
            store: new StoreService(
                accountId: 1,
                channelFactory: static function (string $bucket): never {
                    throw new RuntimeException('channel factory invoked — smoke constructs only');
                },
                index: new MemoryStoreIndex,
                cache: new MemoryHotCache(ttlSeconds: 60, maxBytes: 1024),
                queue: new SyncUploadQueue,
            ),
            runs: new MemoryBackupRunIndex,
        );

        return $service instanceof DatabaseBackupService;
    },
    'Store\Backup\TemporalBackupScheduler adapts the schedule spec through the NullSchedulePublisher seam' => static function (): bool {
        $scheduler = new TemporalBackupScheduler(
            new NullSchedulePublisher,
            new BackupScheduleSpec(86400),
        );
        $status = $scheduler->install();

        return $scheduler instanceof TemporalBackupScheduler
            && $status['backup-dump'] === 'skipped (null schedule driver)'
            && $status['backup-watchdog-check'] === 'skipped (null schedule driver)'
            && str_contains($scheduler->describe(), 'Schedule driver: null');
    },

    // Laravel\Store (Cycle 38 — Goal D): the S3 DURABLE backup tier is
    // plain-PHP constructible with no container, no secret and no MinIO, and
    // its manifest/chunk round-trip works against an in-memory gateway.
    'Laravel\Store\S3DurableVault constructs and round-trips a manifest + chunk offline' => static function (): bool {
        $gateway = new SmokeS3Gateway;
        $vault = new S3DurableVault('smoke-set', $gateway);

        // A chunk is named by its CIPHERTEXT hash, never by the plaintext
        // content address the manifest records, so the object key must not
        // leak the plaintext hash.
        $plainHash = hash('sha256', 'plaintext');
        $wireHash = hash('sha256', 'ciphertext');
        $vault->putChunk($wireHash, 'ciphertext');

        $manifest = json_encode([
            'generation' => 1,
            'created' => '2026-09-24T00:00:00+00:00',
            'files' => [['name' => 'smoke.bin', 'size' => 3]],
            'chunk_hashes' => [$plainHash],
            'wire_chunks' => [$wireHash => $plainHash],
        ], JSON_THROW_ON_ERROR);
        $vault->putManifest($manifest);

        $keys = array_keys($gateway->listAsMap());
        $namespaced = array_filter($keys, static fn (string $k): bool => str_starts_with($k, 'teleframe-backup/smoke-set/'));
        $chunkKey = array_values(array_filter($keys, static fn (string $k): bool => str_contains($k, '/chunks/')))[0] ?? null;
        $latest = $vault->getLatestManifest();

        return $vault instanceof S3DurableVault
            && $vault->gateway() === $gateway
            && count($namespaced) === count($keys)
            && $chunkKey === 'teleframe-backup/smoke-set/chunks/'.$wireHash
            // The plaintext hash must be invisible in the object NAME.
            && ! in_array($plainHash, array_map(static fn (string $k): string => basename($k), $keys), true)
            // A reader resolves the PLAINTEXT address the manifest records.
            && $latest !== null
            && $latest['generation'] === 1
            && $vault->getChunk($plainHash) === 'ciphertext'
            // A missing chunk is a loud RuntimeException, never an empty string.
            && (static function () use ($vault): bool {
                try {
                    $vault->getChunk(hash('sha256', 'absent'));
                } catch (RuntimeException) {
                    return true;
                }

                return false;
            })();
    },

    // App (bot/user app framework — 2026-09-24 design §2/§3/§9): every
    // class plain-PHP constructible — no container, no facade anywhere.
    'App\AppSupport carries the three fixed seams' => static function (): bool {
        return AppSupport::SERVICE_KEY === 'teleclient.app.service'
            && AppSupport::SCHEDULES_KEY === 'teleclient.app.schedules'
            && AppSupport::TRANSPORT_KEY === 'teleclient.app.transport';
    },
    'App\AppMode is a backed enum bot|user' => static function (): bool {
        return AppMode::Bot instanceof BackedEnum
            && AppMode::Bot->value === 'bot'
            && AppMode::User->value === 'user';
    },
    'App\AppException is a RuntimeException' => static fn (): bool => new AppException('x') instanceof RuntimeException,
    'App\AppTrigger::on constructs (shipped matcher grammar only)' => static function (): bool {
        $trigger = AppTrigger::on('updateNewMessage', static function (Update $u): void {}, priority: 1, onOwn: true);
        $row = $trigger->toArray();

        return $trigger instanceof AppTrigger
            && $row['match'] === 'updateNewMessage'
            && $row['priority'] === 1
            && $row['on_own'] === true;
    },
    'App\AppCall::every constructs (name/interval/method/params)' => static function (): bool {
        $call = AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops', 'message' => 'alive']);
        $row = $call->toArray();

        return $call instanceof AppCall
            && $row['interval_seconds'] === 3600
            && $row['method'] === 'messages.sendMessage'
            && $row['params'] === ['peer' => '@ops', 'message' => 'alive'];
    },
    'App\AppDefinition::define constructs + triggerFor resolves' => static function (): bool {
        $trigger = AppTrigger::on('updateNewMessage', static function (Update $u): void {});
        $call = AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']);
        $app = AppDefinition::define('echo-bot', AppMode::Bot, [$trigger], [$call]);

        return $app->name === 'echo-bot'
            && $app->mode === AppMode::Bot
            && $app->triggers === [$trigger]
            && $app->calls === [$call]
            && $app->triggerFor('updateNewMessage') === $trigger
            && $app->triggerFor('missing') === null;
    },
    'App\AppRegistry defines + resolves; unknown name throws AppException' => static function (): bool {
        $app = AppDefinition::define('echo-bot', AppMode::Bot);
        $registry = (new AppRegistry)->define($app);
        $unknown = false;
        try {
            $registry->resolve('missing');
        } catch (AppException) {
            $unknown = true;
        }

        return $registry->resolve('echo-bot') === $app
            && $registry->all() === ['echo-bot' => $app]
            && $unknown;
    },
    'App\AppResponder installs triggers onto a plain HandlerRegistry under tag app:{name}' => static function (): bool {
        $app = AppDefinition::define('echo-bot', AppMode::Bot, [AppTrigger::on('updateNewMessage', static function (Update $u): void {})]);
        $handlerTable = new HandlerRegistry;
        $responder = new AppResponder($handlerTable);
        $installed = $responder->install($app);
        $seen = $handlerTable->compile();
        $removed = $responder->uninstall('echo-bot');

        return $installed === 1
            && count($seen) === 1
            && $seen[0]->match === 'updateNewMessage'
            && $seen[0]->tag === 'app:echo-bot'
            && $removed === 1
            && $handlerTable->count() === 1; // append-only hot table (design §11): uninstall = tagged-row introspection, rows stay live
    },
    'App\AppCallSpec builds the app-call-{app}-{call}-{digest} install payload' => static function (): bool {
        $app = AppDefinition::define('echo-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['peer' => '@ops']),
        ]);
        $spec = new AppCallSpec;
        $id = $spec->callId($app, $app->calls()[0]);
        $payload = $spec->installSpec($app)[$id] ?? null;

        // The id is a function of the (app, call) PAIR: unique WITHIN an app is
        // not enough, because two apps declaring `status-ping` used to install
        // the same schedule id and silently upsert over each other.
        $otherApp = AppDefinition::define('other-bot', AppMode::Bot, calls: [
            AppCall::every('status-ping', 60, 'messages.sendMessage'),
        ]);

        return $payload !== null
            && str_starts_with($id, 'app-call-echo-bot-status-ping-')
            && $id !== $spec->callId($otherApp, $otherApp->calls()[0])
            && $payload['interval_seconds'] === 3600
            && $payload['activity'] === 'telegram.app.run-call echo-bot status-ping'
            && $payload['app'] === 'echo-bot'
            && $payload['call'] === 'status-ping';
    },
    'App\AppTransportResolver resolves through an injected callable' => static function (): bool {
        $resolver = new AppTransportResolver(static fn (AppMode $mode): string => $mode === AppMode::Bot ? 'bot-scope' : 'user-scope');

        return $resolver->resolve(AppMode::Bot) === 'bot-scope'
            && $resolver->resolveFor(AppDefinition::define('u', AppMode::User)) === 'user-scope';
    },
    'App\AppRuntime registers + runs a call + installs calls (faked transport, null seam)' => static function (): bool {
        // user mode: the mtproto catalog method passes the §5/§6 Q4 mode gate
        $app = AppDefinition::define('echo-bot', AppMode::User, calls: [
            AppCall::every('status-ping', 3600, 'messages.sendMessage', ['message' => 'alive']),
        ]);
        $invoked = [];
        $runtime = new AppRuntime(
            registry: new AppRegistry,
            schedules: new NullSchedulePublisher,
            transport: static function (AppMode $mode) use (&$invoked): object {
                $invoked[] = $mode;

                return new class
                {
                    /** @param array<string, mixed> $params */
                    public function call(string $method, array $params = []): array
                    {
                        return ['_' => $method, 'ok' => true];
                    }
                };
            },
        );
        $name = $runtime->register($app);
        $result = $runtime->runCall('echo-bot', 'status-ping');
        $status = $runtime->installCalls();

        $callId = (new AppCallSpec)->callId($app, $app->calls()[0]);

        return $name === 'echo-bot'
            && $result === ['_' => 'messages.sendMessage', 'ok' => true]
            && $invoked === [AppMode::User]
            && $status === [$callId => 'skipped (null schedule driver)'];
    },

    // Passport (2026-09-24 design wave 1): every class plain-PHP
    // constructible — no container, no facade anywhere.
    'Passport\PassportSupport carries the fixed seams' => static function (): bool {
        return PassportSupport::SERVICE_KEY === 'teleclient.passport.service'
            && PassportSupport::KEY_LOADER_KEY === 'teleclient.passport.keys'
            && PassportSupport::NONCES_KEY === 'teleclient.passport.nonces'
            && PassportSupport::IDENTITY_KEY === 'teleclient.passport.identity'
            && PassportSupport::WRITER_KEY === 'teleclient.passport.writer'
            && PassportSupport::CONFIRMER_KEY === 'teleclient.passport.confirmer';
    },
    'Passport\RedisAtomicCacheStore refuses to degrade on a non-Redis store' => static function (): bool {
        // Plain-PHP constructible with NO illuminate/redis present: a named
        // capability must be constructible before it is ever usable, or a
        // consumer without Redis cannot even autoload it.
        $nonRedis = new class implements CacheInterface
        {
            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                return true;
            }

            public function delete(string $key): bool
            {
                return true;
            }

            public function clear(): bool
            {
                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return false;
            }
        };

        // Refuses rather than silently degrading to get-then-delete: a caller
        // that asked for atomicity must never be handed a weaker guarantee.
        return RedisAtomicCacheStore::supports($nonRedis) === false
            && $nonRedis instanceof CacheInterface
            && (new PassportConfig([]))->requireAtomicNonce() === false;
    },
    'Passport\PassportLoginException is a RuntimeException' => static fn (): bool => new PassportLoginException('x') instanceof RuntimeException,
    'Passport\PassportConfig is inert by default (enabled=false)' => static fn (): bool => (new PassportConfig([]))->enabled() === false,
    'Passport\MemoryNonceRegistry issues a 32-hex nonce and consumes it once' => static function (): bool {
        $nonces = new MemoryNonceRegistry(900);
        $nonce = $nonces->issue(7);
        $consumed = $nonces->consume($nonce);
        $replay = $nonces->consume($nonce);

        return strlen($nonce) === 32 && $consumed === 7 && $replay === null;
    },
    'Passport\LoginResult constructs + toArray shape' => static function (): bool {
        $user = new class extends Model {};
        $result = new LoginResult(
            tgUserId: 42,
            accountId: 7,
            user: $user,
            binding: ['tg_id' => 42, 'account_id' => 7, 'user' => $user],
            fieldsVerified: ['personal_details', 'email'],
        );
        $row = $result->toArray();

        return $result->tgUserId === 42
            && $row['tg_user_id'] === 42
            && $row['account_id'] === 7
            && $row['fields_verified'] === ['personal_details', 'email'];
    },
    'Passport\PassportLoginService constructs plain-PHP + silent handleUpdate + issueNonce' => static function (): bool {
        $service = new PassportLoginService(
            config: new PassportConfig([]),
            keys: new PassportKeyLoader('/nonexistent.pem'), // never read — construct only
            nonces: new MemoryNonceRegistry(900),
            identity: new SessionIdentityResolver('No\\Such\\UserClass'), // resolve() would need a DB — construct only
            writer: new MemoryBindingWriter,
        );
        $silent = $service->handleUpdate(['_' => 'updateNewMessage', 'message' => ['text' => 'hi']]);
        $nonce = $service->issueNonce(7);

        return $service->service() === $service
            && $silent === null
            && strlen($nonce) === 32;
    },

    // The ANONYMOUS passport LOGIN family (2026-09-26): the highest-risk surface
    // in the module — it can turn a sessionless visitor into a logged-in user.
    // Every one of its classes must be plain-PHP constructible, and the two
    // things that make the binding non-optional must hold with NO container:
    // a non-nullable fingerprint, and a fingerprint of nothing that is refused
    // rather than digested into a constant.
    'Passport\SessionFingerprint digests a session, refuses a blank one, and matches in constant time' => static function (): bool {
        $a = SessionFingerprint::of('session-a');
        $b = SessionFingerprint::of('session-b');

        $blank = false;
        try {
            SessionFingerprint::of('');
        } catch (PassportLoginException) {
            $blank = true;
        }

        return $a === hash('sha256', 'session-a')
            && $a !== $b
            && SessionFingerprint::matches($a, $a)
            && ! SessionFingerprint::matches($a, $b)
            && $blank;
    },
    'Passport\MemoryPendingLoginRegistry issues, binds to a fingerprint, and refuses a replay + an expiry' => static function (): bool {
        $clock = 1000;
        $registry = new MemoryPendingLoginRegistry(60, static function () use (&$clock): int {
            return $clock;
        });

        $ticket = $registry->issue('fingerprint-a');
        $consumed = $registry->consume($ticket->token());

        // Same token twice: the second consume finds nothing at all.
        $replay = $registry->consume($ticket->token());

        $expiring = $registry->issue('fingerprint-a');
        $clock += 61;
        $expired = $registry->consume($expiring->token());

        return $consumed instanceof PendingLoginBinding
            && $consumed->sessionFingerprint() === 'fingerprint-a'
            && strlen($consumed->nonceDigest()) === 64
            && $replay === null
            && $expired === null
            && strlen($ticket->token()) === 32
            && strlen($ticket->nonce()) === 32
            && $ticket->token() !== $ticket->nonce();
    },
    'Passport\CachePendingLoginRegistry binds digests, never the nonce, and consumes one-time' => static function (): bool {
        // A recording PSR-16 double rather than illuminate/cache: the point is
        // what the registry WRITES, and a framework store would prove nothing
        // about that beyond that a cache works.
        $store = new class implements CacheInterface
        {
            /** @var array<string, mixed> */
            public array $entries = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->entries[$key] ?? $default;
            }

            public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
            {
                $this->entries[$key] = $value;

                return true;
            }

            public function delete(string $key): bool
            {
                unset($this->entries[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->entries = [];

                return true;
            }

            public function getMultiple(iterable $keys, mixed $default = null): iterable
            {
                return [];
            }

            public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple(iterable $keys): bool
            {
                return true;
            }

            public function has(string $key): bool
            {
                return isset($this->entries[$key]);
            }
        };

        $registry = new CachePendingLoginRegistry($store, 60, 'teleframe.passport.pending.');

        $ticket = $registry->issue('fingerprint-a');

        // What the store holds BEFORE the consume: the VALUE is two digests and
        // nothing else — no plaintext nonce, no session id. (The token appears
        // in its own KEY, which is inherent to a keyed store and is not the
        // secret: the marker it addresses is spent by the time anyone can read
        // a dump.)
        $dumped = (string) json_encode($store->entries);
        $stored = $store->entries['teleframe.passport.pending.'.$ticket->token()] ?? null;
        $binding = $registry->consume($ticket->token());

        return $binding instanceof PendingLoginBinding
            && $binding->sessionFingerprint() === 'fingerprint-a'
            && $binding->nonceDigest() === hash('sha256', $ticket->nonce())
            && is_array($stored)
            && array_keys($stored) === ['sf', 'nd']
            && ! str_contains($dumped, $ticket->nonce())
            && $registry->consume($ticket->token()) === null
            && $registry->isAtomic() === false
            && $registry->consume('') === null;
    },
    'Passport\PassportAnonymousLoginService constructs plain-PHP and is INERT by default' => static function (): bool {
        $service = new PassportAnonymousLoginService(
            config: new PassportConfig([]), // everything off
            keys: new PassportKeyLoader('/nonexistent.pem'), // never read — construct only
            pending: new MemoryPendingLoginRegistry(60),
            writer: new MemoryBindingWriter,
        );

        $refused = false;
        try {
            $service->start(SessionFingerprint::of('a-session'));
        } catch (PassportLoginException) {
            $refused = true;
        }

        // The binding is NOT optional: the parameter is a non-nullable string
        // with no default, so no call site can omit the comparison.
        $param = new ReflectionMethod($service, 'login');
        $fingerprint = $param->getParameters()[2];

        return $refused
            && $service->service() === $service
            && $fingerprint->getName() === 'sessionFingerprint'
            && ! $fingerprint->allowsNull()
            && ! $fingerprint->isOptional()
            && ! $fingerprint->isDefaultValueAvailable();
    },

    // Passport WIRE family (Cycle 18, 2026-09-25): the constructors the 2026-09-24
    // audit wrongly called "not wire-callable" — plain-PHP constructible, and the
    // OFFICIAL layer-227 ids carried as data (gap analysis §3.1).
    'Passport\PassportSupport carries the wire seams' => static function (): bool {
        return PassportSupport::SCOPE_KEY === 'teleclient.passport.scope'
            && PassportSupport::WIRE_KEY === 'teleclient.passport.wire'
            && PassportSupport::IMPORTER_KEY === 'teleclient.passport.importer'
            && PassportSupport::EXPORTER_KEY === 'teleclient.passport.exporter'
            && PassportSupport::PROFILE_KEY === 'teleclient.passport.profile'
            && PassportSupport::FORM_KEY === 'teleclient.passport.form'
            && PassportSupport::WIRE_LOGIN_KEY === 'teleclient.passport.wire-login'
            && in_array(PassportSupport::WIRE_LOGIN_KEY, PassportSupport::seams(), true)
            && count(PassportSupport::seams()) === 12;
    },
    'Passport\PassportWireContract carries the official layer-227 ids' => static function (): bool {
        return [
            'auth.exportAuthorization' => 'e5bfffcd',
            'auth.importAuthorization' => 'a57a7dad',
            'help.getPassportConfig' => 'c661ad08',
            'account.getAuthorizationForm' => 'a929597a',
            'account.acceptAuthorization' => 'f3ed4c73',
        ] === PassportWireContract::OFFICIAL_IDS;
    },
    'Passport\MemoryPassportWire records a dispatch and refuses an unscripted one' => static function (): bool {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => [
                ['_' => 'auth.authorization', 'user' => ['id' => 42]],
            ],
        ]);
        $imported = (new AuthorizationImporter($wire))->import(7, 'bytes');

        $refused = false;
        try {
            $wire->call('auth.exportAuthorization', ['dc_id' => 0]);
        } catch (PassportLoginException) {
            $refused = true;
        }

        return $imported->userId === 42
            // The refused call IS recorded: it left the seam, so the log must show it.
            && $wire->firstDispatchOf('auth.importAuthorization') !== null
            && $wire->firstDispatchOf('auth.importAuthorization')['params'] === ['id' => 7, 'bytes' => 'bytes']
            && $wire->dispatchCount() === 2
            && $refused;
    },
    'Passport\UserScopePassportWire with no scope refuses loud (no dispatch)' => static function (): bool {
        try {
            (new UserScopePassportWire)->call(PassportWireContract::METHOD_IMPORT_AUTHORIZATION, ['id' => 1, 'bytes' => 'b']);
        } catch (PassportLoginException $e) {
            return str_contains($e->getMessage(), 'no scope, no dispatch');
        }

        return false;
    },
    'Passport\AuthorizationExporter + PassportProfileFetcher construct and parse' => static function (): bool {
        $exportWire = new MemoryPassportWire([
            PassportWireContract::METHOD_EXPORT_AUTHORIZATION => [
                ['_' => 'auth.exportedAuthorization', 'id' => 3, 'bytes' => 'opaque'],
            ],
            PassportWireContract::METHOD_PASSPORT_CONFIG => [
                ['_' => 'help.passportConfig', 'hash' => 5, 'countries_langs' => '[["US","en"]]'],
            ],
        ]);

        $exported = (new AuthorizationExporter($exportWire))->export();
        $profile = (new PassportProfileFetcher($exportWire))->fetch();

        return $exported->id === 3
            && $exported->toArray() === ['id' => 3, 'bytes_length' => 6]
            && $profile->servesCountry('US')
            && $exportWire->dispatchCount() === 2;
    },
    'Passport\AuthorizationFormService surfaces every per-field secureValueError' => static function (): bool {
        $wire = new MemoryPassportWire([
            PassportWireContract::METHOD_AUTHORIZATION_FORM => [
                [
                    '_' => 'account.authorizationForm',
                    'users' => [['id' => 9]],
                    'errors' => [
                        ['_' => 'secureValueErrorData', 'type' => 'phone', 'field' => 'phone_number', 'text' => 'bad'],
                        ['_' => 'secureValueErrorFile', 'type' => 'passport', 'text' => 'unreadable'],
                    ],
                ],
            ],
        ]);

        $form = (new AuthorizationFormService($wire))->request(1, 'passport', 'PEM');

        try {
            $form->assertNoFieldErrors('account.getAuthorizationForm');
            $raised = null;
        } catch (PassportFieldErrorsException $e) {
            $raised = $e;
        }

        return $form instanceof PassportAuthorizationForm
            && $form->userId() === 9
            && $raised instanceof PassportFieldErrorsException
            && $raised->count() === 2
            && $raised->errors()[1] instanceof PassportFieldError;
    },
    'Passport\PassportProfile + ImportedAuthorization are credential-free' => static function (): bool {
        $imported = new ImportedAuthorization(42, ['id' => 42, 'first_name' => 'Ada']);

        return (new PassportProfile(1))->toArray()['hash'] === 1
            && $imported->toArray()['user_id_string'] === '42'
            && PassportRefusal::PASSWORD_REQUIRED === 'PASSWORD_REQUIRED';
    },
    'Passport\PasswordConfirmer is the 2FA contract; PasswordRequiredException is a PassportLoginException' => static function (): bool {
        $confirmer = new PasswordConfirmer;

        return $confirmer instanceof PasswordConfirmerContract
            && (new PasswordRequiredException) instanceof PassportLoginException
            && (new PasswordRequiredException)->getCode() === 400;
    },
    'Passport\PassportWireLogin is inert when unconfigured (no dispatch)' => static function (): bool {
        $wire = new MemoryPassportWire;
        $login = new PassportWireLogin(
            config: new PassportConfig([]),
            importer: new AuthorizationImporter($wire),
            writer: new MemoryBindingWriter,
        );

        try {
            $login->login(1, 'b', new class extends Model {});
        } catch (PassportLoginException $e) {
            return $wire->dispatchCount() === 0 && str_contains($e->getMessage(), 'not enabled');
        }

        return false;
    },

    // Schema
    'Schema\TeleframeSchemeLoader::parseString' => static function (): bool {
        $scheme = TeleframeSchemeLoader::parseString('bool#0c734172 = Bool;');

        return $scheme !== null && $scheme->types() !== [];
    },
    'Schema\SchemaRegenerator::loadScheme (committed sources)' => static function (): bool {
        $regenerator = new SchemaRegenerator;
        $scheme = $regenerator->loadScheme(__DIR__.'/../schema/sources');

        return $scheme->types() !== [];
    },

    // Daemon
    'Daemon\AccountWorker constructs (injected factories, no wire)' => static fn (): bool => new AccountWorker(
        ['account_id' => 1, 'flood_cap_seconds' => 3600],
        static function (): void {
            throw new RuntimeException('scope factory invoked — smoke never touches the wire');
        },
        null,
        static fn (int $s): bool => true,
    ) instanceof AccountWorker,

    // Handler (Phase 3)
    'Handler substrate constructs (registry/pipeline/update/dispatcher/cache)' => static function (): bool {
        $registry = new HandlerRegistry;
        $dispatcher = new UpdateDispatcher($registry, new Pipeline, new StackContainer($registry, new InMemoryCache), new InMemoryCache, []);

        return Update::fromBus(['_' => 'x'], 1) instanceof Update
            && $dispatcher instanceof UpdateDispatcher;
    },
    'Handler registry singleton shares ONE table per process' => static function (): bool {
        HandlerRegistry::reset();
        $a = HandlerRegistry::instance();
        $b = HandlerRegistry::instance();
        $a->onMessage('App\\HotHandler');

        return $a === $b && $b->count() === 1;
    },
    'FakeRunningMode drives the REAL pipeline plain-PHP' => static function (): bool {
        $registry = new HandlerRegistry;
        $fired = 0;
        $registry->onMessage(static function (Update $u) use (&$fired): void {
            $fired++;
        });
        $sends = new InMemoryCache;
        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage'], 'account_id' => 7]],
            $registry,
            new StackContainer($registry, $sends),
            $sends,
        );
        $result = $fake->run();

        return $fired === 1 && count($result['dispatched']) === 1;
    },
    'Q2 echo elimination in the REAL pipeline (plain-PHP)' => static function (): bool {
        $registry = new HandlerRegistry;
        $fired = 0;
        $registry->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $sends = new InMemoryCache;
        $eliminator = new EchoEliminator(
            $sends,
            new HandlerMatcher($registry),
        );
        $eliminator->remember(7, ['random_id' => 'r-1']);
        $fake = new FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-1'], 'account_id' => 7]],
            $registry,
            new StackContainer($registry, $sends),
            $sends,
        );
        $fake->run();

        return $fired === 0; // echo eliminated before the handler ran
    },
    'Teleframe facade composes + onMessage + run (plain-PHP)' => static function (): bool {
        $registry = new HandlerRegistry;
        $sends = new InMemoryCache;
        $teleframe = new Teleframe(new StackContainer($registry, $sends));
        $fired = 0;
        $teleframe->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $teleframe->run([['update' => ['_' => 'updateNewMessage'], 'account_id' => 1]]);

        return $fired === 1;
    },
    // Realtime (Phase 4)
    'Realtime\ChannelNames accountUpdates format' => static fn (): bool => ChannelNames::accountUpdates(42) === 'account:42:updates',
    'Realtime\ChannelNames accountMessages format' => static fn (): bool => ChannelNames::accountMessages(42, 99) === 'account:42:messages:99',
    'Realtime\HttpCentrifugoBridge constructs' => static fn (): bool => new HttpCentrifugoBridge('http://localhost:8000/api', 'key', 'secret') instanceof HttpCentrifugoBridge,
    'Realtime\CentrifugoBridge interface exists' => static fn (): bool => interface_exists(CentrifugoBridge::class),

    'Facade send writes the elimination registry; echo consumed by dispatcher' => static function (): bool {
        $registry = new HandlerRegistry;
        $sends = new InMemoryCache;
        $teleframe = new Teleframe(new StackContainer($registry, $sends));
        $fired = 0;
        $teleframe->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $teleframe->send(7, ['random_id' => 'r-2']);
        $teleframe->run([['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-2'], 'account_id' => 7]]);

        return $fired === 0; // the recorded send's echo never re-entered the handler
    },

    // Relay terminal (Wave C): RFC6455 codec + WS obfuscated2 terminal
    'WebSocketFrameCodec decodes a built binary frame' => static function (): bool {
        $frame = WebSocketFrameCodec::frame(
            WebSocketFrameCodec::OPCODE_BINARY,
            'smoke-payload',
        );
        $parsed = WebSocketFrameCodec::parseFrame($frame);

        return $parsed !== null && $parsed['payload'] === 'smoke-payload';
    },
    'WebSocketRelayServer constructs without dialing (injected resolver)' => static function (): bool {
        $relay = new WebSocketRelayServer(
            '127.0.0.1',
            4443,
            static function (int $dcId, string $protocol): void {
                throw new RuntimeException('resolver invoked — smoke never dials');
            },
        );

        return $relay instanceof WebSocketRelayServer;
    },

    // Client-in terminal (Wave C shape #2): the "connect to our system" path
    'RelayTerminal dispatches outbound + produces the inbound reply (fake scope)' => static function (): bool {
        $client = new class
        {
            /** @var list<array{0: string, 1: array<string, mixed>}> */
            public array $calls = [];

            /** @param array<string, mixed> $params */
            public function call(string $method, array $params = []): array
            {
                $this->calls[] = [$method, $params];

                return ['_' => $method, 'ok' => true];
            }
        };
        $terminal = new RelayTerminal(static fn (): object => (object) ['client' => $client]);
        $reply = $terminal(new RelayMessage(RelayMessage::OUTBOUND, 'messages.getDialogs', ['limit' => 7]));

        return $client->calls === [['messages.getDialogs', ['limit' => 7]]]
            && $reply->direction === RelayMessage::INBOUND
            && $reply->params === ['_' => 'messages.getDialogs', 'ok' => true];
    },
    'RelayFrameDialBridge constructs + seats a pump without dialing' => static function (): bool {
        $session = new SessionData(1, 'smoke-arm');
        $conn = new EncryptedConnection($session);
        $bridge = (new RelayFrameDialBridge($conn, $session, null))->withFramePump(
            static function (EncryptedConnection $connection, mixed $frame): void {
                // Seating only — the encrypted socket opens lazily on the first RPC.
            },
        );
        $bridge->pumpFrame(['op' => 'ping']);

        return $bridge->surface() === $conn
            && $bridge->lease() === $session
            && ! $conn->isInited();
    },
    'RelayFactory::bridgeFor builds a lazy frame-dial bridge without resolving the vault' => static function (): bool {
        $resolved = 0;
        $factory = new RelayFactory(new RelaySessionResolver(
            vaultLoader: static fn (string $ref, int $dc): ?string => 'smoke-bridge',
            clientFactory: function (string $session, int $dc) use (&$resolved): object {
                $resolved++;

                return new class($session)
                {
                    public SessionData $session;

                    public function __construct(string $session)
                    {
                        $this->session = new SessionData(4, $session);
                    }

                    public function getSession(): SessionData
                    {
                        return $this->session;
                    }
                };
            },
        ));

        $bridge = $factory->bridgeFor(new RelayContext(accountId: 7, dcId: 4, label: 'grace'));
        $resolvedAtBuild = $resolved;
        $seated = $bridge->surface();

        return $bridge instanceof RelayFrameDialBridge
            && $resolvedAtBuild === 0
            && $seated->lastSessionData()->authKey === 'smoke-bridge'
            && ! $seated->isInited();
    },

    // Cycle 6: Laravel-session read-only scope is plain-PHP + OFF by default
    'ReadOnlyScope is off by default, an open frame marks it read-only' => static function (): bool {
        $scope = new ReadOnlyScope;
        $wasOff = ! $scope->isReadOnly();
        $scope->begin();
        $isOn = $scope->isReadOnly();
        $scope->end();

        return $wasOff && $isOn && ! $scope->isReadOnly();
    },
    'ReadOnlyVaultDispatch refuses a write in scope, passes reads' => static function (): bool {
        $calls = 0;
        $scope = new ReadOnlyScope;
        $dispatch = new ReadOnlyVaultDispatch(
            static function (string $method, array $params) use (&$calls): array {
                $calls++;

                return ['_' => $method, 'ok' => true];
            },
            $scope,
        );
        $scope->begin();
        $denied = $dispatch('messages.sendMessage', []);
        $afterDenied = $calls;
        $passed = $dispatch('messages.getDialogs', ['limit' => 7]);
        $afterPassed = $calls;
        $scope->end();
        $outside = $dispatch('messages.sendMessage', []);
        $afterOutside = $calls;

        return $denied['_'] === 'rpc_error'
            && $denied['error_code'] === 400
            && $denied['error_message'] === 'READONLY_SCOPE_FORBIDDEN'
            && $afterDenied === 0
            && $passed['_'] === 'messages.getDialogs'
            && $afterPassed === 1
            && $outside['_'] === 'messages.sendMessage'
            && $afterOutside === 2;
    },
    'ScopeTelegramReadOnly middleware constructs plain-PHP (fake auth resolver)' => static function (): bool {
        $middleware = new ScopeTelegramReadOnly(new ReadOnlyScope, static fn (): bool => true);

        return $middleware instanceof ScopeTelegramReadOnly;
    },
    'RequireTelegramUserSession constructs plain-PHP + refuses a session-less call' => static function (): bool {
        $guard = new RequireTelegramUserSession(static fn (): bool => false);
        $reached = false;
        $refused = $guard->handle(
            Request::create('/tg/'),
            static function () use (&$reached): Response {
                $reached = true;

                return new Response;
            },
        );

        return $guard instanceof RequireTelegramUserSession
            && $refused->getStatusCode() === 401
            && $reached === false;
    },
];

$failed = false;
foreach ($checks as $label => $check) {
    try {
        $ok = $check();
    } catch (Throwable $e) {
        printf("  ✗ %s — %s: %s\n", $label, $e::class, $e->getMessage());
        $failed = true;

        continue;
    }
    printf("%s %s\n", $ok ? '  ✓' : '  ✗', $label);
    $failed = $failed || ! $ok;
}

exit($failed ? 1 : 0);
