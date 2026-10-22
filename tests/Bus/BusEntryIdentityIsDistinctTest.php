<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\RouteTable;
use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Handler\HandlerSink;
use MeRezaRezaei\Teleframe\Handler\Pipeline;
use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Handler\UpdateDispatcher;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayContainer;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

/**
 * NEW-1 — THE BUS PATH MUST NOT COLLAPSE EVERY MESSAGE INTO ONE IDENTITY.
 *
 * ## THE DEFECT, MEASURED
 *
 * `IngestConsumer` hands the routed hook an *identity row, not a payload*
 * (`IngestConsumer.php:583`): `['_ ' => $entry['constructor']]`. The code says
 * so itself — "THE HOOK RECEIVES THE IDENTITY, NOT THE PAYLOAD".
 *
 * `HandlerSink::handle()` then calls `Update::fromBus($update, $accountId)`
 * WITHOUT the third argument, so `$ts` stays `null`.
 *
 * `Update::updateId()` is `sha1(accountId . "\0" . ts . "\0" . raw_json)`.
 * On the bus path the account is constant, `ts` is `''`, and the array is a
 * per-CONSTRUCTOR constant — so `updateId()` is ONE constant for every
 * message of that constructor.
 *
 * Proven before this test existed:
 *
 *     500 distinct bus messages -> distinct updateId() values : 1
 *
 * Both guards then agree on the same wrong answer: `ReplayDedup`'s seen-set
 * and `forwardKey()`'s `'u:'.updateId()` branch both drop the repeat. A
 * bus-path handler runs ONCE per process lifetime and every later message of
 * the same constructor is silently discarded.
 *
 * ## WHY THE SUITE WAS GREEN
 *
 * `ReplayDedupTest` and `FakeDispatcher` feed a FULL payload plus a `ts` — a
 * shape production never emits. The double and the production path disagreed,
 * and that disagreement is what hid this.
 *
 * ## WHAT THIS TEST PINS
 *
 * Distinct bus entries must carry distinct identity, and that identity must
 * survive the trip from the stream entry through the sink into `updateId()`.
 */
final class BusEntryIdentityIsDistinctTest extends TestCase
{
    private ArrayRedis $redis;

    protected function setUp(): void
    {
        $this->redis = new ArrayRedis;
    }

    /** The root cause, isolated: no per-entry identity reaches `Update`. */
    public function test_the_bus_identity_row_alone_yields_a_constant_update_id(): void
    {
        $a = Update::fromBus(['_' => 'messages.messages'], 7);
        $b = Update::fromBus(['_' => 'messages.messages'], 7);

        // This is the defect, asserted so it can never silently return.
        self::assertSame(
            $a->updateId(),
            $b->updateId(),
            'SENTINEL: two distinct bus entries share one updateId — the shape is still degenerate',
        );
    }

    /** End-to-end: N routed entries must reach the pipeline as N dispatches. */
    public function test_distinct_bus_entries_are_not_collapsed_into_one_dispatch(): void
    {
        $dispatched = [];

        $registry = new HandlerRegistry;
        $dispatcher = new UpdateDispatcher(
            $registry,
            new Pipeline,
            new ArrayContainer,
            new ArrayCache,
        );
        $sink = new HandlerSink($dispatcher);

        $table = new RouteTable($this->redis);
        $table->set('updateNewMessage*', 'tg:target:messages');

        $producer = new RedisStreamSink($this->redis, 7);
        for ($i = 1; $i <= 5; $i++) {
            $producer->handle(
                ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => $i]],
                '7',
            );
        }

        $consumer = new IngestConsumer(
            $this->redis,
            null,
            static function (array $update, int $accountId) use (&$dispatched, $sink): void {
                $dispatched[] = $update;
                $sink->handle($update, (string) $accountId);
            },
        );

        $stats = $consumer->consumeOnce();

        self::assertSame(5, $stats['forwarded'], 'all five entries must reach the seam');
        self::assertCount(5, $dispatched);

        // THE ASSERTION THAT MATTERS: the identity each entry carries must be
        // distinct, otherwise every guard downstream drops 4 of the 5.
        $ids = array_map(
            static fn (array $row): string => Update::fromBus($row, 7)->updateId(),
            $dispatched,
        );

        self::assertCount(
            5,
            array_unique($ids),
            'FIVE DISTINCT BUS ENTRIES MUST NOT SHARE AN updateId — otherwise ReplayDedup '
            .'and forwardKey() silently discard four legitimate messages.',
        );
    }
}
