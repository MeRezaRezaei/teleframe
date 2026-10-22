<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Redis\RedisManager;
use MeRezaRezaei\Teleframe\Bus\LaravelRedisAdapter;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * IngestCommand wiring tests (plan Phase 3, Task 3): the command is a thin
 * shell, so these verify the shell — consumer resolution at the
 * CONSUMER_CLASS string key, --once vs daemon loop, pcntl graceful stop —
 * with a fake consumer and the ArrayRedis double. Live-redis adapter
 * coverage lives in tests/Bus/LaravelRedisAdapterTest.php.
 */
final class IngestCommandTest extends TestCase
{
    private ArrayRedis $redis;

    private FakeIngestConsumer $fakeConsumer;

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    public function test_config_bus_section_is_sourced_from_stream_schema(): void
    {
        self::assertSame(StreamSchema::STREAM, config('teleframe.bus.stream'));
        self::assertSame(StreamSchema::GROUP, config('teleframe.bus.group'));
        self::assertSame(StreamSchema::RELOAD_CHANNEL, config('teleframe.bus.reload_channel'));
        self::assertSame('default', config('teleframe.bus.connection'));
        self::assertSame(getenv('TELEFRAME_REDIS') ?: 'predis', config('teleframe.bus.redis_client'));
    }

    public function test_command_arms_the_consumer_group_before_its_first_batch(): void
    {
        // The documented separate-process deployment used to start against a
        // fresh Redis and die immediately:
        //   NOGROUP No such key 'tg:stream:updates' or consumer group
        //   'teleclient' in XREADGROUP
        // because nothing created the group. This is the regression guard.
        self::assertArrayNotHasKey(StreamSchema::GROUP, $this->redis->groups, 'precondition: no group yet');

        $this->artisan('teleframe:ingest', ['--once' => true])->assertExitCode(0);

        self::assertSame(1, $this->fakeConsumer->ensureGroupCalls, 'the command must arm the group itself');
        self::assertArrayHasKey(
            StreamSchema::GROUP,
            $this->redis->groups,
            'the group must exist on the server after the command runs',
        );
    }

    public function test_once_processes_a_single_batch_and_exits_zero(): void
    {
        $this->seedEntries(2);

        $this->artisan('teleframe:ingest', ['--once' => true])
            ->expectsOutput('processed 2 entries')
            ->assertExitCode(0);

        self::assertSame(1, $this->fakeConsumer->calls);
    }

    public function test_daemon_loop_runs_max_iterations_then_exits_zero(): void
    {
        $this->seedEntries(2); // non-empty batches keep the loop out of the idle sleep

        $this->artisan('teleframe:ingest', ['--max' => 3])
            ->expectsOutput('ingest stopped after 3 iterations')
            ->assertExitCode(0);

        self::assertSame(3, $this->fakeConsumer->calls);
    }

    public function test_daemon_loop_stops_gracefully_on_sigterm_mid_run(): void
    {
        if (! function_exists('posix_kill') || ! function_exists('pcntl_signal')) {
            self::markTestSkipped('pcntl/posix unavailable');
        }

        $this->seedEntries(2);
        $this->fakeConsumer->signalAt = 2;

        $this->artisan('teleframe:ingest', ['--max' => 10])
            ->expectsOutput('ingest stopped after 2 iterations')
            ->assertExitCode(0);

        self::assertSame(2, $this->fakeConsumer->calls);
    }

    public function test_provider_fails_loudly_without_the_illuminate_redis_service(): void
    {
        // Testbench binds 'redis' by default, so exercise the guard on a
        // bare container with only our provider registered.
        $app = new Container;
        $app->instance('config', new Repository([
            'teleframe' => require dirname(__DIR__, 2).'/src/Laravel/config/teleframe.php',
        ]));
        (new TeleframeServiceProvider($app))->register();

        try {
            $app->make(RedisConnectionContract::class);
            self::fail('Expected RuntimeException without app("redis")');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('illuminate redis service', $e->getMessage());
        }
    }

    public function test_provider_wraps_a_bound_redis_manager_in_the_adapter(): void
    {
        $this->app->forgetInstance(RedisConnectionContract::class);
        $this->app->instance('redis', new RedisManager(
            $this->app,
            'predis',
            ['default' => ['host' => '127.0.0.1', 'port' => 6399]],
        ));

        self::assertInstanceOf(LaravelRedisAdapter::class, $this->app->make(RedisConnectionContract::class));
    }

    public function test_command_is_registered_and_consumer_key_is_the_task2_fqcn(): void
    {
        $kernel = $this->app->make(Kernel::class);

        self::assertArrayHasKey('teleframe:ingest', $kernel->all());
        self::assertSame('MeRezaRezaei\Teleframe\Bus\IngestConsumer', IngestCommand::CONSUMER_CLASS);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $this->fakeConsumer = new FakeIngestConsumer($this->redis);
        $this->app->instance(RedisConnectionContract::class, $this->redis);
        $this->app->instance(IngestCommand::CONSUMER_CLASS, $this->fakeConsumer);
    }

    /**
     * Seed `count` entries shaped the way a v2 producer publishes them.
     *
     * `StreamSchema::fields()` is the map every writer hands to `xadd()`, so
     * the stream this command reads carries exactly what RedisStreamSink
     * writes. The old fixture called `StreamSchema::encode()` over an inline
     * `update` payload, which the v2 encoder REFUSES by design (D-039: the
     * entry names a committed row, it does not carry it).
     */
    private function seedEntries(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->redis->xadd(StreamSchema::STREAM, '*', StreamSchema::fields([
                'account_id' => 42,
                'seq' => $i,
                'position' => 1,
                'constructor' => 'updateNewMessage',
                'ts' => time(),
            ]));
        }
    }
}

/**
 * Test double bound at IngestCommand::CONSUMER_CLASS: records calls, counts
 * the seeded stream entries as its batch result, and can deliver a real
 * SIGTERM to the test process mid-run to exercise the graceful-stop path.
 */
final class FakeIngestConsumer
{
    public int $calls = 0;

    public int $ensureGroupCalls = 0;

    public ?int $signalAt = null;

    public function __construct(private readonly ArrayRedis $redis) {}

    /**
     * The command now arms the group itself before the first batch: a fresh
     * Redis has no group and XREADGROUP dies with NOGROUP without this. The
     * fake counts the calls so a test can assert the command asked.
     */
    public function ensureGroup(): bool
    {
        $this->ensureGroupCalls++;

        return $this->redis->command('xgroup', [
            'CREATE',
            StreamSchema::STREAM,
            StreamSchema::GROUP,
            '0',
            'MKSTREAM',
        ]) === 'OK';
    }

    /** @return array{processed: int} */
    public function consumeOnce(): array
    {
        $this->calls++;

        if ($this->signalAt !== null && $this->signalAt <= $this->calls && function_exists('posix_kill')) {
            posix_kill(getmypid(), SIGTERM);
        }

        return ['processed' => count($this->redis->streamEntries(StreamSchema::STREAM))];
    }
}
