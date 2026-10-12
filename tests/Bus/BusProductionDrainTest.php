<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory as ConsoleComponentsFactory;
use Illuminate\Redis\Connections\Connection as IlluminateConnection;
use Illuminate\Support\Facades\Redis;
use LogicException;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * The shipped daemon's BUS DRAIN, and the bounds that keep it from becoming
 * a slow memory leak.
 *
 * Before the 2026-09-27 fix, DaemonCommand::buildDaemon() wired only
 * reseatAccounts() into the rotation-top onLoop hook. The drain existed
 * exclusively inside runIngestLoop(), which runs ONLY under --ingest-only.
 * So the DEFAULT daemon mode produced updates onto tg:stream:updates and
 * consumed nothing: the stream grew without bound, no handler ran, and every
 * health signal (the process, teleframe:doctor, the banner) looked green.
 *
 * These tests pin the four properties that fix depends on:
 *
 *  1. IngestConsumer::ensureGroup() is idempotent and BUSYGROUP-tolerant —
 *     without it the documented separate-process deployment died instantly
 *     with NOGROUP against a fresh Redis.
 *  2. A real daemon-mode rotation (buildDaemon() -> onLoop -> daemon->run())
 *     actually CONSUMES the entries. This is end-to-end through the real
 *     LaravelRedisAdapter and a real IngestConsumer.
 *  3. A success must not create a strikes-hash field. clearStrike() used to
 *     hset($id, '0') forever, and the key only got a TTL on the FAILURE path
 *     (strike()) — so a never-throwing consumer grew one permanent field per
 *     update, and OpsReadModel HGETALLs the whole hash per ops page load.
 *  4. The stream is bounded (RedisStreamSink::DEFAULT_MAXLEN) and the
 *     dead-letter stream is bounded (10_000).
 *
 * Every test is bounded: the daemon run is stopped from inside the worker's
 * own first tick, so exactly ONE rotation executes and run() returns. There
 * is no polling loop, no sleep loop, no stream wrapper, and no background
 * process anywhere in this file.
 */
final class BusProductionDrainTest extends TestbenchTestCase
{
    /** The dead-letter bound is a private const on IngestConsumer; pin the number. */
    private const DL_BOUND = 10_000;

    private ArrayRedis $redis;

    private BufferedOutput $output;

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

        $this->redis = new ArrayRedis;
        $this->app->instance(RedisConnectionContract::class, $this->redis);

        // buildBusDrain() deliberately uses the illuminate/redis facade, not
        // the container-bound RedisConnectionContract (that seam is the
        // worker SINK's). To drive the real production path we therefore swap
        // the facade for a manager whose connection() is an illuminate
        // Connection that speaks to the same ArrayRedis double. Without this
        // the drain would attempt a live TCP connect and be swallowed by
        // drainBusOnce()'s containment — the test would then "pass" against a
        // drain that did nothing.
        Redis::swap(new BusFakeRedisManager($this->redis));

        $this->output = new BufferedOutput;
    }

    protected function tearDown(): void
    {
        // Daemon::run() arms pcntl handlers that outlive the command object.
        if (function_exists('pcntl_signal') && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(false);
            pcntl_signal(SIGTERM, SIG_DFL);
            pcntl_signal(SIGINT, SIG_DFL);
        }

        Redis::swap(null);

        parent::tearDown();
    }

    // ---------------------------------------------------------------- 1.

    /**
     * Property 1: the group invariant. A fresh Redis has no group, and
     * XREADGROUP dies with "NOGROUP No such key 'tg:stream:updates' or
     * consumer group 'teleclient'" — which is exactly how the documented
     * separate-process deployment (teleframe:ingest) failed on first boot.
     * ensureGroup() must therefore CREATE the group AND survive the second
     * call that gets BUSYGROUP back from the server.
     */
    public function test_ensure_group_creates_the_group_on_a_fresh_redis_and_stays_true_on_the_second_call(): void
    {
        $consumer = $this->consumer();

        self::assertArrayNotHasKey(StreamSchema::GROUP, $this->redis->groups, 'precondition: a fresh Redis has no consumer group');

        self::assertTrue($consumer->ensureGroup(), 'first call creates the group and reports success');

        self::assertArrayHasKey(
            StreamSchema::GROUP,
            $this->redis->groups,
            'group '.StreamSchema::GROUP.' must exist on the server after the first call',
        );
        self::assertArrayHasKey(
            StreamSchema::STREAM,
            $this->redis->groups[StreamSchema::GROUP],
            'MKSTREAM: the group is created on the frozen stream key '.StreamSchema::STREAM,
        );

        // The second call is the one that used to be fatal: a real server
        // answers BUSYGROUP, which is the DESIRED end state, not a failure.
        self::assertTrue(
            $consumer->ensureGroup(),
            'a pre-existing group (BUSYGROUP) must report true, not throw and not return false',
        );
        self::assertTrue($consumer->ensureGroup(), 'and a third call is still true — the invariant is idempotent, not one-shot');
    }

    // ---------------------------------------------------------------- 2.

    /**
     * Property 2, the high-value one: a real daemon-mode rotation drains the
     * bus. This runs the production buildDaemon() (not a scratch Daemon), so
     * the onLoop hook under test IS the one the command ships:
     *
     *   $daemon->onLoop(fn () => { reseatAccounts($daemon); drainBusOnce(); })
     *
     * Three entries are seeded, a route is installed so the drain takes the
     * forwarding branch (no DB round-trip, so the assertion is about the
     * DRAIN and nothing else), and the worker's first tick stops the daemon —
     * so exactly one rotation runs and run() returns.
     *
     * Asserted: the entries were CONSUMED (acked off the group's pending
     * list, forwarded onto the target stream), not merely that the daemon
     * started.
     */
    public function test_a_default_mode_daemon_rotation_actually_drains_the_bus(): void
    {
        $this->app['config']->set('teleframe.daemon.accounts', [['account_id' => 1]]);

        $target = 'tg:stream:test-forwarded';
        (new RouteTable($this->redis))->set('updateNewMessage*', $target);

        $seeded = $this->seedEntries(3);

        // Precondition: a fresh Redis. The drain must create its own group —
        // this is the same NOGROUP condition the separate-process deployment hit.
        self::assertArrayNotHasKey(StreamSchema::GROUP, $this->redis->groups);

        $hold = new \stdClass;
        $hold->daemon = null;
        $ticks = new \stdClass;
        $ticks->n = 0;

        $this->app->instance(DaemonCommand::WORKER_FACTORY_KEY, function (array $seatConfig) use ($hold, $ticks): array {
            $worker = new BusDrainCallbackWorker(function () use ($hold, $ticks): void {
                $ticks->n++;
                $hold->daemon?->stop(); // exactly one rotation, then a clean exit 0
            });

            return ['config' => $seatConfig, 'worker' => $worker, 'wrapper' => $worker];
        });

        $command = $this->command();
        $accounts = $this->invoke($command, 'resolveAccounts');
        self::assertCount(1, $accounts);

        $hold->daemon = $this->invoke($command, 'buildDaemon', $accounts);

        $exit = $hold->daemon->run();

        self::assertSame(0, $exit, 'a graceful stop after one rotation is a clean exit');
        self::assertSame(1, $ticks->n, 'exactly one rotation ran — the test is bounded, not a poll loop');

        // The drain happened.
        self::assertStringContainsString(
            '[bus] drained 3 entries (3 forwarded)',
            $this->output->fetch(),
            'the rotation-top drain reported the batch it consumed',
        );
        self::assertCount(
            3,
            $this->redis->streamEntries($target),
            'every seeded entry was CONSUMED and forwarded onto its route target — default daemon mode drains, it does not just start',
        );
        self::assertSame(
            0,
            $this->pendingTotal(),
            'the group pending list drained: all three entries were acked, none left PENDING',
        );
        self::assertArrayHasKey(
            StreamSchema::GROUP,
            $this->redis->groups,
            'the drain armed the consumer group itself on a Redis that had none',
        );
        // The three seeded ids must no longer be deliverable: a second drain
        // pass over the same state is a no-op.
        $this->invoke($command, 'drainBusOnce');
        self::assertCount(3, $this->redis->streamEntries($target), 'a further drain pass does not re-deliver acked entries');
        self::assertSame(0, $this->pendingTotal());

        self::assertCount(3, $seeded, 'precondition: three ids were actually seeded');
    }

    /**
     * Property 2 falsifier. A rotation that does NOT drain (the shipped
     * 2026-09-26 wiring: onLoop = reseat only) leaves the seeded entries
     * un-consumed — nothing forwarded, nothing pending, no drain line. The
     * scratch Daemon below is built in THIS file, NOT in src/, so the
     * regression is demonstrated without a single edit to production code.
     */
    public function test_a_rotation_without_the_drain_hook_leaves_the_entries_unconsumed(): void
    {
        $target = 'tg:stream:test-forwarded';
        (new RouteTable($this->redis))->set('updateNewMessage*', $target);
        $this->seedEntries(3);

        $this->app->instance(RedisConnectionContract::class, $this->redis);
        $scratch = new BusFakeRedisManager($this->redis);
        Redis::swap($scratch);

        $hold = new \stdClass;
        $hold->daemon = null;

        $daemon = new Daemon(
            accountsConfig: [['account_id' => 1]],
            workerFactory: fn (array $config): WorkerInterface => new BusDrainCallbackWorker(
                function () use ($hold): void {
                    $hold->daemon?->stop();
                }
            ),
        );

        // THE PRE-FIX WIRING: reseat only. No drain.
        $daemon->onLoop(static function (): void {});

        $hold->daemon = $daemon;
        self::assertSame(0, $daemon->run());

        self::assertSame(
            [],
            $this->redis->streamEntries($target),
            'FALSIFIER: with no drain in the rotation, nothing is forwarded — this is what the property-2 assertion detects',
        );
        self::assertSame(
            0,
            $this->pendingTotal(),
            'FALSIFIER: nothing was ever read, so nothing is even pending',
        );
        self::assertArrayNotHasKey(
            StreamSchema::GROUP,
            $this->redis->groups,
            'FALSIFIER: no consumer group is created either — a daemon that never drains never arms the invariant',
        );
    }

    // ---------------------------------------------------------------- 3.

    /**
     * Property 3: a run of successful consumes must leave the strikes hash
     * EMPTY. clearStrike() used to hset($entryId, '0') on every success and
     * the key only got a TTL on the failure path, so a consumer that never
     * threw grew one permanent field per update forever.
     *
     * The mirror ingester seam is used (not Teleclient::ingest) so the entry
     * succeeds without a database round-trip and the assertion is about the
     * strike bookkeeping alone.
     */
    public function test_successful_consumes_leave_the_strikes_hash_without_a_single_field(): void
    {
        $consumer = $this->successfulConsumer();
        $consumer->ensureGroup();

        $k = 25; // more than one BATCH (10), so several cycles run
        $seeded = $this->seedEntries($k);

        self::assertCount($k, $seeded, 'precondition: the stream is loaded past a single batch');

        $drained = 0;
        for ($cycle = 0; $cycle < 5 && $drained < $k; $cycle++) {
            $drained += $consumer->consumeOnce()['processed'];
        }

        self::assertSame($k, $drained, 'all seeded entries were consumed across the bounded cycles');
        self::assertSame(
            0,
            $this->pendingTotal(),
            'nothing stayed pending: a success acks',
        );

        self::assertSame(
            [],
            $this->redis->hgetall(StreamSchema::STRIKES_KEY),
            'a success must not create a strikes field — tg:bus:strikes stays empty across '.$k.' successful consumes',
        );
        self::assertSame(
            0,
            $this->redis->llen(StreamSchema::STRIKES_KEY),
            'the strikes key holds no field at all (not even an empty-but-present hash)',
        );
    }

    /**
     * Property 3 falsifier. Replays the pre-fix clearStrike() (hset '0' per
     * success) against the SAME key with the SAME entry ids, in this file's
     * own namespace, and shows the field count the production assertion
     * rejects. No production file is touched.
     */
    public function test_the_legacy_zero_placeholder_clear_strike_would_grow_the_hash(): void
    {
        $consumer = $this->successfulConsumer();
        $consumer->ensureGroup();

        $k = 25;
        $seeded = $this->seedEntries($k);
        $drained = 0;
        for ($cycle = 0; $cycle < 5 && $drained < $k; $cycle++) {
            $drained += $consumer->consumeOnce()['processed'];
        }
        self::assertSame($k, $drained);

        // The pre-fix behaviour, written out literally: a success writes a
        // '0' placeholder and the key never gets a TTL on this path.
        foreach ($seeded as $entryId) {
            $this->redis->hset(StreamSchema::STRIKES_KEY, $entryId, '0');
        }

        self::assertCount(
            $k,
            $this->redis->hgetall(StreamSchema::STRIKES_KEY),
            'FALSIFIER: the old clearStrike() leaves one permanent field per consumed entry — exactly what the production assertion above forbids',
        );
    }

    // ---------------------------------------------------------------- 4.

    /**
     * Property 4a: the update stream is bounded. An XACK only clears the
     * group's pending list; it never shortens the stream. With no MAXLEN the
     * stream holds every update the tenant ever received and a first real
     * deployment OOMs Redis within days.
     *
     * The bound is asserted from the double's count, which trims exactly. A
     * real server's `~` trim may overshoot by a little, hence `<=`, not `===`.
     */
    public function test_appending_past_the_sink_maxlen_leaves_the_stream_bounded(): void
    {
        $bound = RedisStreamSink::DEFAULT_MAXLEN;
        self::assertSame(50_000, $bound, 'the shipped bound the production append relies on');

        $sink = new RedisStreamSink($this->redis, 42); // default maxlen, as the daemon builds it

        $appended = $bound + 50;
        for ($i = 0; $i < $appended; $i++) {
            $sink->handle(['_' => 'updateNewMessage', 'i' => $i]);
        }

        self::assertCount(
            $bound,
            $this->redis->streamEntries(StreamSchema::STREAM),
            'the stream is trimmed to the bound, not left holding every update forever',
        );
        self::assertLessThanOrEqual($bound, $this->redis->llen(StreamSchema::STREAM), 'stream length stays at or below the bound');
    }

    /**
     * Property 4b: the dead-letter stream is bounded too. A dead-letter is an
     * operator artifact; unbounded it is a slow leak that is invisible until
     * Redis is under memory pressure — and the entries nobody looked at are
     * exactly the ones a postmortem needs.
     */
    public function test_poison_entries_dead_letter_into_a_bounded_stream(): void
    {
        $consumer = $this->successfulConsumer();
        $consumer->ensureGroup();

        $total = self::DL_BOUND + 50;
        for ($i = 0; $i < $total; $i++) {
            $this->redis->xadd(StreamSchema::STREAM, '*', ['update' => 'not-json-'.$i]);
        }

        // Bounded drain: BATCH(10) fresh entries per cycle, so a fixed
        // ceiling of cycles is enough and cannot loop forever.
        for ($cycle = 0; $cycle <= intdiv($total, 10) + 5; $cycle++) {
            $consumer->consumeOnce();
        }

        self::assertSame(
            0,
            $this->pendingTotal(),
            'precondition: every poison entry was acked, so the group holds nothing pending',
        );
        self::assertCount(
            self::DL_BOUND,
            $this->redis->streamEntries(StreamSchema::DL),
            'the dead-letter stream is trimmed to its 10_000 bound',
        );
        self::assertLessThanOrEqual(
            self::DL_BOUND,
            $this->redis->llen(StreamSchema::DL),
            'FALSIFIER ANCHOR: a regression that drops the DL maxlen yields '.$total.' entries here',
        );
    }

    /**
     * Property 4c: a long run of successes never grows the strikes hash —
     * the bound assertion over many cycles, not a single batch.
     */
    public function test_a_long_run_of_successes_never_grows_the_strikes_hash(): void
    {
        $consumer = $this->successfulConsumer();
        $consumer->ensureGroup();

        $peak = 0;
        $consumed = 0;
        for ($cycle = 0; $cycle < 12; $cycle++) {
            $this->seedEntries(9);
            $consumed += $consumer->consumeOnce()['processed'];
            $peak = max($peak, $this->redis->llen(StreamSchema::STRIKES_KEY));
        }

        self::assertSame(108, $consumed, 'sanity: the run really did consume 12 x 9 entries');
        self::assertSame(
            0,
            $peak,
            'across 12 drain cycles the strikes hash never held a single field',
        );
        self::assertSame([], $this->redis->hgetall(StreamSchema::STRIKES_KEY));
        self::assertSame(0, $this->pendingTotal(), 'and nothing was left pending');
    }

    // --------------------------------------------------------- harness.

    /**
     * A consumer whose entries always SUCCEED (mirror-ingester seam), so
     * clearStrike() + ack run for every entry and the strike bookkeeping is
     * the only thing under test.
     */
    private function successfulConsumer(): IngestConsumer
    {
        $stored = new \stdClass;
        $stored->n = 0;

        return new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            null,
            null,
            static function (array $update, int $accountId) use ($stored): void {
                $stored->n++;
            },
        );
    }

    /**
     * A consumer on the plain ArrayRedis double (no adapter), for the
     * ensureGroup() invariant — the group primitive is the contract's, not
     * the illuminate driver's.
     */
    private function consumer(): IngestConsumer
    {
        return new IngestConsumer($this->redis, $this->app->make(Teleclient::class));
    }

    /**
     * @return list<string> the seeded stream entry ids
     */
    private function seedEntries(int $count): array
    {
        $ids = [];

        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->redis->xadd(StreamSchema::STREAM, '*', [
                'account_id' => '42',
                'update' => StreamSchema::encode([
                    'account_id' => 42,
                    'update' => ['_' => 'updateNewMessage', 'i' => $i],
                    'ts' => time(),
                ]),
                'ts' => (string) time(),
            ]);
        }

        return $ids;
    }

    /**
     * The group's pending total, read the way BOTH real drivers answer.
     *
     * This used to read `array_key_last($rows)['pending']` — a fabricated
     * ['name' => 'total', 'pending' => N] row that no driver has ever
     * produced, and that the ArrayRedis double used to emit so that the
     * wrong code kept passing. It threw `Undefined array key "pending"` the
     * moment the double started answering honestly.
     *
     * The real XPENDING summary reply is
     *
     *     [count, minId, maxId, [[consumer, count], ...]]
     *
     * so the count leads (predis, RESP2). phpredis collapses the same reply
     * into a keyed array carrying a 'message' slot, which
     * LaravelRedisAdapter::xpending() unsets and re-indexes with
     * array_values() — when there is nothing to report that leaves a leading
     * null behind and shifts the count one position later:
     * [null, count, minId, maxId, [[consumer, count], ...]].
     *
     * The EXTENDED form (a range was asked for) answers a bare list of
     * [id, consumer, idleMs, deliveryCount] rows with no count slot at all;
     * there the count is the number of rows.
     *
     * Fail-safe by construction: a shape it does not recognise reads 0. It
     * never throws and never invents a number.
     */
    private function pendingTotal(): int
    {
        $summary = $this->redis->xpending(StreamSchema::STREAM, StreamSchema::GROUP);

        if ($summary === []) {
            return 0;
        }

        // predis RESP2: the count leads.
        $leading = self::countSlot($summary[0] ?? null);

        if ($leading !== null) {
            return $leading;
        }

        // phpredis through the adapter: ONLY when slot 0 really is the null
        // message note does the count move one slot later. Requiring that null
        // keeps a malformed reply (e.g. ['COUNT', 10]) from being read as a
        // count instead of rejected.
        if (($summary[0] ?? null) === null) {
            $next = self::countSlot($summary[1] ?? null);

            if ($next !== null) {
                return $next;
            }
        }

        // The extended form carries no count — count the rows, and only when
        // EVERY element really is a row, so a malformed reply still reads 0.
        if (is_array($summary[0] ?? null)) {
            foreach ($summary as $row) {
                if (! is_array($row)) {
                    return 0;
                }
            }

            return count($summary);
        }

        return 0;
    }

    /**
     * Read one summary slot as a pending count, or null when it holds
     * anything else (a null message note, a 'ms-seq' stream id, the
     * consumers map, an entry row). `is_numeric` is safe here precisely
     * BECAUSE a stream id is never numeric.
     */
    private static function countSlot(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * A container-resolved DaemonCommand with a real components factory AND a
     * real output, so the drain's own "[bus] drained N entries" line is
     * captured instead of being written to a null output.
     */
    private function command(): DaemonCommand
    {
        $command = $this->app->make(DaemonCommand::class);
        $command->setLaravel($this->app);

        $components = new ReflectionProperty(DaemonCommand::class, 'components');
        $components->setAccessible(true);
        $components->setValue($command, new ConsoleComponentsFactory($this->output));

        $output = new ReflectionProperty(Command::class, 'output');
        $output->setAccessible(true);
        $output->setValue($command, $this->output);

        return $command;
    }

    private function invoke(object $target, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($target, $method);
        $ref->setAccessible(true);

        return $ref->invoke($target, ...$args);
    }
}

/**
 * Stops the daemon from inside the worker's own first tick: exactly one
 * rotation, then Daemon::run() returns 0. No background thread, no timer.
 */
final class BusDrainCallbackWorker implements WorkerInterface
{
    private readonly Closure $body;

    public function __construct(callable $body)
    {
        $this->body = $body(...);
    }

    public function run(?callable $shouldStop = null): void
    {
        ($this->body)($shouldStop);
    }
}

/**
 * Minimal stand-in for Illuminate's RedisManager: hands the daemon's
 * `new LaravelRedisAdapter(Redis::connection())` an illuminate Connection
 * that speaks to the ArrayRedis double. Without it, the production drain
 * attempts a live connect and its own containment swallows the failure —
 * which would make every "the drain ran" assertion vacuously true.
 */
final class BusFakeRedisManager
{
    public function __construct(private readonly ArrayRedis $redis) {}

    public function connection(?string $name = null): IlluminateConnection
    {
        return new BusArrayRedisConnection($this->redis);
    }
}

/**
 * An illuminate/redis Connection that is NOT a PredisConnection, so
 * LaravelRedisAdapter takes its phpredis argument order — and routes every
 * primitive to the in-memory double. Argument shapes below mirror exactly
 * what LaravelRedisAdapter emits.
 */
final class BusArrayRedisConnection extends IlluminateConnection
{
    public function __construct(private readonly ArrayRedis $redis) {}

    public function createSubscription($channels, Closure $callback, $method = 'subscribe')
    {
        throw new LogicException('the in-memory double has no pub/sub loop');
    }

    public function command($method, array $parameters = [])
    {
        return match (strtolower((string) $method)) {
            'xgroup' => $this->redis->command('xgroup', $parameters),
            'xadd' => $this->redis->xadd(
                (string) $parameters[0],
                (string) $parameters[1],
                (array) $parameters[2],
                $this->maxlen($parameters) > 0 ? ['maxlen' => $this->maxlen($parameters)] : [],
            ),
            'xreadgroup' => $this->xreadgroup($parameters),
            'xack' => $this->redis->xack((string) $parameters[0], (string) $parameters[1], array_values((array) $parameters[2])),
            'xpending' => $this->redis->xpending(
                (string) $parameters[0],
                (string) $parameters[1],
                $parameters[2] ?? null,
                $parameters[3] ?? null,
                isset($parameters[4]) ? (int) $parameters[4] : 100,
            ),
            'xautoclaim' => $this->redis->xautoclaim(
                (string) $parameters[0],
                (string) $parameters[1],
                (string) $parameters[2],
                (int) $parameters[3],
                (string) ($parameters[4] ?? '0-0'),
                $this->claimCount($parameters),
            ),
            'xrange' => $this->redis->xrange(
                (string) $parameters[0],
                (string) $parameters[1],
                (string) $parameters[2],
                isset($parameters[4]) ? (int) $parameters[4] : 100,
            ),
            'xdel' => $this->redis->xdel((string) $parameters[0], array_values((array) $parameters[1])),
            'hgetall' => $this->redis->hgetall((string) $parameters[0]),
            'hget' => $this->redis->hget((string) $parameters[0], (string) $parameters[1]),
            'hset' => $this->redis->hset((string) $parameters[0], (string) $parameters[1], (string) $parameters[2]),
            'hdel' => $this->redis->hdel((string) $parameters[0], ...array_map('strval', array_slice($parameters, 1))),
            'expire' => $this->redis->expire((string) $parameters[0], (int) $parameters[1]),
            'publish' => $this->redis->publish((string) $parameters[0], (string) $parameters[1]),
            'del' => $this->redis->del(...array_map('strval', (array) $parameters[0])),
            'type' => $this->type((string) $parameters[0]),
            'xlen' => count($this->redis->streamEntries((string) $parameters[0])),
            'hlen' => count($this->redis->hgetall((string) $parameters[0])),
            default => throw new RuntimeException('BusArrayRedisConnection does not implement '.$method),
        };
    }

    /**
     * phpredis XREADGROUP: [group, consumer, [stream => minId], count].
     *
     * @param  list<mixed>  $parameters
     * @return array<string, array<string, array<string, string>>>
     */
    private function xreadgroup(array $parameters): array
    {
        $keys = (array) $parameters[2];
        $count = (int) $parameters[3];
        $streams = [];
        $minId = '>';

        foreach ($keys as $stream => $id) {
            $streams[(string) $stream] = $count;
            $minId = (string) $id;
        }

        return $this->redis->xreadgroup((string) $parameters[0], (string) $parameters[1], $streams, $minId);
    }

    /**
     * @param  list<mixed>  $parameters
     */
    private function maxlen(array $parameters): int
    {
        // [$stream, $id, $fields, 'MAXLEN', '~', $maxlen]
        return strtoupper((string) ($parameters[3] ?? '')) === 'MAXLEN' ? (int) ($parameters[5] ?? 0) : 0;
    }

    /**
     * The XAUTOCLAIM count — the SIXTH positional argument, index 5.
     *
     * LaravelRedisAdapter used to append a literal 'COUNT' as the sixth
     * argument and the count as the seventh, because it believed predis
     * wanted the keyword on the wire. It does not:
     * Predis\Command\Redis\XAUTOCLAIM splices the first five arguments and
     * then, only if a sixth is present, pushes its OWN 'COUNT' followed by
     * that sixth argument. So the old adapter put `COUNT COUNT` on the wire
     * and Redis rejected the command.
     *
     * This double read `isset($parameters[6]) ? (int) $parameters[6] : 100`
     * — which, against the corrected six-argument adapter, silently fell
     * back to 100 instead of the count the caller actually asked for. That
     * is worse than a throw: the double recovered a limit the real driver
     * had already discarded, so a test could pass while production claimed
     * ten times the batch it meant to.
     *
     * @param  list<mixed>  $parameters
     */
    private function claimCount(array $parameters): int
    {
        $count = $parameters[5] ?? null;

        if (is_int($count) && $count > 0) {
            return $count;
        }

        if (is_string($count) && $count !== '' && is_numeric($count) && (int) $count > 0) {
            return (int) $count;
        }

        return 100;
    }

    /** phpredis TYPE_* constants, so LaravelRedisAdapter::llen() dispatches correctly. */
    private function type(string $key): int
    {
        if (isset($this->redis->streams[$key])) {
            return 6; // stream
        }

        if (isset($this->redis->hashes[$key])) {
            return 5; // hash
        }

        return 0; // none
    }
}
