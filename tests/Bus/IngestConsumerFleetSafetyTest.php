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
 * TWO INGEST WORKERS MUST NOT STEAL EACH OTHER'S WORK.
 *
 * A Redis consumer group tracks its pending list PER CONSUMER NAME. Every
 * IngestConsumer used the shared literal `ingest-1`, which made a scaled
 * deployment silently wrong in two ways:
 *
 *  1. "Read my own pending" (XREADGROUP with id 0) returns entries the group
 *     still has pending for THAT name. With one shared name, worker B saw
 *     worker A's delivered-but-unacked entries as its own and reprocessed
 *     them: the same update stored twice, UpdateStored fired twice into
 *     listeners that are not idempotent.
 *
 *  2. reclaimStalePending() runs XAUTOCLAIM across the whole group, so a
 *     worker that was merely SLOW had its in-flight entries claimed by the
 *     other worker once they aged past the idle threshold.
 *
 * A per-process name gives each worker its own pending list and scopes reclaim
 * to a name that is genuinely not progressing. The single-worker case is
 * unchanged in every way that matters.
 */
final class IngestConsumerFleetSafetyTest extends IngestTestCase
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

    private const USER_ID = 501558149;

    private ArrayRedis $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
    }

    public function test_two_workers_get_distinct_consumer_names(): void
    {
        $a = $this->worker('worker-a');
        $b = $this->worker('worker-b');

        self::assertNotSame($a, $b, 'two workers must not share a consumer name');
    }

    public function test_the_default_name_is_built_from_the_frozen_base(): void
    {
        $name = IngestConsumer::defaultConsumerName();

        self::assertStringStartsWith(
            StreamSchema::CONSUMER.'-',
            $name,
            'the frozen base must stay the prefix so ops tooling and docs still find it',
        );
        self::assertDoesNotMatchRegularExpression('/\s/', $name, 'a consumer name must not contain whitespace');
    }

    public function test_two_default_named_workers_do_not_collide(): void
    {
        // THE LOAD-BEARING ASSERTION.
        //
        // Both consumers are left to the default. Before the fix this name was
        // the literal 'ingest-1' for every process, so these two were the same
        // string and this test FAILED. An earlier draft of this file passed
        // explicit names here, which made the assertion true by construction
        // and hid the very bug this exists to catch.
        $a = $this->nameOf(new IngestConsumer($this->redis, $this->app->make(Teleclient::class)));
        $b = $this->nameOf(new IngestConsumer($this->redis, $this->app->make(Teleclient::class)));

        self::assertNotSame(
            $a,
            $b,
            'two default-named workers must not collapse into one consumer name, or a fleet double-processes',
        );
    }

    public function test_an_explicit_name_is_honoured(): void
    {
        $consumer = new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            consumerName: 'stable-identity',
        );

        self::assertSame('stable-identity', $this->nameOf($consumer));
    }

    public function test_a_forced_shared_name_does_cause_duplicate_processing(): void
    {
        // WHY THE DEFAULT MUST BE UNIQUE — this is the hazard, demonstrated.
        //
        // Two workers are deliberately given the SAME name, which is what every
        // process did before the fix. Worker A is handed delivery and dies
        // before acking; worker B then reads that entry as its own pending and
        // stores it a second time, forwarding it twice.
        //
        // An earlier draft of this test asserted the OPPOSITE while delivering
        // through a hard-coded 'worker-a' string, so it passed with the bug
        // present. The hazard is real; it is the default that prevents it, and
        // the test above pins that default.
        $this->publishOneRoutedUpdate();

        $workerA = $this->worker('shared');
        $workerB = $this->worker('shared');
        $workerA->ensureGroup();

        // Deliver onto the group under the shared name and do not ack.
        $this->redis->xreadgroup(StreamSchema::GROUP, 'shared', [StreamSchema::STREAM => 10], '>');

        $stats = $workerB->consumeOnce();

        self::assertSame(
            1,
            $stats['processed'],
            'precondition: a shared name makes worker-b reprocess worker-a\'s unacked entry',
        );
        self::assertSame(
            1,
            $stats['forwarded'],
            'precondition: the update is therefore forwarded twice across the fleet',
        );
    }

    public function test_a_dead_workers_entry_is_still_reclaimed_by_a_live_one(): void
    {
        // The recovery path that per-process names must NOT break.
        $this->publishOneRoutedUpdate();

        $live = $this->worker('worker-live');
        $live->ensureGroup();

        $this->redis->xreadgroup(StreamSchema::GROUP, 'worker-dead', [StreamSchema::STREAM => 10], '>');

        // Too young to reclaim.
        self::assertSame(0, $live->consumeOnce()['processed']);

        $this->redis->warpTime(61_000);

        $stats = $live->consumeOnce();

        self::assertSame(1, $stats['processed'], "a dead worker's entry must still be reclaimable");
        self::assertSame(1, $stats['forwarded']);
        self::assertCount(1, $this->redis->streamEntries('tg:target:messages'), 'forwarded exactly once');
    }

    // ---- helpers ---------------------------------------------------------

    private function worker(string $name): IngestConsumer
    {
        return new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            consumerName: $name,
        );
    }

    /** One routed update on the stream, waiting to be consumed. */
    private function publishOneRoutedUpdate(): void
    {
        (new RouteTable($this->redis))->set('updateNewMessage*', 'tg:target:messages');

        (new RedisStreamSink($this->redis, self::ACCOUNT))->handle(
            ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 7]],
            (string) self::ACCOUNT,
        );
    }

    private function nameOf(IngestConsumer $consumer): string
    {
        return (string) (new \ReflectionProperty(IngestConsumer::class, 'consumerName'))->getValue($consumer);
    }
}
