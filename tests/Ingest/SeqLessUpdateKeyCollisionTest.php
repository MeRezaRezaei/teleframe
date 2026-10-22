<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * A seq-less update must own its fact key, not share one slot with every other
 * seq-less update of the same account.
 *
 * ## THE DEFECT THIS EXISTS TO KILL
 *
 * `writeUpdate()` used to declare `int $seq = 0` and the seq-less callers relied
 * on that default, so EVERY seq-less update of an account landed on the single
 * key `(account_id, 0, 0)`. Immediately after each `updateOrInsert()` the
 * ingestor ran `deleteUpdateChildren()` for that key and rewrote the children,
 * so the arriving update destroyed the PREVIOUS update's hand-authored facts
 * across every `tf_updates_*` child table. The prior `tf_updates` row survived
 * as a shell with its facts gone.
 *
 * Measured with the pre-fix class (probe over sqlite :memory:), three
 * consecutive seq-less updates produced this, on DEFAULT settings, with no
 * non-default flag in sight:
 *
 *   after #1: tf_updates (0,0)                        pts_child rows=1  pts=1001
 *   after #2: tf_updates (0,0)                        pts_child rows=1  pts=1002   <- #1 DESTROYED
 *   after #3: tf_updates (0,0)                        pts_child rows=1  pts=1003   <- #2 DESTROYED
 *
 * ## WHAT THE KEY IS NOW, AND WHY THESE TESTS NAME A FRAME
 *
 * A seq-less update has no delivering seq to key on, so one has to be supplied
 * by the caller as a FRAME IDENTITY — the Redis stream entry id it arrived
 * under. With one, `UpdateIngestor::frameKey()` derives a negative
 * `(seq, position)` from it (see `SeqLessFrameKeyedUpdateTest` for the
 * derivation and the collision policy). Two changes followed from that:
 *
 *  - `MAX(seq) + 1` is gone. It fixed this file's first defect and caused a
 *    worse one: a counter is not stable across the `xautoclaim` redelivery the
 *    bus performs, so one frame delivered twice appended a duplicate row.
 *  - the `seq, position` VALUES are no longer `0, 1, 2` — they are derived, so
 *    no test may assert a literal. They assert RELATIONSHIPS instead: distinct
 *    keys, one set of children each, a stable key across re-delivery. That is
 *    the meaning each test below was written to hold, and it is unchanged.
 *
 * THE DIRECT PATH IS THE EXCEPTION, AND IT IS NO LONGER A CONSTANT. A caller
 * with no frame identity — `Teleframe::ingest()`, `Teleclient::ingest()` — has
 * no frame, so the key is derived from the update's OWN CONTENT: same payload
 * + same account ⇒ same key, so a re-ingest still upserts, while two different
 * frameless updates get two different keys and neither can purge the other's
 * children.
 * `test_the_direct_path_derives_a_key_from_the_update_content` holds that, and
 * `test_byte_identical_frameless_updates_upsert_onto_one_row` pins the residual
 * it costs (two byte-identical frameless updates fold onto one row), so the
 * trade is a gate rather than a comment. Backfill is not exposed to any of it:
 * its pages are `messages.messages` envelopes, which route to writeEnvelope().
 */
final class SeqLessUpdateKeyCollisionTest extends IngestTestCase
{
    private const ACCOUNT = 4242;

    /** A different tenant, so cross-account bleed would be visible too. */
    private const OTHER_ACCOUNT = 4243;

    /** The one child table this file asserts on by value. */
    private const PTS = 'tf_updates_pts';

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT, self::OTHER_ACCOUNT];
    }

    /**
     * The regression: three seq-less updates on three frames, three keys, three
     * sets of facts.
     *
     * Asserts on the CHILD VALUES and not merely on a row count: a count alone
     * would be satisfied by one surviving row whose value had been overwritten
     * by the last update, which is precisely what the pre-fix code produced
     * (rows=1 at every step, carrying only the newest update's pts).
     */
    public function test_each_seq_less_update_keeps_its_own_children(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->update(1001, 1), self::ACCOUNT, $this->frame(1));
        $ingestor->ingest($this->update(1002, 2), self::ACCOUNT, $this->frame(2));
        $ingestor->ingest($this->update(1003, 3), self::ACCOUNT, $this->frame(3));

        // Three updates, three rows: the key space no longer collapses.
        self::assertSame(3, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());

        // THE ASSERTION. The first update's facts are still there, still ITS
        // facts, and still at ITS key. Pre-fix this row's child was deleted by
        // update #2.
        self::assertSame(
            [1001, 1002, 1003],
            $this->ptsValues(self::ACCOUNT),
            'every seq-less update must keep its own pts child; the first must not be purged by a later one',
        );

        $keys = $this->keys(self::ACCOUNT);
        self::assertCount(3, $keys, 'three frames must derive three distinct keys');
        foreach ($keys as $key) {
            self::assertLessThan(0, $key['seq'], 'a derived key is always negative');
        }

        // THE DERIVATION IS STABLE, which is the other half of the requirement
        // and the half the `MAX(seq) + 1` counter failed. Re-delivering all
        // three frames — what `xautoclaim` does to in-flight entries — must
        // land on the same three keys with the same three children, not append
        // three more rows. Under the counter this file would read 6 rows.
        $ingestor->ingest($this->update(1001, 1), self::ACCOUNT, $this->frame(1));
        $ingestor->ingest($this->update(1002, 2), self::ACCOUNT, $this->frame(2));
        $ingestor->ingest($this->update(1003, 3), self::ACCOUNT, $this->frame(3));

        self::assertSame(3, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        self::assertSame($keys, $this->keys(self::ACCOUNT), 'a re-delivered frame re-derives the same key');
        self::assertSame([1001, 1002, 1003], $this->ptsValues(self::ACCOUNT));

        // The sibling 1:1 children survive too, not just the one asserted on.
        foreach (['tf_updates_pts_count', 'tf_updates_qts'] as $table) {
            self::assertSame(
                3,
                DB::table($table)->where('account_id', self::ACCOUNT)->count(),
                "{$table} must hold one row per seq-less update",
            );
        }
    }

    /**
     * NEGATIVE CONTROL — the same destruction, produced on purpose.
     *
     * Two updates carrying the SAME delivering seq, in the same slot of one
     * container, put both on the same (account, seq, position). This asserts
     * the purge really does fire and really does take the prior occupant's
     * children with it.
     *
     * If this ever fails, the survival assertion in the test above is
     * vacuous: it would be reporting "nothing was destroyed" about a harness in
     * which nothing is ever destroyed.
     */
    public function test_a_shared_key_still_purges_the_prior_children(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->container('updatesCombined', 55, $this->update(7001, 1)), self::ACCOUNT);
        self::assertSame([7001], $this->ptsValues(self::ACCOUNT), 'control setup: the first update wrote its child');

        // Same delivering seq, same slot: a genuine key collision.
        $ingestor->ingest($this->container('updatesCombined', 55, $this->update(7002, 1)), self::ACCOUNT);

        self::assertSame(
            [7002],
            $this->ptsValues(self::ACCOUNT),
            'control: two updates on ONE key must leave only the later one\'s child — the purge is live',
        );
    }

    /**
     * A delivering seq is a real key and must not be reassigned.
     *
     * Replay/backfill idempotency rests on this: the same container arriving
     * twice must upsert onto the same row, not mint a second one. The derived
     * negative range does not touch it — real keys are >= 0 by construction.
     */
    public function test_a_delivering_seq_keeps_its_exact_key_and_is_idempotent(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->container('updates', 900, $this->update(9001, 1)), self::ACCOUNT);
        $ingestor->ingest($this->container('updates', 900, $this->update(9001, 1)), self::ACCOUNT);

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'a replayed container must upsert onto its own key, not allocate a new seq',
        );
        self::assertSame([9001], $this->ptsValues(self::ACCOUNT), 'the delivering seq is the key, untouched');
        self::assertSame([['seq' => 900, 'position' => 0]], $this->keys(self::ACCOUNT));
    }

    /**
     * The key space is scoped to ONE account, not shared.
     *
     * The account leads the digest, so two tenants ingesting under their own
     * identities cannot reach one another's key even when their frames happen
     * to coincide — and, the other way round, one tenant's rows never appear in
     * the other's.
     */
    public function test_the_key_space_is_scoped_to_one_account(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->update(5001, 1), self::ACCOUNT, $this->frame(1));
        $ingestor->ingest($this->update(5002, 2), self::ACCOUNT, $this->frame(2));
        $ingestor->ingest($this->update(6001, 1), self::OTHER_ACCOUNT, $this->frame(1));

        self::assertSame([5001, 5002], $this->ptsValues(self::ACCOUNT));
        self::assertSame([6001], $this->ptsValues(self::OTHER_ACCOUNT));

        // The SAME frame id under both accounts derives DIFFERENT keys. Under a
        // derivation that hashed the frame alone, tenant 8 would have landed on
        // tenant 7's key and overwritten its facts.
        $first = $this->keys(self::ACCOUNT);
        $second = $this->keys(self::OTHER_ACCOUNT);
        self::assertNotSame($first[0], $second[0], 'the account must lead the derivation');
    }

    /**
     * THE DIRECT PATH — a caller with NO frame identity, keyed by the update's
     * OWN CONTENT.
     *
     * This used to pin `(0, 0)` and to pin the COST of it, because that was the
     * state: a direct caller had no frame, so the key was a constant, so two
     * DIFFERENT direct updates of one account shared it and the second purged
     * the first's children. It now asserts the fixed behaviour, which is that
     * there is no constant and no shared key — the only identity such a caller
     * has is the payload, so the payload is what the key is derived from.
     *
     * Three things are asserted here, and the middle one is the point of the
     * whole file: two DIFFERENT direct updates keep their own facts.
     */
    public function test_the_direct_path_derives_a_key_from_the_update_content(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->update(9501, 1), self::ACCOUNT);
        $first = $this->keys(self::ACCOUNT);

        self::assertCount(1, $first, 'one direct ingest writes one row');
        self::assertLessThan(
            0,
            $first[0]['seq'],
            'a frameless seq-less update must take a NEGATIVE seq like any other derived key. The negative '
            .'range is disjoint from real delivering seqs (always >= 0), which a constant (0, 0) is NOT.',
        );
        self::assertGreaterThanOrEqual(0, $first[0]['position']);

        // Same update again: an upsert. This is the property NestedIngestTest's
        // re-ingest stability rests on, and a content-derived key gives it by
        // construction rather than by everyone sharing one slot.
        $ingestor->ingest($this->update(9501, 1), self::ACCOUNT);
        self::assertSame(1, DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count());
        self::assertSame($first, $this->keys(self::ACCOUNT), 'a re-ingest re-derives the same key');
        self::assertSame([9501], $this->ptsValues(self::ACCOUNT));

        // A DIFFERENT update — THE REGRESSION. Under the constant (0, 0) this
        // second ingest landed on the first's key and deleted its pts child.
        $ingestor->ingest($this->update(9502, 2), self::ACCOUNT);

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'two different frameless updates must be two rows — the shared (0, 0) key is what made the second '
            .'one purge the first one\'s children',
        );
        self::assertSame(
            [9501, 9502],
            $this->ptsValues(self::ACCOUNT),
            'THE ASSERTION: both direct updates keep their own pts child. The first must not be destroyed by '
            .'the arrival of the second, which is the data loss this file exists to kill.',
        );
        self::assertNotSame(
            $first[0],
            $this->keys(self::ACCOUNT)[1],
            'two different payloads must derive two different keys',
        );
    }

    /**
     * THE RESIDUAL, ASSERTED ON PURPOSE rather than left in a comment.
     *
     * Two DIFFERENT updates whose payloads are BYTE-IDENTICAL now upsert onto
     * ONE row instead of being stored as two. That is the accepted trade: the
     * old behaviour stored two rows at the cost of destroying the first one's
     * children on the second arrival, so the loss went from "a fact is silently
     * erased" to "a duplicate record is collapsed".
     *
     * It is not reachable from the bus, which supplies a frame for every entry —
     * `SeqLessFrameKeyedUpdateTest::test_two_bus_entries_carrying_the_same_payload_are_two_rows`
     * asserts that two frames carrying one payload stay two rows, and must keep
     * passing. What collapses is only a caller handing the same bytes over twice
     * with no identity attached.
     */
    public function test_byte_identical_frameless_updates_upsert_onto_one_row(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingest($this->update(9601, 1), self::ACCOUNT);
        $ingestor->ingest($this->update(9601, 1), self::ACCOUNT);

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'ACCEPTED RESIDUAL, asserted on purpose: two frameless updates whose payloads are byte-identical '
            .'derive the same key and upsert onto one row. Strictly better than the alternative, which was '
            .'two rows where the second arrival DELETED the first one\'s children.',
        );
        self::assertSame([9601], $this->ptsValues(self::ACCOUNT), 'the surviving row carries its own facts');
    }

    /**
     * A supplied frame id still WINS over the payload's own content.
     *
     * Without this, "derive from the content" and "ignore the caller" would be
     * indistinguishable, and the bus — which supplies a frame for every entry
     * precisely so two identical payloads stay two facts — would have been
     * silently downgraded to the weaker content key.
     */
    public function test_a_supplied_frame_id_is_still_honoured_over_the_payload(): void
    {
        $ingestor = new UpdateIngestor;
        $payload = $this->update(9701, 1);

        // The SAME payload: once with no frame, once with one. Same bytes, so
        // the content key would be identical — the rows can only differ if the
        // frame actually reached the derivation.
        $ingestor->ingest($payload, self::ACCOUNT);
        $ingestor->ingest($payload, self::ACCOUNT, $this->frame(1));

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'one payload ingested with and without a frame id must be two rows: the frame is honoured',
        );
    }

    /**
     * A batch of bare frameless updates must NOT collapse onto one key.
     *
     * `ingestBatch()` documents "one frame identity for the whole batch", and
     * with a frame supplied that is exactly right — the frame, not the payload,
     * decides. With NO frame, each member derives its own key from its own
     * content, so a page of updates is a page of rows. Under the constant
     * `(0, 0)` this test read ONE row carrying only the last member's pts.
     */
    public function test_a_batch_of_frameless_updates_does_not_collapse(): void
    {
        $ingestor = new UpdateIngestor;

        $ingestor->ingestBatch([
            $this->update(9801, 1),
            $this->update(9802, 1),
            $this->update(9803, 1),
        ], self::ACCOUNT);

        self::assertSame(
            3,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'three different frameless updates in one batch are three rows — a batch is not one key',
        );
        self::assertSame(
            [9801, 9802, 9803],
            $this->ptsValues(self::ACCOUNT),
            'every member of the batch keeps its own pts child',
        );
    }

    /**
     * A Redis stream entry id, in the `<ms>-<seq>` shape `XADD *` produces.
     */
    private function frame(int $n): string
    {
        return '1724852400000-'.$n;
    }

    /**
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
     * The pts child VALUES, ascending by VALUE.
     *
     * Sorted by value rather than by seq because a DERIVED key carries no
     * arrival order — that is the point of it. `seq` needs to be monotonic for
     * nothing in this package: the only `orderBy('seq')` in `src/` is a
     * one-time data-folding migration, not a runtime read path — so the rows
     * come back in hash order, and a test that read them in arrival order
     * would be asserting an accident of the old counter.
     *
     * Asserted on by value, never by count: a count alone is satisfied by one
     * surviving row carrying only the newest update's pts, which is what the
     * pre-fix code produced.
     *
     * @return list<int>
     */
    private function ptsValues(int $accountId): array
    {
        $values = DB::table(self::PTS)->where('account_id', $accountId)
            ->orderBy('seq')->orderBy('position')
            ->pluck('pts')->map(static fn ($p): int => (int) $p)->all();

        sort($values);

        return $values;
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
