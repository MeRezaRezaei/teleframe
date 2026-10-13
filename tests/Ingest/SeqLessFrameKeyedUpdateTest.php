<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Teleclient;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use RuntimeException;

/**
 * A seq-less update's fact key must be DERIVED FROM THE FRAME that carried it.
 *
 * ## THE DEFECT THIS EXISTS TO KILL
 *
 * A seq-less update is a bare `update*` ctor with no container naming a
 * delivering seq, so nothing on the payload itself can key the `tf_updates`
 * row. Three schemes have been tried against that gap and only this one
 * satisfies both halves of the requirement:
 *
 *  1. `(account_id, 0, 0)` — every seq-less update of an account shared one
 *     key, and each arrival purged its predecessor's ~14 child tables.
 *  2. `MAX(seq) + 1` — a counter. It fixed the loss, and introduced a worse
 *     defect: `IngestConsumer` re-delivers in-flight entries via `xautoclaim`,
 *     so the SAME frame arriving twice read `MAX` again after the first
 *     delivery committed, took the NEXT value, and appended a DUPLICATE row
 *     where an upsert was required.
 *  3. THIS ONE — derive a negative `(seq, position)` from the Redis frame id.
 *     Deterministic, so a re-delivery re-derives the same key and upserts;
 *     injective enough that two different frames get two different keys.
 *
 * ## WHY THIS FILE IS THE GATE AND NOT SeqLessUpdateKeyCollisionTest
 *
 * Because the four tests in that file all pass against a HALF-APPLIED fix.
 * Threading the entry id into `IngestConsumer` and deriving the key in
 * `UpdateIngestor` are two separate edits; do only the second and every one of
 * those four still passes, because the bus never supplies a frame and the
 * direct path never had one to supply. `test_a_bus_entry_lands_on_a_negative_seq`
 * is the assertion that fails in that state — a seq is NEGATIVE only when the
 * frame id actually reached the key derivation, and the direct path's `(0, 0)`
 * can never produce one. Everything else here supports it.
 *
 * `test_one_frame_key_over_two_updates_collapses_to_a_single_row` is the
 * NEGATIVE CONTROL in the other direction: it forces two DIFFERENT updates onto
 * one derived key and asserts they collapse, so the survival assertions in the
 * other tests are measurements of a harness in which destruction is real
 * rather than a fixture that cannot fail.
 */
final class SeqLessFrameKeyedUpdateTest extends IngestTestCase
{
    private const ACCOUNT = 4242;

    /** A different tenant, so cross-account bleed would be visible too. */
    private const OTHER_ACCOUNT = 4243;

    /** The one child table asserted on by value. */
    private const PTS = 'tf_updates_pts';

    private ArrayRedis $redis;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, self::OTHER_ACCOUNT];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
    }

    /**
     * THE GATE. A bus entry's seq-less update lands on a NEGATIVE seq.
     *
     * This is the assertion a half-applied fix cannot satisfy. The key
     * derivation produces `[-1073741824, -1]`; the direct no-frame path
     * produces `(0, 0)`. So a negative seq read back off a row the CONSUMER
     * wrote is proof that the entry id was threaded from
     * `IngestConsumer::handleEntry()` all the way into
     * `UpdateIngestor::withinFrame()` and on into the derivation. Drop the
     * `withinFrame()` call and this reads 0, which the next test's shape would
     * never catch.
     */
    public function test_a_bus_entry_lands_on_a_negative_seq(): void
    {
        $this->publish($this->update(8001, 1));
        $this->consume();

        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());

        $row = DB::table('tf_updates')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row);
        self::assertLessThan(
            0,
            (int) $row->seq,
            'a frame-keyed seq-less update must take a NEGATIVE seq. The negative range is disjoint from '
            .'real delivering seqs (always >= 0), which is the half of the requirement the MAX(seq)+1 '
            .'counter could not give. A seq of 0 here means the entry id never reached the derivation.',
        );
        self::assertGreaterThanOrEqual(0, (int) $row->position);
        self::assertSame('updateNewMessage', (string) $row->constructor);
        self::assertSame([8001], $this->ptsValues(self::ACCOUNT));
    }

    /**
     * THE CORE. The SAME frame re-delivered is ONE row with ONE set of children.
     *
     * `onStored` throws, which is a documented in-band failure: the ingest has
     * already committed inside `handleEntry()`'s try, the hook then fails, so
     * the entry is never acked and stays in the group's pending list. The next
     * `consumeOnce()` re-reads own pending (`0`) and delivers the very same
     * Redis entry id again — the same redelivery `xautoclaim` performs after a
     * worker dies, which is what the `MAX(seq) + 1` counter turned into a
     * duplicate row.
     */
    public function test_a_redelivered_bus_entry_upserts_onto_one_row(): void
    {
        $consumer = $this->consumerThatFailsAfterStoring();

        $this->publish($this->update(8002, 1));

        // Delivery #1: the row commits, the hook throws, nothing is acked.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        $keyAfterFirst = $this->keys(self::ACCOUNT);

        // Delivery #2: the SAME entry id, re-read from pending.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'a re-delivered frame must UPSERT onto the row it already wrote, not append a second one. '
            .'Under MAX(seq)+1 this is 2.',
        );
        self::assertSame(
            $keyAfterFirst,
            $this->keys(self::ACCOUNT),
            'the re-delivery must land on the SAME key — that is what makes it an upsert',
        );
        self::assertSame([8002], $this->ptsValues(self::ACCOUNT), 'one set of children, not two');
        self::assertSame(1, $this->childCount('tf_updates_pts_count'));
    }

    /**
     * TWO DIFFERENT FRAMES ARE TWO ROWS — and this is also what rules out the
     * tempting shortcut of hashing the PAYLOAD instead of the frame.
     *
     * Both entries carry byte-identical payloads. A content-derived key would
     * collapse them onto one row; a frame-derived key cannot, because the
     * frames differ. It also proves the direct-path `(0, 0)` is not what is
     * happening here: two rows is two keys.
     */
    public function test_two_bus_entries_carrying_the_same_payload_are_two_rows(): void
    {
        $this->publish($this->update(8003, 1));
        $this->publish($this->update(8003, 1));

        $this->consume();

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'two frames carrying one payload are two facts, not one',
        );

        $keys = $this->keys(self::ACCOUNT);
        self::assertCount(2, $keys);
        self::assertNotSame(
            [$keys[0]['seq'], $keys[0]['position']],
            [$keys[1]['seq'], $keys[1]['position']],
            'two frames must derive two different keys',
        );

        foreach ($keys as $key) {
            self::assertLessThan(0, $key['seq']);
        }

        self::assertSame([8003, 8003], $this->ptsValues(self::ACCOUNT), 'each frame keeps its own child');
        self::assertSame(2, $this->childCount('tf_updates_pts_count'));
    }

    /**
     * NEGATIVE CONTROL — the destruction, produced on purpose.
     *
     * Two DIFFERENT updates are forced onto ONE derived key by giving them the
     * same frame id. They must collapse to a single row carrying only the
     * later update's children.
     *
     * This makes the survival assertions above non-vacuous in two directions at
     * once. It proves the purge really does take the prior occupant's children
     * with it, so "each frame keeps its own children" is a measurement. And it
     * proves the key really IS the frame id: if `writeUpdate()` ignored the
     * frame and allocated per arrival instead, this would leave TWO rows and
     * fail.
     */
    public function test_one_frame_key_over_two_updates_collapses_to_a_single_row(): void
    {
        $ingestor = new UpdateIngestor;
        $frame = '1724852400000-0';

        $ingestor->ingest($this->update(8101, 1), self::ACCOUNT, $frame);
        $ingestor->ingest($this->update(8102, 2), self::ACCOUNT, $frame);

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'control: ONE frame key carries one row — the second update replaces the first',
        );
        self::assertSame(
            [8102],
            $this->ptsValues(self::ACCOUNT),
            "control: the second update's purge took the first update's children with it, so the "
            .'harness in which the other tests measure survival is one in which destruction is real',
        );
        self::assertSame(1, $this->childCount('tf_updates_pts_count'));
    }

    /**
     * The negative range cannot meet a real delivering seq, and a real
     * delivering seq keeps its exact key.
     *
     * `MAX(seq) + 1` could not say this: it allocated from the same integer
     * space Telegram delivers into, so a container whose delivering seq
     * happened to equal an allocated value collided with it.
     */
    public function test_a_frame_keyed_row_coexists_with_a_real_delivering_seq(): void
    {
        $this->publish($this->update(8201, 1));           // seq-less → frame key
        $this->publish($this->container('updates', 900, $this->update(8202, 1))); // delivering seq 900

        $this->consume();

        $keys = $this->keys(self::ACCOUNT);
        self::assertCount(2, $keys);

        $negative = array_values(array_filter($keys, static fn (array $k): bool => $k['seq'] < 0));
        $delivered = array_values(array_filter($keys, static fn (array $k): bool => $k['seq'] >= 0));

        self::assertCount(1, $negative, 'the seq-less update keeps its negative key');
        self::assertCount(1, $delivered);
        self::assertSame([900, 0], [$delivered[0]['seq'], $delivered[0]['position']], 'the delivering seq is the key, untouched');
    }

    /**
     * The derivation leads with the account, so one Redis entry id ingested
     * under two tenants yields two rows — never a cross-tenant overwrite.
     */
    public function test_the_same_frame_id_under_two_accounts_is_two_rows(): void
    {
        $ingestor = new UpdateIngestor;
        $frame = '1724852400000-7';

        $ingestor->ingest($this->update(8301, 1), self::ACCOUNT, $frame);
        $ingestor->ingest($this->update(8302, 2), self::OTHER_ACCOUNT, $frame);

        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::OTHER_ACCOUNT)->count());
        self::assertSame([8301], $this->ptsValues(self::ACCOUNT));
        self::assertSame([8302], $this->ptsValues(self::OTHER_ACCOUNT));
    }

    /**
     * A consumer whose `onStored` hook always throws: the ingest commits and the
     * entry is left un-acked, which is what makes the next cycle a redelivery.
     */
    private function consumerThatFailsAfterStoring(): IngestConsumer
    {
        return new IngestConsumer(
            $this->redis,
            $this->app->make(Teleclient::class),
            static function (): void {
                throw new RuntimeException('onStored refused the row (test hook)');
            },
        );
    }

    /** A plain consumer — the default ingest path, no hooks. */
    private function consume(): void
    {
        (new IngestConsumer($this->redis, $this->app->make(Teleclient::class)))->consumeOnce();
    }

    /**
     * @param  array<string, mixed>  $update
     */
    private function publish(array $update): void
    {
        (new RedisStreamSink($this->redis, self::ACCOUNT))->handle($update, (string) self::ACCOUNT);
    }

    /**
     * The account's `tf_updates` keys, ascending by seq.
     *
     * @return list<array{seq: int, position: int}>
     */
    private function keys(int $accountId): array
    {
        $rows = DB::table('tf_updates')->where('account_id', $accountId)
            ->orderBy('seq')->orderBy('position')->get(['seq', 'position']);

        return array_map(
            static fn ($row): array => ['seq' => (int) $row->seq, 'position' => (int) $row->position],
            $rows->all(),
        );
    }

    /**
     * The pts child VALUES, ascending by seq — asserted on by value, never by
     * count: a count alone is satisfied by one surviving row carrying the
     * newest update's value, which is what the pre-fix code produced.
     *
     * @return list<int>
     */
    private function ptsValues(int $accountId): array
    {
        return DB::table(self::PTS)->where('account_id', $accountId)
            ->orderBy('seq')->orderBy('position')
            ->pluck('pts')->map(static fn ($p): int => (int) $p)->all();
    }

    private function childCount(string $table): int
    {
        return DB::table($table)->where('account_id', self::ACCOUNT)->count();
    }

    /**
     * A seq-less `update*` payload. No nested `message`, so this exercises the
     * update fact row and its hand-authored children and nothing else.
     *
     * @return array<string, mixed>
     */
    private function update(int $pts, int $ptsCount): array
    {
        return [
            '_' => 'updateNewMessage',
            'pts' => $pts,
            'pts_count' => $ptsCount,
            'qts' => $pts + 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $member
     * @return array<string, mixed>
     */
    private function container(string $ctor, int $seq, array $member): array
    {
        return [
            '_' => $ctor,
            'seq' => $seq,
            'updates' => [$member],
        ];
    }
}
