<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
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
 *     defect: the bus re-delivers in-flight entries via `xautoclaim`, so the
 *     SAME frame arriving twice read `MAX` again after the first delivery
 *     committed, took the NEXT value, and appended a DUPLICATE row where an
 *     upsert was required.
 *  3. THIS ONE — derive a negative `(seq, position)` from the frame identity.
 *     Deterministic, so a re-delivery re-derives the same key and upserts;
 *     injective enough that two different frames get two different keys.
 *
 * ## WHERE THE FRAME COMES FROM NOW, AND WHY THAT MOVED THIS FILE'S FIXTURE
 *
 * Under the S1 inversion (D-039/D-040) the bus entry names a row that the
 * PRODUCER already committed, so the entry no longer carries the payload and
 * `IngestConsumer` no longer writes: it resolves the key it is given. That
 * leaves no Redis entry id at the moment a seq-less key must be derived —
 * the id is minted by the `xadd` that happens AFTER the commit. So the frame
 * is minted one layer up instead, by the producer (D-041,
 * `producer:<accountId>:<fromPts>:<ordinal>`, `UpdatePollerService::
 * mintProducerFrame()`), threaded `streamUserPayload()` →
 * `processUpdate()` → `CommittedRowSink::handleCommitted()`, which commits
 * through `UpdateIngestor` under that frame and only then appends the entry.
 *
 * So the identity that keys a seq-less row is UNCHANGED in kind and moved one
 * hop up the flow. The derivation, the negative range, the re-delivery
 * stability and the two-frames-two-rows property are all still exactly what
 * this file exists to pin; what changed is the call the bus path makes. The
 * fixtures publish through `handleCommitted()` — the production producer call
 * — instead of `handle()`, which under the v2 contract commits nothing and
 * therefore publishes `-1/-1` sentinels naming no row at all.
 *
 * ## WHY THIS FILE IS THE GATE AND NOT SeqLessUpdateKeyCollisionTest
 *
 * Because the four tests in that file all pass against a HALF-APPLIED fix.
 * Threading the frame into the producer and deriving the key in
 * `UpdateIngestor` are two separate edits; do only the second and every one of
 * those four still passes, because the bus never supplies a frame and the
 * direct path never had one to supply.
 *
 * ⛔ WHICH TEST IS THE GATE CHANGED WITH THE INVERSION, AND IT IS RECORDED
 * HERE RATHER THAN LEFT IMPLIED. It used to be
 * `test_a_bus_entry_lands_on_a_negative_seq`: a seq is NEGATIVE only when the
 * frame reached the derivation. That argument no longer holds on its own,
 * because `UpdateIngestor::contentIdentity()` — the frameless fallback — goes
 * through the SAME `frameKey()` and is therefore ALSO negative. A dropped
 * frame now produces a negative key and a green gate. So:
 *
 *  - `test_two_bus_entries_carrying_the_same_payload_are_two_rows` is the gate
 *    that fails on a half-applied frame-threading. Two byte-identical payloads
 *    under two different frames collapse onto ONE row the moment the frame
 *    stops reaching the derivation, because content identity is what remains.
 *  - and `test_a_bus_entry_lands_on_a_negative_seq` carries a second,
 *    in-test witness for the same property, so it cannot go vacuous either:
 *    the key the bus wrote must be the key a frame-keyed re-ingest derives,
 *    and the frameless re-ingest of the same payload must land elsewhere.
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
     * THE RANGE. A seq-less update published on the bus lands on a NEGATIVE
     * seq, in the range a real delivering seq can never occupy.
     *
     * This is the assertion a half-applied fix could not satisfy back when the
     * frameless path was the constant `(0, 0)` — see the class docblock for
     * why the frameless path is now `contentIdentity()` instead, and for why
     * `test_two_bus_entries_carrying_the_same_payload_are_two_rows` carries
     * the load of being the gate. The extra witness at the bottom of this
     * test keeps the negative range from passing on its own.
     */
    public function test_a_bus_entry_lands_on_a_negative_seq(): void
    {
        $frame = self::producerFrame(7001, 0);

        $this->publish($this->update(8001, 1), $frame);
        $this->consume();

        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());

        $row = DB::table('tf_updates')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row);
        self::assertLessThan(
            0,
            (int) $row->seq,
            'a frame-keyed seq-less update must take a NEGATIVE seq. The negative range is disjoint from '
            .'real delivering seqs (always >= 0), which is the half of the requirement the MAX(seq)+1 '
            .'counter could not give.',
        );
        self::assertGreaterThanOrEqual(0, (int) $row->position);
        self::assertSame('updateNewMessage', (string) $row->constructor);
        self::assertSame([8001], $this->ptsValues(self::ACCOUNT));

        // THE FRAME IS IN THE KEY — a negative seq alone no longer proves it,
        // because the frameless `contentIdentity()` path lands in the same
        // negative range. Two more ingests of the SAME payload separate them.
        $busKey = $this->keys(self::ACCOUNT)[0];

        $ingestor = new UpdateIngestor;
        $ingestor->ingest($this->update(8001, 1), self::ACCOUNT, $frame);

        self::assertSame(
            [$busKey],
            $this->keys(self::ACCOUNT),
            'the key the bus row carries IS the frame key: re-ingesting under that frame UPSERTS onto it',
        );

        $ingestor->ingest($this->update(8001, 1), self::ACCOUNT);

        self::assertCount(
            2,
            $this->keys(self::ACCOUNT),
            'and the frameless identity is a DIFFERENT row, so the negative range alone proves nothing about '
            .'the frame reaching the derivation',
        );
    }

    /**
     * THE CORE. The SAME entry re-delivered resolves ONE row with ONE set of
     * children.
     *
     * `onStored` throws, which is a documented in-band failure: the hook fires
     * after the consumer has resolved the committed row, the hook then fails,
     * so the entry is never acked and stays in the group's pending list. The
     * next `consumeOnce()` re-reads own pending (`0`) and delivers the very
     * same Redis entry id again — the same redelivery `xautoclaim` performs
     * after a worker dies, which is what the `MAX(seq) + 1` counter turned into
     * a duplicate row.
     *
     * ⛔ WHICH SIDE WRITES MOVED, AND THE COUNT NOW MEASURES SOMETHING ELSE.
     *
     * The commit is the PRODUCER's, at publish time: `publish()` below goes
     * through `handleCommitted()`, so the row is already there before either
     * `consumeOnce()`. Under the old arrangement the first delivery wrote it,
     * so "still exactly one row after the re-delivery" proved the re-ingest
     * upserted. Here it proves the stronger, inverted thing: neither delivery
     * wrote anything at all — the producer wrote once, and a re-delivery
     * resolves the row it already names instead of appending a second one. The
     * child count and the pts value are asserted for the same reason: a second
     * write would replace them, and the file must not be satisfiable by a
     * single row that merely happens to carry the newest values.
     */
    public function test_a_redelivered_bus_entry_upserts_onto_one_row(): void
    {
        $consumer = $this->consumerThatFailsAfterStoring();

        $this->publish($this->update(8002, 1), self::producerFrame(7002, 0));

        // Delivery #1: the named row is resolved, the hook throws, nothing is acked.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());
        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        $keyAfterFirst = $this->keys(self::ACCOUNT);

        // Delivery #2: the SAME entry id, re-read from pending.
        self::assertSame(['processed' => 0, 'forwarded' => 0], $consumer->consumeOnce());

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'a re-delivered entry must resolve the row it already names, not append a second one — under '
            .'MAX(seq)+1 the second delivery read MAX again and took the NEXT value.',
        );
        self::assertSame(
            $keyAfterFirst,
            $this->keys(self::ACCOUNT),
            'and it must land on the SAME key — that is what makes a re-delivery an upsert',
        );
        self::assertSame([8002], $this->ptsValues(self::ACCOUNT), 'one set of children, not two');
        self::assertSame(1, $this->childCount('tf_updates_pts_count'));
    }

    /**
     * THE GATE. TWO DIFFERENT FRAMES ARE TWO ROWS — and this is also what
     * rules out the tempting shortcut of hashing the PAYLOAD instead of the
     * frame.
     *
     * Both entries carry byte-identical payloads. A content-derived key would
     * collapse them onto one row; a frame-derived key cannot, because the
     * frames differ.
     *
     * ⛔ THIS IS THE TEST THAT FAILS ON A HALF-APPLIED FRAME-THREADING, and
     * the class docblock says why it is no longer the negative-seq assertion:
     * `contentIdentity()` is ALSO negative, so dropping the frame leaves every
     * other test here green and collapses THIS one onto a single row. Read
     * that as the property it is — D-041's "two byte-identical payloads stay
     * two rows" — rather than as a side effect.
     *
     * The frames are the producer's own, `producer:<accountId>:<fromPts>:<ordinal>`,
     * differing in the ordinal exactly as `streamUserPayload()` numbers two
     * updates inside one difference window.
     */
    public function test_two_bus_entries_carrying_the_same_payload_are_two_rows(): void
    {
        $this->publish($this->update(8003, 1), self::producerFrame(7003, 0));
        $this->publish($this->update(8003, 1), self::producerFrame(7003, 1));

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
        $frame = self::producerFrame(7004, 0);

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
        $this->publish($this->update(8201, 1), self::producerFrame(7005, 0));           // seq-less → frame key
        $this->publish($this->container('updates', 900, $this->update(8202, 1)), self::producerFrame(7005, 1)); // delivering seq 900

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
     * The derivation leads with the account, so one frame ingested under two
     * tenants yields two rows — never a cross-tenant overwrite.
     */
    public function test_the_same_frame_id_under_two_accounts_is_two_rows(): void
    {
        $ingestor = new UpdateIngestor;
        $frame = self::producerFrame(7006, 0);

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
            static function (): void {
                throw new RuntimeException('onStored refused the row (test hook)');
            },
        );
    }

    /** A plain consumer — the default ingest path, no hooks. */
    private function consume(): void
    {
        (new IngestConsumer($this->redis))->consumeOnce();
    }

    /**
     * Publish one update the way the DAEMON's producer publishes it.
     *
     * `RedisStreamSink::handleCommitted()` — the call
     * `UpdatePollerService::processUpdate()` drives on a
     * `CommittedRowSink` — commits the row through `UpdateIngestor` FIRST,
     * under $frame, and only then appends the entry naming the committed
     * `(account_id, seq, position)`. That ordering is the S1 inversion
     * (D-039/D-040) and it is why a seq-less row CAN have a key: the frame is
     * the producer's, minted before the commit (D-041), because the Redis
     * entry id it used to be does not exist yet.
     *
     * ⚠ NOT `handle()`. The one-argument face commits nothing — no committer,
     * no row — so under the v2 contract it publishes the `-1/-1` sentinels
     * (D-042): an entry naming no row, which the consumer correctly refuses
     * and dead-letters. Every test here published through `handle()` before
     * the fixtures moved, and every one of them failed with a row count of
     * zero, which is that refusal showing up as "nothing was written".
     *
     * @param  array<string, mixed>  $update
     */
    private function publish(array $update, ?string $frame = null): void
    {
        (new RedisStreamSink($this->redis, self::ACCOUNT))
            ->handleCommitted($update, (string) self::ACCOUNT, new UpdateIngestor, $frame);
    }

    /**
     * A producer frame in the shape `UpdatePollerService::mintProducerFrame()`
     * mints: `producer:<accountId>:<fromPts>:<ordinal>` (D-041).
     *
     * A literal rather than a call to the poller, so the fixture states the
     * exact identity the derivation is fed instead of coupling this file to the
     * poller's construction — the same convention `tests/Laravel/
     * ProducerFrameTest.php` uses. The account segment is this file's
     * ACCOUNT, which is what makes "the same frame id under two accounts"
     * below a real cross-tenant comparison rather than two different strings.
     */
    private static function producerFrame(int $fromPts, int $ordinal): string
    {
        return 'producer:'.self::ACCOUNT.':'.$fromPts.':'.$ordinal;
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
