<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * Counts Redis round trips on the ingest hot path. A pure "does it still
 * route correctly" test cannot see a per-entry HGETALL at all — routing the
 * same update set one-per-HGETALL or from a single per-pass snapshot produces
 * byte-identical forwarded streams, so only the call count distinguishes them.
 */
final class RouteTableSnapshotCostTest extends IngestTestCase
{
    /**
     * The tenants this fixture writes under.
     *
     * MEASURED, not assumed: extracted from the account ids this file
     * actually inserts. It was undeclared, and with foreign-key enforcement
     * on (19 constraints) every row was refused at the DATABASE rather than
     * at the code under test — the test failed on its own fixture.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(): that helper
     * contracts that each test declares the tenants it means to use, so a
     * test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working. A blanket auto-seed would also
     * make the orphan-refusal tests unable to plant an orphan.
     */
    protected function ingestTenantIds(): array
    {
        return [7];
    }

    private const ACCOUNT = 7;

    private const TARGET = 'tg:target:messages';

    private ArrayRedis $redis;

    private IngestConsumer $consumer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $this->consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
        );
    }

    public function test_one_hgetall_per_batch_not_per_entry(): void
    {
        $this->seedRoute();

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        for ($i = 1; $i <= 5; $i++) {
            $sink->handle(
                ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => $i]],
                (string) self::ACCOUNT,
            );
        }

        $stats = $this->consumer->consumeOnce();

        self::assertSame(5, $stats['forwarded']);
        self::assertSame(
            1,
            $this->redis->hgetallCalls,
            'one HGETALL per pass, not one per entry (5 entries)',
        );

        // The work itself must have happened — never satisfiable by skipping it.
        self::assertCount(5, $this->redis->streamEntries(self::TARGET));
    }

    public function test_second_pass_reads_the_table_again(): void
    {
        $this->seedRoute();

        $sink = new RedisStreamSink($this->redis, self::ACCOUNT);
        $sink->handle(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 1]],
            (string) self::ACCOUNT,
        );

        $this->consumer->consumeOnce();
        self::assertSame(1, $this->redis->hgetallCalls);

        // Retarget the route BETWEEN passes, then give the next pass work.
        (new RouteTable($this->redis))->set('updateNewMessage*', 'tg:target:retargeted');
        $sink->handle(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 2]],
            (string) self::ACCOUNT,
        );

        $this->consumer->consumeOnce();

        // Per-pass, never cached across passes: a route change lands on the
        // very next pass, with no restart and no reload-channel publish.
        self::assertSame(
            2,
            $this->redis->hgetallCalls,
            'snapshot is per-pass, so a route change is picked up immediately',
        );
        self::assertCount(1, $this->redis->streamEntries('tg:target:retargeted'));
    }

    public function test_an_idle_pass_costs_no_hgetall_at_all(): void
    {
        $this->seedRoute();

        // The snapshot is read lazily, only once there is an entry to match.
        // A daemon spinning on an empty stream must not HGETALL the route
        // table on every rotation.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $this->consumer->consumeOnce());
        self::assertSame(0, $this->redis->hgetallCalls, 'an idle pass reads nothing');
    }

    public function test_set_does_not_publish_on_the_reload_channel(): void
    {
        $seen = [];
        $this->redis->subscribe(
            StreamSchema::RELOAD_CHANNEL,
            static function (string $channel, string $payload) use (&$seen): void {
                $seen[] = [$channel, $payload];
            },
        );

        (new RouteTable($this->redis))->set('updateNewMessage*', self::TARGET);

        // Why: nothing on this path announces a route change. The consumer
        // re-reads the whole table once per pass, so there is nothing to
        // invalidate and the reload channel stays free for account-lifecycle
        // signals.
        self::assertSame([], $seen);
    }

    private function seedRoute(): void
    {
        (new RouteTable($this->redis))->set('updateNewMessage*', self::TARGET);
        // set() is an hset, but reset anyway so the count measures the pass only.
        $this->redis->hgetallCalls = 0;
    }
}
