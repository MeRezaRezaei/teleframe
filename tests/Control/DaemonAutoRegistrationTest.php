<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Control;

use Illuminate\Console\View\Components\Factory as ConsoleComponentsFactory;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountAdded;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountRemoved;
use MeRezaRezaei\Teleframe\AccountLifecycle\BotAdded;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Cycle-5 item 2 — daemon AUTO-REGISTRATION on account/bot discovery:
 * a running daemon that spots a NEW tf_accounts row (with no config-seed
 * entry) seats it through the session_ref → SessionData resolver seam, fires
 * the AccountAdded/BotAdded lifecycle event on the DB-discovery path, and
 * pre-seeds the account's default route (tg_update_routing peer 0/0
 * store_only + Redis-2 hot cache refresh) so the router answers immediately;
 * removing the row unseats it, fires AccountRemoved, and forgets the account's
 * routing cache. Mirrors DaemonLiveReseatTest's loop harness — the seeded
 * rows are raw-inserted so the Eloquent AccountLifecycleObserver never fires;
 * every captured event proves the DAEMON discovery path, not the model path.
 */
final class DaemonAutoRegistrationTest extends TestbenchTestCase
{
    private const STABLE_ID = 1;

    private const ARRIVING_ID = 2;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_22_000100_create_tf_accounts_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);

        // The routing table is the SHIPPED migration, executed — not
        // transcribed. A hand-typed copy had already drifted from it (a
        // single-column account_id index instead of (account_id, mode), and
        // an unsigned peer_id where the shipped DDL is deliberately signed
        // so negative Telegram channel ids are not rejected on MySQL).
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);

        // Stable account 1 lives in the registry at boot; raw insert so the
        // lifecycle observer is never involved in this test's event stream.
        DB::table('tf_accounts')->insert([
            'id' => self::STABLE_ID,
            'label' => 'stable',
            'dc_id' => 2,
            'kind' => TfAccount::KIND_ACCOUNT,
            'session_ref' => 'vault:stable',
            'enabled' => true,
        ]);
    }

    /**
     * A container-resolved DaemonCommand with a real components factory on a
     * buffer, so the reseat logging paths behave like an artisan run.
     */
    private function command(BufferedOutput $output): DaemonCommand
    {
        $command = $this->app->make(DaemonCommand::class);
        $command->setLaravel($this->app);

        $property = new ReflectionProperty(DaemonCommand::class, 'components');
        $property->setAccessible(true);
        $property->setValue($command, new ConsoleComponentsFactory($output));

        return $command;
    }

    private function invoke(object $target, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($target, $method);
        $ref->setAccessible(true);

        return $ref->invoke($target, ...$args);
    }

    private function reloadRequested(DaemonCommand $command): bool
    {
        $property = new ReflectionProperty(DaemonCommand::class, 'reloadRequested');
        $property->setAccessible(true);

        return (bool) $property->getValue($command);
    }

    /**
     * Listeners that record the lifecycle events the daemon dispatches, keyed
     * away from the model-observer path. `addedBots` and `added` are separate
     * so the test proves a BotAdded dispatch does not double-fire the base
     * listener (Illuminate routes events by exact class + interfaces only).
     *
     * @return \stdClass{added: list<array{int, string, ?string}>, addedBots: list<array{int, string, ?string}>, removed: list<array{int, string, ?string}>}
     */
    private function installEventCapture(): \stdClass
    {
        $capture = new \stdClass;
        $capture->added = [];
        $capture->addedBots = [];
        $capture->removed = [];

        $events = $this->app->make('events');

        $events->listen(AccountAdded::class, static function (AccountAdded $e) use ($capture): void {
            $capture->added[] = [$e->accountId, $e->kind, $e->label];
        });
        $events->listen(BotAdded::class, static function (BotAdded $e) use ($capture): void {
            $capture->addedBots[] = [$e->accountId, $e->kind, $e->label];
        });
        $events->listen(AccountRemoved::class, static function (AccountRemoved $e) use ($capture): void {
            $capture->removed[] = [$e->accountId, $e->kind, $e->label];
        });

        return $capture;
    }

    /**
     * Raw-insert a NEW registry row (bypasses the Eloquent observer): the row
     * is what the daemon's per-rotation re-read discovers.
     */
    private function discoverAccount(int $id, string $label, string $kind): void
    {
        DB::table('tf_accounts')->insert([
            'id' => $id,
            'label' => $label,
            'dc_id' => 2,
            'kind' => $kind,
            'session_ref' => 'vault:arriving',
            'enabled' => true,
        ]);
    }

    /**
     * Bind the host's session_ref → SessionData resolver seam and record the
     * opaque refs it was asked to resolve.
     */
    private function registerSessionResolver(\stdClass $state): void
    {
        $this->app->instance(DaemonCommand::SESSION_RESOLVER_KEY, static function (string $sessionRef) use ($state): ?SessionData {
            $state->sessionRefs[] = $sessionRef;

            return new SessionData(dcId: 2, authKey: 'auth-key-arriving');
        });
    }

    /**
     * The Cycle-5 item-2 flagship: a registry-only account (no config-seed
     * entry) is discovered while the daemon runs, seated via the session_ref →
     * SessionData resolver seam, auto-registered a default route (DB row + hot
     * cache) with the router answering for it, then unseated on removal with
     * AccountRemoved fired and the hot cache forgotten.
     */
    public function test_discovered_account_is_seated_seeded_and_evicted_via_daemon_discovery(): void
    {
        // The seed supplies dialable credentials for STABLE only; ARRIVING has
        // none — that is the point of the discovery resolver seam.
        config(['teleframe.daemon.accounts' => [
            ['account_id' => self::STABLE_ID],
        ]]);

        $redis = new ArrayRedis;
        $this->app->instance(RedisConnectionContract::class, $redis);

        $reloads = [];
        $redis->subscribe(StreamSchema::RELOAD_CHANNEL, static function (string $channel, string $payload) use (&$reloads): void {
            $reloads[] = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        });

        $capture = $this->installEventCapture();

        $state = new \stdClass;
        $state->runs = [];
        $state->builds = [];
        $state->sessionRefs = [];
        $state->diaConfigs = [];
        $this->registerSessionResolver($state);

        $hold = new \stdClass;
        $hold->daemon = null;

        $factory = function (array $config) use ($state, $hold, $redis): array {
            $accountId = (int) $config['account_id'];
            $state->builds[$accountId] = ($state->builds[$accountId] ?? 0) + 1;
            $state->diaConfigs[$accountId] = $config;

            $worker = new TickingWorker(function (?callable $shouldStop = null) use ($accountId, $state, $hold, $redis): void {
                $state->runs[$accountId] = ($state->runs[$accountId] ?? 0) + 1;

                if ($accountId !== self::STABLE_ID) {
                    return; // the discovered account ticks once between seat and unseat
                }

                $n = $state->runs[$accountId];

                if ($n === 1) {
                    $this->discoverAccount(self::ARRIVING_ID, 'droid', TfAccount::KIND_ACCOUNT);

                    $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                        'kind' => 'accounts',
                        'account_id' => self::ARRIVING_ID,
                        'change' => 'added',
                    ], JSON_THROW_ON_ERROR));
                } elseif ($n === 2) {
                    DB::table('tf_accounts')->where('id', self::ARRIVING_ID)->delete();

                    $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                        'kind' => 'accounts',
                        'account_id' => self::ARRIVING_ID,
                        'change' => 'removed',
                    ], JSON_THROW_ON_ERROR));
                } elseif ($n === 3) {
                    $hold->daemon?->stop();
                }
            });

            return ['config' => $config, 'worker' => $worker, 'wrapper' => $worker];
        };
        $this->app->instance(DaemonCommand::WORKER_FACTORY_KEY, $factory);

        $command = $this->command(new BufferedOutput);
        $this->invoke($command, 'armReloadWatch');

        $hold->daemon = $this->invoke($command, 'buildDaemon', $this->invoke($command, 'resolveAccounts'));

        $exit = $hold->daemon->run();

        self::assertSame(0, $exit);
        self::assertSame([1 => 1, 2 => 1], $state->builds, 'stable account never rebuilt; the discovered account built exactly once');
        self::assertSame([1 => 3, 2 => 1], $state->runs, 'worker 2 ticked once between seat and unseat');
        self::assertSame([self::STABLE_ID], $hold->daemon->seatedIds(), 'account 2 was unseated when its registry row disappeared');
        self::assertFalse($this->reloadRequested($command), 'the reload nudge was consumed');

        // (a) discovery: the resolver turned the opaque session_ref into
        // SessionData — never a session string on the worker config.
        self::assertSame(['vault:arriving'], $state->sessionRefs, 'the host resolver answered once for the registry-only row');
        self::assertInstanceOf(SessionData::class, $state->diaConfigs[self::ARRIVING_ID]['session_data'] ?? null);
        self::assertArrayNotHasKey('session_string', $state->diaConfigs[self::ARRIVING_ID], 'never a session string on the wire');

        // (b) auto-registration: the account-level default route row + hot
        // cache exist, and the router answers for the account's peers.
        $rule = DB::table('tg_update_routing')
            ->where('account_id', self::ARRIVING_ID)
            ->where('peer_type', UpdateRoutingRule::DEFAULT_PEER_TYPE)
            ->where('peer_id', UpdateRoutingRule::DEFAULT_PEER_ID)
            ->first();
        self::assertNotNull($rule, 'the daemon pre-seeded the account-level default route');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $rule->mode);

        self::assertNotEmpty(
            array_filter(
                $reloads,
                static fn (array $p): bool => ($p['kind'] ?? null) === 'routing' && (int) ($p['account_id'] ?? 0) === self::ARRIVING_ID,
            ),
            'seeding refreshed the Redis-2 hot cache (publishes a routing reload)',
        );

        $router = new UpdateRouter(DB::connection(), new UpdateRoutingCache($redis, DB::connection()));
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->mode(self::ARRIVING_ID, 7, 1234567),
            'the router answers for the discovered account via the account-level default',
        );

        self::assertSame(
            0,
            DB::table('tg_update_routing')->where('account_id', self::STABLE_ID)->count(),
            'boot-seated accounts are NOT re-registered by the discovery seeding',
        );

        // (c) lifecycle events fire on the daemon-discovery path...
        self::assertSame(
            [[self::ARRIVING_ID, TfAccount::KIND_ACCOUNT, 'droid']],
            $capture->added,
            'AccountAdded fired when the reseat seated the discovered account',
        );
        self::assertSame(
            [[self::ARRIVING_ID, TfAccount::KIND_ACCOUNT, 'droid']],
            $capture->removed,
            'AccountRemoved fired when the reseat unseated the removed account',
        );

        // ...and unseating forgets the departed account's hot routing cache.
        self::assertNull(
            $redis->hget(UpdateRoutingCache::KEY.':'.self::ARRIVING_ID, '0:0'),
            'the departed account leaves no hot routing cache behind',
        );
    }

    /**
     * A discovered BOT fires BotAdded (kind 'bot' on the payload) and gets the
     * same default-route auto-registration as a user account.
     */
    public function test_discovered_bot_fires_bot_added_and_auto_registers_route(): void
    {
        config(['teleframe.daemon.accounts' => [
            ['account_id' => self::STABLE_ID],
        ]]);

        $redis = new ArrayRedis;
        $this->app->instance(RedisConnectionContract::class, $redis);

        $capture = $this->installEventCapture();

        $state = new \stdClass;
        $state->runs = [];
        $state->builds = [];
        $state->sessionRefs = [];
        $this->registerSessionResolver($state);

        $hold = new \stdClass;
        $hold->daemon = null;

        // No removal in this scenario: the bot stays seated until the daemon
        // stops on account 1's second tick.
        $factory = function (array $config) use ($state, $hold, $redis): array {
            $accountId = (int) $config['account_id'];
            $state->builds[$accountId] = ($state->builds[$accountId] ?? 0) + 1;

            $worker = new TickingWorker(function (?callable $shouldStop = null) use ($accountId, $state, $hold, $redis): void {
                $state->runs[$accountId] = ($state->runs[$accountId] ?? 0) + 1;

                if ($accountId !== self::STABLE_ID) {
                    return; // the bot ticks once after its seat
                }

                $n = $state->runs[$accountId];

                if ($n === 1) {
                    $this->discoverAccount(self::ARRIVING_ID, 'droid', TfAccount::KIND_BOT);

                    $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                        'kind' => 'accounts',
                        'account_id' => self::ARRIVING_ID,
                        'change' => 'added',
                    ], JSON_THROW_ON_ERROR));
                } elseif ($n === 3) {
                    $hold->daemon?->stop();
                }
            });

            return ['config' => $config, 'worker' => $worker, 'wrapper' => $worker];
        };
        $this->app->instance(DaemonCommand::WORKER_FACTORY_KEY, $factory);

        $command = $this->command(new BufferedOutput);
        $this->invoke($command, 'armReloadWatch');

        $hold->daemon = $this->invoke($command, 'buildDaemon', $this->invoke($command, 'resolveAccounts'));

        $exit = $hold->daemon->run();

        self::assertSame(0, $exit);
        self::assertSame([1 => 1, 2 => 1], $state->builds, 'the discovered bot built exactly once');
        self::assertSame([1 => 3, 2 => 1], $state->runs, 'the bot got a full rotation after its seat, then the daemon stopped');

        self::assertSame(
            [[self::ARRIVING_ID, TfAccount::KIND_BOT, 'droid']],
            $capture->addedBots,
            'a discovered bot fires BotAdded with kind "bot" on the payload',
        );
        self::assertSame([], $capture->added, 'a BotAdded dispatch does not double-fire the base AccountAdded listener');
        self::assertSame([], $capture->removed, 'nothing was removed in the bot scenario');

        self::assertSame(['vault:arriving'], $state->sessionRefs, 'the resolver answered once for the registry-only bot row');

        // Same auto-registration: default route row + hot cache populated.
        $rule = DB::table('tg_update_routing')
            ->where('account_id', self::ARRIVING_ID)
            ->where('peer_type', UpdateRoutingRule::DEFAULT_PEER_TYPE)
            ->where('peer_id', UpdateRoutingRule::DEFAULT_PEER_ID)
            ->first();
        self::assertNotNull($rule, 'a discovered bot gets its default route pre-seeded too');
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $rule->mode);
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $redis->hget(UpdateRoutingCache::KEY.':'.self::ARRIVING_ID, '0:0'),
            'the bot hot routing cache is populated',
        );
    }
}

/**
 * Small in-test worker double: runs the injected body until the daemon's
 * shouldStop hook fires — mirrors CallbackWorker from DaemonLiveReseatTest
 * (declared in its own file in this namespace, so a distinct name is needed).
 */
final class TickingWorker implements WorkerInterface
{
    private readonly \Closure $body;

    public function __construct(callable $body)
    {
        $this->body = $body(...);
    }

    public function run(?callable $shouldStop = null): void
    {
        ($this->body)($shouldStop);
    }
}
