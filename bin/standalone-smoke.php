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

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use MeRezaRezaei\Teleframe\Backfill\BackfillWorker;
use MeRezaRezaei\Teleframe\Backfill\FetchQueue;
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
use MeRezaRezaei\Teleframe\Daemon\AccountWorker;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\IdentityLock;
use MeRezaRezaei\Teleframe\Ingest\PayloadWalker;
use MeRezaRezaei\Teleframe\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\TeleframeSchemeLoader;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use MeRezaRezaei\Teleframe\Realtime\HttpCentrifugoBridge;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Teleframe;
use Psr\SimpleCache\CacheInterface;

/** Minimal in-memory RedisConnectionContract double (no laravel redis). */
final class SmokeRedis implements RedisConnectionContract
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $streams = [];

    /** @var array<string, array<string, string>> */
    private array $hashes = [];

    public int $received = 0;

    public function xadd(string $stream, string $id, array $fields): string
    {
        $entryId = ($id === '*') ? (string) (count($this->streams[$stream] ?? []) + 1) : $id;
        $this->streams[$stream][$entryId] = $fields;

        return $entryId;
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

    public function xack(string $stream, string $group, array $ids): int { return count($ids); }

    public function hgetall(string $key): array { return $this->hashes[$key] ?? []; }

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

    public function llen(string $key): int { return count($this->streams[$key] ?? []); }

    public function hget(string $key, string $field): ?string { return $this->hashes[$key][$field] ?? null; }

    public function expire(string $key, int $seconds): bool { return true; }
}

/**
 * Framework-free PSR-11 + PSR-16 double for the Phase 3 handler stack:
 * resolves the singleton registry/dispatcher and shares ONE sends cache
 * between the facade's send path and the echo eliminator.
 */
final class StackContainer implements \Psr\Container\ContainerInterface
{
    private ?UpdateDispatcher $dispatcher = null;

    public function __construct(
        private readonly HandlerRegistry $registry,
        private readonly InMemoryCache $sends,
    ) {
    }

    public function get(string $id): mixed
    {
        if ($id === HandlerRegistry::class) {
            return $this->registry;
        }
        if ($id === CacheInterface::class) {
            return $this->sends;
        }
        if ($id === UpdateDispatcher::class) {
            return $this->dispatcher ??= new UpdateDispatcher($this->registry, new Pipeline(), $this, $this->sends);
        }

        throw new \RuntimeException('smoke stack: ' . $id . ' not bound');
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

    public function hasListeners($eventName): bool { return false; }

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

    public function getListeners($eventName): array { return []; }

    public function push($event, $payload = []): void {}

    public function flush($event): void {}

    public function forget($event): void {}

    public function forgetPushed(): void {}
}

$ingestor = new UpdateIngestor(events: new SmokeDispatcher());
$entities = new EntityAggregator();

$checks = [
    // Ingest
    'Ingest\UpdateStored loads' => static fn (): bool => class_exists(UpdateStored::class),
    'Ingest\UpdateStored is a plain DTO' => static fn (): bool => class_uses(UpdateStored::class) === [],
    'Ingest\RouteIdempotency constructs' => static fn (): bool => new RouteIdempotency() instanceof RouteIdempotency,
    'Ingest\UpdateIngestor constructs (fake dispatcher)' => static fn (): bool => $ingestor instanceof UpdateIngestor,
    'Ingest\EntityAggregator constructs' => static fn (): bool => $entities instanceof EntityAggregator,
    'Ingest\IdentityLock loads' => static fn (): bool => class_exists(IdentityLock::class),
    'Ingest\PayloadWalker loads' => static fn (): bool => class_exists(PayloadWalker::class),

    // Public face
    'Teleclient constructs (two ingestor singletons)' => static fn (): bool =>
        new Teleclient($ingestor, $entities) instanceof Teleclient,

    // Bus
    'Bus\StreamSchema loads' => static fn (): bool => class_exists(StreamSchema::class),
    'Bus\StreamSchema encode/decode round-trip' => static function (): bool {
        $entry = ['account_id' => 42, 'update' => ['_' => 'updateNewMessage', 'seq' => 7], 'ts' => 1720000000];

        return StreamSchema::decode(StreamSchema::encode($entry)) === $entry;
    },

    'Bus\RouteTable constructs (redis double)' => static fn (): bool =>
        new RouteTable(new SmokeRedis()) instanceof RouteTable,
    'Bus\RedisStreamSink constructs (redis double)' => static fn (): bool =>
        new RedisStreamSink(new SmokeRedis(), 42) instanceof RedisStreamSink,
    'Bus\RedisStreamSink writes to the stream' => static function (): bool {
        $received = 0;
        $redis = new SmokeRedis();
        $sink = new RedisStreamSink($redis, 42);

        $sink->handle(['_' => 'updateNewMessage', 'seq' => 9]);

        return $redis->llen(StreamSchema::STREAM) === 1;
    },

    // Backfill
    'Backfill\FetchQueue constructs' => static fn (): bool => new FetchQueue() instanceof FetchQueue,
    'Backfill\BackfillWorker constructs (scripted sleep)' => static fn (): bool =>
        new BackfillWorker(10, 3600, static fn (int $s): bool => true) instanceof BackfillWorker,

    // Backup
    'Backup\VaultCrypto constructs' => static fn (): bool => new VaultCrypto() instanceof VaultCrypto,
    'Backup\Chunker constructs' => static fn (): bool => new Chunker() instanceof Chunker,
    'Backup\InMemoryVault constructs' => static fn (): bool => new InMemoryVault() instanceof InMemoryVault,
    'Backup\BackupRunner loads' => static fn (): bool => class_exists(BackupRunner::class),
    'Backup\Pruner loads' => static fn (): bool => class_exists(Pruner::class),
    'Backup\Restorer loads' => static fn (): bool => class_exists(Restorer::class),
    'Backup\Verifier loads' => static fn (): bool => class_exists(Verifier::class),

    // Schema
    'Schema\TeleframeSchemeLoader::parseString' => static function (): bool {
        $scheme = TeleframeSchemeLoader::parseString('bool#0c734172 = Bool;');

        return $scheme !== null && $scheme->types() !== [];
    },
    'Schema\SchemaRegenerator::loadScheme (committed sources)' => static function (): bool {
        $regenerator = new SchemaRegenerator();
        $scheme = $regenerator->loadScheme(__DIR__ . '/../schema/sources');

        return $scheme->types() !== [];
    },

    // Daemon
    'Daemon\AccountWorker constructs (injected factories, no wire)' => static fn (): bool => new AccountWorker(
        ['account_id' => 1, 'flood_cap_seconds' => 3600],
        static function (): void {
            throw new \RuntimeException('scope factory invoked — smoke never touches the wire');
        },
        null,
        static fn (int $s): bool => true,
    ) instanceof AccountWorker,

    // Handler (Phase 3)
    'Handler substrate constructs (registry/pipeline/update/dispatcher/cache)' => static function (): bool {
        $registry = new HandlerRegistry();
        $dispatcher = new UpdateDispatcher($registry, new Pipeline(), new StackContainer($registry, new InMemoryCache()), new InMemoryCache(), []);

        return Update::fromBus(['_' => 'x'], 1) instanceof Update
            && $dispatcher instanceof UpdateDispatcher;
    },
    'FakeRunningMode drives the REAL pipeline plain-PHP' => static function (): bool {
        $registry = new HandlerRegistry();
        $fired = 0;
        $registry->onMessage(static function (Update $u) use (&$fired): void {
            $fired++;
        });
        $sends = new InMemoryCache();
        $fake = new \MeRezaRezaei\Teleframe\Testing\FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage'], 'account_id' => 7]],
            $registry,
            new StackContainer($registry, $sends),
            $sends,
        );
        $result = $fake->run();

        return $fired === 1 && count($result['dispatched']) === 1;
    },
    'Q2 echo elimination in the REAL pipeline (plain-PHP)' => static function (): bool {
        $registry = new HandlerRegistry();
        $fired = 0;
        $registry->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $sends = new InMemoryCache();
        $eliminator = new \MeRezaRezaei\Teleframe\Handler\Middleware\EchoEliminator(
            $sends,
            new \MeRezaRezaei\Teleframe\Handler\HandlerMatcher($registry),
        );
        $eliminator->remember(7, ['random_id' => 'r-1']);
        $fake = new \MeRezaRezaei\Teleframe\Testing\FakeDispatcher(
            [['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-1'], 'account_id' => 7]],
            $registry,
            new StackContainer($registry, $sends),
            $sends,
        );
        $fake->run();

        return $fired === 0; // echo eliminated before the handler ran
    },
    'Teleframe facade composes + onMessage + run (plain-PHP)' => static function (): bool {
        $registry = new HandlerRegistry();
        $sends = new InMemoryCache();
        $teleframe = new \MeRezaRezaei\Teleframe\Teleframe(new StackContainer($registry, $sends));
        $fired = 0;
        $teleframe->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $teleframe->run([['update' => ['_' => 'updateNewMessage'], 'account_id' => 1]]);

        return $fired === 1;
    },
    // Realtime (Phase 4)
    'Realtime\ChannelNames accountUpdates format' => static fn (): bool =>
        ChannelNames::accountUpdates(42) === 'account:42:updates',
    'Realtime\ChannelNames accountMessages format' => static fn (): bool =>
        ChannelNames::accountMessages(42, 99) === 'account:42:messages:99',
    'Realtime\HttpCentrifugoBridge constructs' => static fn (): bool =>
        new HttpCentrifugoBridge('http://localhost:8000/api', 'key', 'secret') instanceof HttpCentrifugoBridge,
    'Realtime\CentrifugoBridge interface exists' => static fn (): bool =>
        interface_exists(\MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge::class),

    'Facade send writes the elimination registry; echo consumed by dispatcher' => static function (): bool {
        $registry = new HandlerRegistry();
        $sends = new InMemoryCache();
        $teleframe = new \MeRezaRezaei\Teleframe\Teleframe(new StackContainer($registry, $sends));
        $fired = 0;
        $teleframe->onMessage(static function (?Update $u) use (&$fired): void {
            $fired++;
        });
        $teleframe->send(7, ['random_id' => 'r-2']);
        $teleframe->run([['update' => ['_' => 'updateNewMessage', 'random_id' => 'r-2'], 'account_id' => 7]]);

        return $fired === 0; // the recorded send's echo never re-entered the handler
    },
];

$failed = false;
foreach ($checks as $label => $check) {
    try {
        $ok = $check();
    } catch (\Throwable $e) {
        printf("  ✗ %s — %s: %s\n", $label, $e::class, $e->getMessage());
        $failed = true;
        continue;
    }
    printf("%s %s\n", $ok ? '  ✓' : '  ✗', $label);
    $failed = $failed || !$ok;
}

exit($failed ? 1 : 0);