<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Control;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountAdded;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountLifecycleObserver;
use MeRezaRezaei\Teleframe\AccountLifecycle\AccountRemoved;
use MeRezaRezaei\Teleframe\AccountLifecycle\BotAdded;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use RuntimeException;

/**
 * AccountLifecycleObserver — the Cycle-4 lifecycle hot-event loop on the
 * DB-backed tf_accounts registry: an account/bot add, change or removal
 * publishes a best-effort {kind:'accounts', account_id, change} reload on
 * tg:bus:reload AND dispatches AccountAdded / AccountRemoved (BotAdded for
 * kind='bot'). The bus redis stays nullable — a host without one still gets
 * every event; the publish is skipped, never fatal.
 */
final class AccountLifecycleObserverTest extends TestbenchTestCase
{
    private ArrayRedis $redis;

    /** @var list<object> */
    private array $dispatched = [];

    /** @var list<array{channel: string, payload: array<string, mixed>}> */
    private array $reloads = [];

    private AccountLifecycleObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $this->redis->subscribe(StreamSchema::RELOAD_CHANNEL, function (string $channel, string $payload): void {
            $this->reloads[] = [
                'channel' => $channel,
                'payload' => json_decode($payload, true, 512, JSON_THROW_ON_ERROR),
            ];
        });

        $events = new class($this->dispatched) implements Dispatcher
        {
            public function __construct(private array &$sink) {}

            public function listen($events, $listener = null): void {}

            public function hasListeners($eventName): bool
            {
                return true;
            }

            public function subscribe($subscriber): void {}

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->sink[] = $event;

                return [];
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}

            public function getRawListeners($eventName): array
            {
                return [];
            }
        };

        $this->observer = new AccountLifecycleObserver($this->redis, $events);
    }

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

    private function migrateRegistry(): void
    {
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_22_000100_create_tf_accounts_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
    }

    private function persist(array $attributes): TfAccount
    {
        $account = new TfAccount($attributes);
        $account->save();

        return $account;
    }

    private function lastReload(): array
    {
        return $this->reloads[count($this->reloads) - 1]['payload'];
    }

    public function test_created_publishes_reload_and_emits_account_added(): void
    {
        $this->migrateRegistry();
        $account = $this->persist([
            'id' => 42,
            'label' => 'main',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:main',
            'enabled' => true,
        ]);

        $this->observer->created($account);

        self::assertSame(StreamSchema::RELOAD_CHANNEL, $this->reloads[0]['channel']);
        self::assertSame('accounts', $this->lastReload()['kind']);
        self::assertSame(42, $this->lastReload()['account_id']);
        self::assertSame('added', $this->lastReload()['change']);
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(AccountAdded::class, $this->dispatched[0]);
        self::assertSame(42, $this->dispatched[0]->accountId());
        self::assertSame('account', $this->dispatched[0]->kind());
        self::assertSame('added', $this->dispatched[0]->change);
        self::assertFalse($this->dispatched[0]->isBot());
    }

    public function test_created_bot_dispatches_bot_added_alias(): void
    {
        $this->migrateRegistry();
        $bot = $this->persist([
            'id' => 900,
            'label' => 'support-bot',
            'dc_id' => 2,
            'kind' => 'bot',
            'session_ref' => 'vault:bot',
            'enabled' => true,
        ]);

        $this->observer->created($bot);

        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(BotAdded::class, $this->dispatched[0]);
        self::assertInstanceOf(AccountAdded::class, $this->dispatched[0], 'the alias IS an AccountAdded');
        self::assertSame('bot', $this->dispatched[0]->kind());
        self::assertTrue($this->dispatched[0]->isBot());
        self::assertSame(TfAccount::KIND_BOT, $bot->kind);
        self::assertTrue($bot->isBot());
    }

    public function test_updated_publishes_changed_reload(): void
    {
        $this->migrateRegistry();
        $account = $this->persist([
            'id' => 43,
            'label' => 'account-two',
            'dc_id' => 4,
            'kind' => 'account',
            'session_ref' => 'vault:two',
            'enabled' => true,
        ]);

        $this->observer->updated($account);

        self::assertSame('accounts', $this->lastReload()['kind']);
        self::assertSame(43, $this->lastReload()['account_id']);
        self::assertSame('changed', $this->lastReload()['change']);
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(AccountAdded::class, $this->dispatched[0], 'pressable re-announce, change-kind on the payload');
        self::assertSame('changed', $this->dispatched[0]->change);
    }

    public function test_deleted_publishes_removed_and_emits_account_removed(): void
    {
        $this->migrateRegistry();
        $account = $this->persist([
            'id' => 44,
            'label' => 'departing',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:bye',
            'enabled' => false,
        ]);

        $this->observer->deleted($account);

        self::assertSame('accounts', $this->lastReload()['kind']);
        self::assertSame(44, $this->lastReload()['account_id']);
        self::assertSame('removed', $this->lastReload()['change']);
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(AccountRemoved::class, $this->dispatched[0]);
        self::assertSame(44, $this->dispatched[0]->accountId());
        self::assertSame('removed', $this->dispatched[0]->change);
    }

    public function test_publish_is_best_effort_when_redis_unavailable(): void
    {
        $this->migrateRegistry();
        $account = $this->persist([
            'id' => 45,
            'label' => 'silent-host',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:silent',
            'enabled' => true,
        ]);

        // No redis injected and nothing bound in the container: the observer
        // must still emit the event — the publish is skipped, never thrown.
        $noBus = new AccountLifecycleObserver(redis: null, events: app()->make(Dispatcher::class));

        $captured = [];
        Event::listen(AccountAdded::class, function (AccountAdded $event) use (&$captured): void {
            $captured[] = $event;
        });

        $noBus->created($account);

        self::assertCount(1, $captured, 'events fire even without a bus redis');
        self::assertSame([], $this->reloads, 'no publish without a bus');
    }

    public function test_publish_skips_when_redis_publish_throws_runtime_exception(): void
    {
        $this->migrateRegistry();
        $account = $this->persist([
            'id' => 46,
            'label' => 'failing-bus',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:fail',
            'enabled' => true,
        ]);

        $broken = new class implements RedisConnectionContract
        {
            public function xadd(string $stream, string $id, array $fields, array $options = []): string
            {
                return $id;
            }

            public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
            {
                return [];
            }

            public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
            {
                return [];
            }

            public function xrange(string $stream, string $start, string $end, int $count = 100): array
            {
                return [];
            }

            public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
            {
                return [];
            }

            public function xdel(string $stream, array $ids): int
            {
                return 0;
            }

            public function hdel(string $key, string ...$fields): int
            {
                return 0;
            }

            public function command(string $command, array $arguments = []): mixed
            {
                return null;
            }

            public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
            {
                return [];
            }

            public function xack(string $stream, string $group, array $ids): int
            {
                return 0;
            }

            public function hgetall(string $key): array
            {
                return [];
            }

            public function hset(string $key, string $field, string $value): int
            {
                return 0;
            }

            public function publish(string $channel, string $payload): int
            {
                throw new RuntimeException('bus redis is down');
            }

            public function subscribe(string $channel, callable $handler): void {}

            public function del(string ...$keys): int
            {
                return 0;
            }

            public function llen(string $key): int
            {
                return 0;
            }

            public function hget(string $key, string $field): ?string
            {
                return null;
            }

            public function expire(string $key, int $seconds): bool
            {
                return false;
            }
        };

        $events = new class($this->dispatched) implements Dispatcher
        {
            public function __construct(private array &$sink) {}

            public function listen($events, $listener = null): void {}

            public function hasListeners($eventName): bool
            {
                return true;
            }

            public function subscribe($subscriber): void {}

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->sink[] = $event;

                return [];
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}

            public function getRawListeners($eventName): array
            {
                return [];
            }
        };

        $observer = new AccountLifecycleObserver($broken, $events);

        $observer->created($account); // must NOT throw

        self::assertCount(1, $this->dispatched, 'the event still fires when the publish throws');
        self::assertInstanceOf(AccountAdded::class, $this->dispatched[0]);
    }

    /**
     * The model wires the observer through its own booted() hook — a real
     * TfAccount save() must publish the reload and dispatch AccountAdded
     * with NO manual observer call. The observer class-string registration
     * is re-resolved from the current container at fire time.
     */
    public function test_booted_registration_auto_fires_observer_on_eloquent_save(): void
    {
        $this->migrateRegistry();

        // Point the bus redis contract at the in-memory double BEFORE the
        // model first boots, so the auto-registered observer publishes to it.
        $this->app->instance(RedisConnectionContract::class, $this->redis);

        // Re-arm the static boot so this test's app owns the registration
        // (Eloquent boots each model class once per process).
        $this->replayModelBoot();

        $captured = [];
        Event::listen(AccountAdded::class, function (AccountAdded $event) use (&$captured): void {
            $captured[] = $event;
        });

        $this->persist([
            'id' => 7,
            'label' => 'registered',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:reg',
            'enabled' => true,
        ]);

        self::assertSame('accounts', $this->lastReload()['kind']);
        self::assertSame(7, $this->lastReload()['account_id']);
        self::assertSame('added', $this->lastReload()['change']);
        self::assertCount(1, $captured, 'AccountAdded fired from the booted() lifecycle hook');
        self::assertSame(7, $captured[0]->accountId());
    }

    private function replayModelBoot(): void
    {
        // Eloquent boots each model class once per process; re-arm ONLY
        // TfAccount's boot slot so THIS app owns the observer listener
        // registration. Never wipe the process-wide boot map — a global reset
        // makes every other model re-boot later against whichever app is
        // current, double-registering or orphaning listeners (W-14).
        $booted = new \ReflectionProperty(Model::class, 'booted');
        $current = $booted->getValue();
        unset($current[TfAccount::class]);
        $booted->setValue(null, $current);

        TfAccount::setEventDispatcher($this->app->make(Dispatcher::class));
    }
}
