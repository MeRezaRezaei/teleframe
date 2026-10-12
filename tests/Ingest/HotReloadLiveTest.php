<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Predis\Response\Status;

/**
 * Cycle-5 item 3 LIVE gate — the "Redis two" hot-reload proof against a
 * REAL Redis, not the in-memory double.
 *
 * The unit proof (MirrorIngesterFactoryRoutingCacheTest) pins the wiring
 * with ArrayRedis; this test proves the operator's live gesture: an
 * operator toggles one peer store_only↔act_on in the DB, the daemon's
 * same built-ingester seam re-reads via the tg:routing:rules:{account}
 * hash refreshed by RoutingSettingsObserver, and the event stream (the
 * UpdateStored dispatches) switches with ZERO restart.
 *
 * Skipped unless TF_REDIS_TWO_LIVE=1 AND a real Redis answers on
 * TF_REDIS_TWO_DSN (default tcp://127.0.0.1:6379): the same opt-in
 * convention as tests/Laravel/Console/VaultTerminalLiveTest.
 *
 *     TF_REDIS_TWO_LIVE=1 vendor/bin/phpunit --filter HotReloadLive
 *
 * The DB source of truth stays the isolated in-memory sqlite from the
 * testbench; the ONLY production side effects are writes under the
 * dedicated account namespace tg:routing:rules:999999001 (deleted again
 * in tearDown) and one pub/sub tick on tg:bus:reload — never a real
 * account's hash. Requires no real credentials (a session-string-free
 * board: routing rules are data-plane, not auth-plane).
 */
final class HotReloadLiveTest extends TestbenchTestCase
{
    private const GATE_ENV = 'TF_REDIS_TWO_LIVE';

    private const DSN_ENV = 'TF_REDIS_TWO_DSN';

    private const DEFAULT_DSN = 'tcp://127.0.0.1:6379';

    /** Dedicated live-proof account namespace — cannot collide with a real account. */
    private const ACCOUNT_ID = 999999001;

    /** peerType 3 = channel (PeerShapeTool enum: 1 user, 2 chat, 3 channel). */
    private const PEER_TYPE = 3;

    private const PEER_ID = 42424242;

    private const HASH_KEY = UpdateRoutingCache::KEY.':999999001';

    private const FIELD = '3:42424242';

    private ?LaravelRedisAdapter $adapter = null;

    private ?UpdateRoutingCache $cache = null;

    private ?UpdateRouter $router = null;

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    public function test_redis_two_hot_reload_switches_the_same_seam_without_restart(): void
    {
        if (getenv(self::GATE_ENV) !== '1') {
            $this->markTestSkipped(self::GATE_ENV.'=1 to run the Redis-two live proof');
        }

        try {
            $adapter = $this->liveRedis();
        } catch (\Throwable $e) {
            $this->markTestSkipped(
                'live Redis on '.self::dsn().' unreachable: '.$e->getMessage(),
            );
        }

        // Point the provider-wired RoutingSettingsObserver at the LIVE bus
        // BEFORE its singleton is first resolved (RoutingSettingsObserverTest
        // proves this ordering), so a plain Eloquent save refreshes the real
        // hash and publishes the reload channel.
        $this->app->instance(RedisConnectionContract::class, $adapter);

        // Idempotent re-runs: never start from a stale hash.
        $adapter->del(self::HASH_KEY);

        $this->migrateIngest();

        // The seam router exactly as MirrorIngesterFactory::build wires it:
        // UpdateRouter(DB::connection(), $routingCache) on line 54.
        $this->cache = new UpdateRoutingCache($adapter, DB::connection());
        $this->router = new UpdateRouter(DB::connection(), $this->cache);
        $this->adapter = $adapter;

        /** @var list<Model> $seen */
        $seen = [];
        Event::listen(UpdateStored::class, static function (UpdateStored $event) use (&$seen): void {
            $seen[] = $event->model;
        });

        // The full daemon seam, built ONCE with the live routing cache — the
        // same instance the requirement must switch without a restart.
        $seam = MirrorIngesterFactory::build(
            $this->app->make(Dispatcher::class),
            null,
            $this->cache,
        );

        // ── gesture 1: seed act_on for the test peer via the model. The
        //    provider-registered observer refreshes the LIVE hash.
        UpdateRoutingRule::create([
            'account_id' => self::ACCOUNT_ID,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
            'priority' => 0,
        ]);

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $adapter->hget(self::HASH_KEY, self::FIELD),
            'seeding act_on refreshed the live tg:routing:rules hash via the observer',
        );
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $this->router->mode(self::ACCOUNT_ID, self::PEER_TYPE, self::PEER_ID));
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $this->router->explicitMode(self::ACCOUNT_ID, self::PEER_TYPE, self::PEER_ID));

        // act_on ⇒ the event stream is LIVE for this peer.
        $seam($this->message(1, 'hot-reload-live-acton'), self::ACCOUNT_ID);
        self::assertCount(1, $seen, 'act_on peer dispatches UpdateStored (event stream ON)');
        $this->assertStored(1);

        // ── gesture 2: the OPERATOR flips the DB row to store_only via the
        //    model; the observer refreshes the live hash and re-publishes.
        $rule = UpdateRoutingRule::query()
            ->where('account_id', self::ACCOUNT_ID)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->first();
        self::assertNotNull($rule);
        $rule->update(['mode' => UpdateRoutingRule::MODE_STORE_ONLY]);

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $adapter->hget(self::HASH_KEY, self::FIELD),
            'the DB flip refreshed the live hash (hot-read path, no restart)',
        );
        $this->assertNotNull($this->router, 'router is the same built instance');
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->router->mode(self::ACCOUNT_ID, self::PEER_TYPE, self::PEER_ID),
            'the SAME router instance now answers store_only',
        );

        // store_only ⇒ the SAME seam silently persists: no event, fact stored.
        $seam($this->message(2, 'hot-reload-live-storeonly'), self::ACCOUNT_ID);
        self::assertCount(1, $seen, 'store_only peer no longer dispatches UpdateStored (event stream OFF)');
        $this->assertStored(2);

        // ── gesture 3: prove the answer comes from the hash, not a fresh DB
        //    read. Delete the DB row (query builder — deliberately bypassing
        //    the model observer so the hash is NOT refreshed) and ask the
        //    router again: a DB fallback would return null (explicitMode) /
        //    the store_only default (mode); only the still-warm hash answers
        //    store_only.
        DB::table('tg_update_routing')
            ->where('account_id', self::ACCOUNT_ID)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->delete();

        self::assertSame(0, DB::table('tg_update_routing')->where('account_id', self::ACCOUNT_ID)->count(), 'DB row gone');
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->router->explicitMode(self::ACCOUNT_ID, self::PEER_TYPE, self::PEER_ID),
            'router still answers store_only from the live hash — a DB read would be null',
        );
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $adapter->hget(self::HASH_KEY, self::FIELD),
            'the hash field outlives the deleted DB row while the daemon keeps running',
        );
    }

    protected function tearDown(): void
    {
        // Never pollute a real hash: the whole account namespace is ours.
        if ($this->adapter !== null) {
            try {
                $this->adapter->del(self::HASH_KEY);
            } catch (\Throwable) {
                // Redis already gone — nothing to clean.
            }
        }
        try {
            DB::table('tg_update_routing')->where('account_id', self::ACCOUNT_ID)->delete();
        } catch (\Throwable) {
            // In-memory sqlite is discarded with the application anyway.
        }

        parent::tearDown();
    }

    /**
     * One message fact for the seam — the same wire shape
     * MirrorIngesterFactoryRoutingCacheTest drives.
     *
     * @return array<string, mixed>
     */
    private function message(int $id, string $text): array
    {
        return [
            '_' => 'message',
            'id' => $id,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => self::PEER_ID],
            'date' => 1726000000,
            'message' => $text,
            'out' => false,
        ];
    }

    private function assertStored(int $messageId): void
    {
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT_ID)->where('id', $messageId)->count(),
            'the fact is mirrored regardless of the routing mode',
        );
    }

    private function migrateIngest(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
    }

    /**
     * A live predis connection wired through the production LaravelRedisAdapter,
     * exactly as the daemon builds it (DaemonCommand::buildWorker).
     *
     * @throws \Throwable when the Redis address is unreachable / auth-gated
     */
    private function liveRedis(): LaravelRedisAdapter
    {
        $manager = new RedisManager($this->app, 'predis', [
            'default' => self::redisConfig(self::dsn()),
        ]);

        $connection = $manager->connection();
        $pong = $connection->command('ping', []);
        if ($pong instanceof Status) {
            $pong = $pong->getPayload();
        }
        if ((string) $pong !== 'PONG') {
            throw new \RuntimeException('ping did not PONG: '.(string) $pong);
        }

        return new LaravelRedisAdapter($connection);
    }

    private static function dsn(): string
    {
        return (string) (getenv(self::DSN_ENV) ?: self::DEFAULT_DSN);
    }

    /**
     * Parse a Redis DSN into a predis/illuminate connection config.
     * Accepts tcp://host:port, redis://[:pass@]host:port, or plain host[:port].
     * The DSN is parsed here (not handed to illuminate's URL parser) so the
     * live gate behaves the same with or without a password.
     *
     * @return array<string, string|int|float>
     */
    private static function redisConfig(string $dsn): array
    {
        $config = ['scheme' => 'tcp', 'host' => '127.0.0.1', 'port' => 6379, 'timeout' => 5.0];

        if (str_contains($dsn, '://')) {
            $u = parse_url($dsn);
            if (! is_array($u) || ($u['host'] ?? '') === '') {
                throw new \RuntimeException("unparsable Redis DSN: {$dsn}");
            }
            $config['scheme'] = strtolower((string) ($u['scheme'] ?? 'tcp'));
            $config['host'] = (string) $u['host'];
            if (isset($u['port'])) {
                $config['port'] = (int) $u['port'];
            }
            if (isset($u['pass'])) {
                $config['password'] = (string) $u['pass'];
            } elseif (($u['user'] ?? '') !== '') {
                $config['password'] = (string) $u['user'];
            }
        } else {
            $bits = explode(':', $dsn, 2);
            $config['host'] = $bits[0] === '' ? '127.0.0.1' : $bits[0];
            if (isset($bits[1])) {
                $config['port'] = (int) $bits[1];
            }
        }

        return $config;
    }
}
