<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Bus\IngestConsumer;
use MeRezaRezaei\Teleframe\Bus\RedisStreamSink;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Teleframe;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

/**
 * TWO NAMED PATHS, AUDITED: neither reaches the seq-less `(0, 0)` keyspace.
 *
 * ## WHAT THIS FILE IS
 *
 * A closing task asserted that two production callers still ingest through
 * `UpdateIngestor::ingest()` with no frame id, and therefore still land on the
 * historical `(0, 0)` key where `deleteUpdateChildren()` purges a predecessor's
 * children. The two call sites named were:
 *
 *  - `MirrorIngesterFactory.php:153` and `:238` (the mirror path), and
 *  - `TeleframeServiceProvider.php:333` (a batch of messages built there).
 *
 * MEASURED against real SQLite with the shipped migrations: **neither call site
 * reaches `UpdateIngestor` at all**, so neither can collide, and neither needs
 * a frame identity. The two assertions that carry that are
 * `test_the_mirror_seam_writes_no_update_row_for_a_seq_less_update_ctor` and
 * `test_the_provider_batch_ingester_writes_no_update_row`. This file pins that
 * so a future change that DOES route either path into the update keyspace
 * cannot do it silently.
 *
 * ## WHY EACH PATH IS OUT OF THE KEYSPACE
 *
 * **The mirror path.** `MirrorIngesterFactory:153` calls
 * `MirrorUpdateIngester::ingest(ConnectionInterface $db, int $accountId, array
 * $payload, string $table, callable $hydrate)` and `:238` calls
 * `MirrorResultIngester::ingest(ConnectionInterface $db, int $accountId, array
 * $result)`. Neither class mentions `UpdateIngestor`, `frameKey` or
 * `deleteUpdateChildren`; they go through `MirrorFactDecomposer` +
 * `MirrorFactWriter`. The mirror catalog DOES declare `tf_updates` (keyed
 * `["seq","position"]`), but a seq-less update payload carries neither column,
 * so the row cannot cover its primary key and the writer SKIPS it rather than
 * store it under a fabricated key — see the clue asserted below. Absence of a
 * row is not a lost row here; it is the writer refusing to invent a key.
 *
 * **The provider path.** `:333` builds the envelope `['_' => 'messages.messages',
 * ...]`. Parsed from the shipped scheme, that ctor's result type is
 * `messages.Messages` and its parameters are `Vector<Message>`,
 * `Vector<ForumTopic>`, `Vector<Chat>`, `Vector<User>` — it declares no
 * `Vector<Update>`, so `updateCarriers()` does not contain it, and it is neither
 * a message ctor, an RPC-result ctor, nor an `update*` prefix. `ingestImpl()`
 * therefore reaches `writeEnvelope()`, which writes messages and takes no frame
 * id. This matches the note already on `UpdateIngestor::ingest()`: "Backfill is
 * not exposed to it: its pages are `messages.messages` envelopes, which route
 * to writeEnvelope(), not here."
 *
 * ## WHY THE DIRECT PATH IS NO LONGER EXCLUDED — D-040, 2026-10-13
 *
 * When this file was written, `Teleframe::ingest()` / `Teleclient::ingest()`
 * reached `writeUpdate()` with no frame and was deliberately OUT of scope: it
 * belonged to a different lane, so it served as the NEGATIVE CONTROL for this
 * file's zeros. That scoping is now WRONG, and the measurement is why.
 *
 * `TeleframeServiceProvider` binds `DaemonCommand::MIRROR_INGESTER_KEY`
 * unconditionally in every Laravel host, and `DaemonCommand` handed that
 * binding to `IngestConsumer`. So the daemon's bus drain always took the
 * mirror branch, the ingest branch below it was dead code in a default
 * install, and **the mirror branch writes no `tf_updates` row at all** — the
 * fact this file already pins for the seam itself. In a default install there
 * was therefore no committed row carrying `(account_id, seq, position)` for
 * the update bus to name, which is the object D-039's inversion is built on.
 *
 * D-040 makes `UpdateIngestor` the daemon's truth writer and retires the
 * mirror seam from the BUS PATH ONLY — the provider binding stays, because it
 * still serves the backfill and RPC-response paths. So the direct path is now
 * the daemon's production bus path, and this file asserts that as a fact
 * rather than describing it as someone else's lane.
 *
 * The two zero-assertions below survive unchanged and now describe the
 * BACKFILL paths: the mirror seam still writes no update row for a seq-less
 * ctor (it refuses to invent a key), and the `messages.messages` envelope
 * carries no `Vector<Update>` so it owes no row. Neither is the daemon's bus
 * path any more.
 */
final class MirrorAndBackfillAvoidTheUpdateKeyspaceTest extends IngestTestCase
{
    private const ACCOUNT = 4242;

    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    /**
     * TWO DIFFERENT payloads through the mirror seam keep their own rows.
     *
     * The mirror's fact surface is keyed on the entity's own natural key
     * `(account_id, id)`, so two distinct messages are two rows — the mirror
     * never funnels them through a shared `(0, 0)`.
     */
    public function test_two_mirror_facts_keep_their_own_rows(): void
    {
        $mirror = $this->mirrorSeam();

        $mirror($this->message(5, 'MIRROR FIRST'), self::ACCOUNT);
        $mirror($this->message(6, 'MIRROR SECOND'), self::ACCOUNT);

        self::assertSame(2, DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(
            ['MIRROR FIRST', 'MIRROR SECOND'],
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->orderBy('id')->pluck('message')->all(),
            'each mirror fact keeps its own row; neither replaces the other',
        );
    }

    /**
     * RE-INGESTING ONE upserts — row count stable, no duplicate.
     *
     * The mirror half of the "stable identity" requirement, asserted on the
     * keyspace the mirror actually writes.
     */
    public function test_reingesting_one_mirror_fact_upserts(): void
    {
        $mirror = $this->mirrorSeam();

        $mirror($this->message(5, 'MIRROR FIRST'), self::ACCOUNT);
        $mirror($this->message(6, 'MIRROR SECOND'), self::ACCOUNT);
        $mirror($this->message(5, 'MIRROR FIRST'), self::ACCOUNT); // same source fact again

        self::assertSame(
            2,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            're-ingesting the SAME fact must upsert onto its existing row, not append a duplicate',
        );
        self::assertSame(
            ['MIRROR FIRST', 'MIRROR SECOND'],
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->orderBy('id')->pluck('message')->all(),
        );
    }

    /**
     * THE ASSERTION THAT PINS THE FINDING: the mirror seam writes NO
     * `tf_updates` row for a seq-less update ctor — so it cannot be on the
     * `(0, 0)` key and cannot purge a predecessor's children.
     *
     * `tf_updates` IS in the mirror catalog, keyed `["seq","position"]`, so this
     * is a routing fact rather than a table-absence accident: the row is
     * refused because a seq-less payload cannot cover its own primary key.
     */
    public function test_the_mirror_seam_writes_no_update_row_for_a_seq_less_update_ctor(): void
    {
        $mirror = $this->mirrorSeam();

        $mirror(['_' => 'updateNewMessage', 'pts' => 8001, 'pts_count' => 1, 'qts' => 8002], self::ACCOUNT);
        $mirror(['_' => 'updateNewMessage', 'pts' => 9001, 'pts_count' => 1, 'qts' => 9002], self::ACCOUNT);

        self::assertSame(
            0,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'the mirror seam must never write the seq-less update keyspace — that is why it cannot collide',
        );
        self::assertSame(0, DB::table('tf_updates_pts')->count());

        $details = DB::table('tf_ingest_clues')->pluck('detail')->all();
        self::assertNotEmpty(
            array_values(array_filter(
                $details,
                static fn (string $d): bool => str_contains($d, "keying column 'seq' missing from payload"),
            )),
            'and the refusal is RECORDED rather than silent — the row is skipped for want of a key, not dropped',
        );
    }

    /**
     * Same shape for the provider's batch ingester: two batches both survive.
     */
    public function test_two_provider_batches_keep_their_own_rows(): void
    {
        $batch = $this->providerBatchIngest();

        $batch([$this->message(11, 'BATCH ONE A')]);
        $batch([$this->message(12, 'BATCH ONE B')]);

        self::assertSame(
            ['BATCH ONE A', 'BATCH ONE B'],
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->orderBy('id')->pluck('message')->all(),
        );
    }

    /**
     * ...and re-ingesting one batch upserts, row count stable.
     */
    public function test_reingesting_one_provider_batch_upserts(): void
    {
        $batch = $this->providerBatchIngest();

        $batch([$this->message(11, 'BATCH ONE A')]);
        $batch([$this->message(12, 'BATCH ONE B')]);
        $batch([$this->message(11, 'BATCH ONE A')]);

        self::assertSame(
            2,
            DB::table('tf_messages')->where('account_id', self::ACCOUNT)->count(),
            'a re-ingested batch member must upsert, not duplicate',
        );
    }

    /**
     * THE ASSERTION THAT PINS THE SECOND FINDING: the `messages.messages`
     * envelope routes to `writeEnvelope()` and writes no update row either.
     */
    public function test_the_provider_batch_ingester_writes_no_update_row(): void
    {
        $batch = $this->providerBatchIngest();

        $batch([$this->message(11, 'BATCH ONE A')]);
        $batch([$this->message(12, 'BATCH ONE B')]);

        self::assertSame(
            0,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'the messages.messages envelope carries no Vector<Update>, so it owes no tf_updates row',
        );
        self::assertSame(0, DB::table('tf_updates_pts')->count());
    }

    /**
     * NEGATIVE CONTROL — the destruction this file's zeros are measured against.
     *
     * This control USED to be built on the direct frameless path: two DIFFERENT
     * seq-less updates through `Teleclient::ingest()` landed on the one
     * `(0, 0)` key, and the second purged the first's children. That was true,
     * and it is exactly why the file's zeros could not be vacuous. It is no
     * longer true — the direct path now derives the key from the payload's own
     * content, so two different frameless updates are two rows and neither
     * purge touches the other.
     *
     * THE CONTROL IS REBUILT, NOT DELETED. Its purpose is unchanged and is the
     * valuable part: prove that in THIS database, on THESE tables, with THIS
     * counting code, a `tf_updates` row really is written and a child purge
     * really does fire — so that a fixture which could not fail does not print
     * the same zeros. A key collision is still reachable and is still the
     * honest thing to build the control on, via a container whose two members
     * share one delivering seq and one slot: that is a REAL collision between
     * two different updates, and it purges exactly as the defect did. What
     * changed is only WHICH path produces it, so the control follows the path
     * that still does.
     */
    public function test_negative_control_a_shared_key_still_purges_the_prior_children(): void
    {
        $direct = $this->app->make(Teleframe::class);

        // Two DIFFERENT updates forced onto ONE key: same delivering seq, same
        // slot in the vector.
        $direct->ingest(
            ['_' => 'updatesCombined', 'seq' => 55, 'updates' => [$this->update(8001)]],
            self::ACCOUNT,
        );
        $direct->ingest(
            ['_' => 'updatesCombined', 'seq' => 55, 'updates' => [$this->update(9001)]],
            self::ACCOUNT,
        );

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'control: two updates sharing one key collapse onto one row',
        );

        $row = DB::table('tf_updates')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row);
        self::assertSame(55, (int) $row->seq, 'control: the shared delivering seq is the key');
        self::assertSame(0, (int) $row->position);

        self::assertSame(
            [9001],
            DB::table('tf_updates_pts')->where('account_id', self::ACCOUNT)->pluck('pts')->all(),
            'control: the second update purged the first update children — the loss the other '
            .'tests prove is unreachable from the mirror and provider paths',
        );
    }

    /**
     * The control's own corollary: the direct frameless path is now SAFE, and
     * this asserts it so the rebuilt control above cannot quietly become
     * vacuous by the defect returning.
     *
     * Two DIFFERENT frameless updates via `Teleclient::ingest()` are two rows,
     * each keeping its own pts child. Under the constant `(0, 0)` this read ONE
     * row with only the later child — the very loss the control above measures.
     */
    public function test_the_direct_frameless_path_no_longer_collides(): void
    {
        $direct = $this->app->make(Teleframe::class);

        $direct->ingest($this->update(8001), self::ACCOUNT);
        $direct->ingest($this->update(9001), self::ACCOUNT);

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'two different frameless direct updates are two rows — the shared (0, 0) key is gone',
        );
        self::assertSame(
            [8001, 9001],
            DB::table('tf_updates_pts')->where('account_id', self::ACCOUNT)->orderBy('pts')->pluck('pts')->all(),
            'and each keeps its own child: the earlier update is no longer destroyed by the later one',
        );
    }

    /**
     * THE INVERTED ASSERTION — D-041: the daemon's bus path COMMITS THE ROW,
     * and it does so on the PRODUCER side, before the entry exists.
     *
     * Every other assertion in this file counts what is NOT there. This one
     * counts what IS: a daemon-published seq-less `update*` produces EXACTLY
     * ONE `tf_updates` row. That single property is what every later step of
     * S1 depends on — D-039 publishes `(account_id, seq, position)`, so the
     * row has to exist before anything on the bus can name it.
     *
     * EXACTLY one, and the count is load-bearing twice over. Under one, the
     * row D-039 needs does not exist. Over one, the entry was written twice
     * — which is what happens if the mirror seam and `UpdateIngestor` both
     * reach the same tables, because both converge on the same keyspace.
     *
     * ⚠ WHICH SIDE WRITES MOVED, AND THE COUNT NOW MEASURES SOMETHING ELSE.
     *
     * This test used to prove the property by having the SINK publish with no
     * committer (asserting 0 rows as a precondition) and then the CONSUMER
     * commit the row on the UpdateIngestor branch. Under S1 that arrangement
     * is dead: the consumer no longer writes, it resolves a key the producer
     * published, and an entry naming no row is refused rather than ingested —
     * so "sink commits nothing, consumer commits one" became "sink commits
     * nothing, consumer refuses", and the count this file exists to pin could
     * no longer be produced at all.
     *
     * The property is therefore asserted on the PRODUCTION producer call,
     * `RedisStreamSink::handleCommitted()` — the same method
     * `UpdatePollerService::processUpdate()` drives — which commits through
     * `UpdateIngestor` and only then publishes. The count still has to be
     * exactly one, but it now carries a second, stronger obligation: the
     * consumer pass must NOT add a row. Under the old arrangement the same
     * number proved "the consumer wrote"; here it proves "the producer wrote
     * and the consumer wrote nothing", which is the inversion itself.
     *
     * The consumer is built with the EXACT argument list
     * `DaemonCommand::buildBusDrain()` now passes (mirror ingester `null`).
     * It is not a hand-picked consumer: this is the shape the daemon ships,
     * and the wiring itself is pinned in
     * `tests/Bus/BusProductionDrainTest::test_the_daemons_bus_drain_carries_no_mirror_ingester`.
     */
    public function test_a_daemon_published_seq_less_update_writes_exactly_one_update_row(): void
    {
        $redis = new ArrayRedis;

        // Published exactly as the daemon's producer publishes it: commit
        // through UpdateIngestor FIRST, then append the entry naming the key.
        $sink = new RedisStreamSink($redis, self::ACCOUNT);
        $sink->handleCommitted($this->update(8001), (string) self::ACCOUNT, new UpdateIngestor, null);

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'D-041: the row is committed BEFORE the entry is published — the producer is the writer',
        );
        self::assertCount(
            1,
            $redis->streamEntries(StreamSchema::STREAM),
            'and exactly one entry names it',
        );

        $consumer = $this->daemonBusConsumer($redis);
        self::assertTrue($consumer->ensureGroup());

        $result = $consumer->consumeOnce();

        self::assertSame(1, $result['processed'], 'the daemon bus drain resolved the entry');
        self::assertSame(0, $result['forwarded'], 'an unrouted update is hydrated, not forwarded');

        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'D-040/D-041: the consumer READ the committed row rather than writing a second one — '
            .'still exactly one tf_updates row after the drain',
        );
        self::assertSame(
            [8001],
            DB::table('tf_updates_pts')->where('account_id', self::ACCOUNT)->pluck('pts')->all(),
            'and the row carries its children, so it is a real row rather than a bare key',
        );

        // Nothing left pending: the entry cannot be re-delivered, so it cannot
        // commit a second row. This is the half of "exactly one" that a row
        // count alone cannot prove — the count is a snapshot of one pass, and
        // the double-write this path exists to remove would arrive on the
        // NEXT one. (The mirror-count assertion lives in the gate test below;
        // here the seam is not even bound, so a counter would read 0 vacuously.)
        self::assertSame(
            0,
            $redis->xpending(StreamSchema::STREAM, StreamSchema::GROUP)[0] ?? 0,
            'the entry was acked, so no redelivery is pending',
        );

        $again = $consumer->consumeOnce();

        self::assertSame(0, $again['processed'], 'a further drain pass re-delivers nothing');
        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'and the row count is still exactly one after a second pass — hydration reads, it never '
            .'appends',
        );
    }

    /**
     * THE VERSION DISCRIMINATOR — the mirror seam drains a PRE-MIGRATION entry
     * and is structurally unreachable for a v2 one.
     *
     * Retiring the seam from the daemon's bus path must not remove it. Entries
     * published by a PRE-MIGRATION producer are already on `tg:stream:updates`
     * when the new code rolls out; if the branch were deleted outright, those
     * entries lose their drain entirely.
     *
     * Both halves are asserted on ONE consumer with a bound seam, so the only
     * thing that differs between them is the entry's own version:
     *
     *  - a v1 entry — the shipped `{account_id, update, ts}` shape, which
     *    carries NO `v` field at all — drains through the seam exactly as it
     *    always has. Absence is the discriminator: the shape that predates the
     *    marker is what an unmarked entry is.
     *  - a v2 entry is refused the seam and resolves its committed row instead.
     *
     * ⚠ THE SECOND HALF'S MECHANISM CHANGED, AND IT GOT STRONGER, NOT WEAKER.
     * This test used to build its "v2" entry as the v1 payload with a `v`
     * marker bolted on, and the marker alone is what gated the branch. That
     * construction is no longer expressible: `StreamSchema::encode()` REFUSES
     * an entry carrying `update`, so a v2 entry cannot hold a payload at all.
     * A v2 entry here is now `{v, account_id, seq, position, constructor, ts}`
     * naming a row the producer already committed (D-039) — and the seam is
     * refused it for a structural reason rather than a marker: `drainLegacy()`
     * runs only for an entry the v2 codec refused, and this one is readable.
     * So the ungated double-write below can no longer be reached from a v2
     * entry even if the marker check were deleted tomorrow.
     */
    public function test_the_mirror_seam_drains_a_v1_entry_and_is_refused_a_v2_one(): void
    {
        $redis = new ArrayRedis;
        $stored = new \stdClass;
        $stored->mirror = 0;
        $stored->seen = [];

        // v1: no `v` field, the payload inlined — a pre-migration producer's
        // entry, exactly as it sits on the stream today. Built with
        // `json_encode`, because the v2 encoder refuses an `update` key and
        // that refusal is the guarantee under test.
        $redis->xadd(StreamSchema::STREAM, '*', [
            'account_id' => (string) self::ACCOUNT,
            'update' => json_encode([
                'account_id' => self::ACCOUNT,
                'update' => $this->update(8001),
                'ts' => 1_758_000_000,
            ], JSON_THROW_ON_ERROR),
            'ts' => '1758000000',
        ]);

        // v2: THE PRODUCTION ORDER, performed by hand — commit the row, then
        // publish the entry naming it. `UpdateIngestor::ingest()` is the call
        // `RedisStreamSink::commit()` makes, and it is the only thing under
        // test here that writes.
        $root = (new UpdateIngestor)->ingest($this->update(9001), self::ACCOUNT);
        self::assertNotNull($root, 'precondition: the v2 fixture committed a row to name');

        $redis->xadd(StreamSchema::STREAM, '*', StreamSchema::fields([
            'v' => StreamSchema::VERSION,
            'account_id' => self::ACCOUNT,
            'seq' => (int) $root->getAttribute('seq'),
            'position' => (int) $root->getAttribute('position'),
            'constructor' => 'updateNewMessage',
            'ts' => 1_758_000_000,
        ]));

        $consumer = $this->daemonBusConsumer($redis, function (array $update, int $accountId) use ($stored): void {
            $stored->mirror++;
            $stored->seen[] = $update['_'] ?? '';
        });
        $consumer->ensureGroup();

        // ONE drain handles both entries, and the two counters are what
        // separate them: `consumeOnce()` reads three batches, so splitting the
        // pass per entry is not available — and it is not needed. The mirror
        // count and the row count are complementary. An ungated branch runs
        // the seam twice and commits nothing; a gated branch runs it once and
        // commits the v2 entry's row. Neither reading can satisfy both asserts.
        $result = $consumer->consumeOnce();

        self::assertSame(2, $result['processed'], 'both entries were handled');
        self::assertSame(0, $result['forwarded'], 'neither was forwarded — no route matches an update*');

        self::assertSame(
            1,
            $stored->mirror,
            'EXACTLY ONE entry reached the mirror seam: a v1 entry from a pre-migration producer still '
            .'drains through it — retiring the seam from the bus path must not strand the entries '
            .'already on the stream — while a v2 entry is refused it. Two here is the ungated '
            .'double-write: both writers converge on the same tables.',
        );
        self::assertSame(
            1,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT)->count(),
            'and EXACTLY ONE row exists, the one the PRODUCER committed before publishing. Two means '
            .'the seam re-wrote a v2 entry it must never see; zero means the drain was broken.',
        );
        self::assertSame(
            [9001],
            DB::table('tf_updates_pts')->where('account_id', self::ACCOUNT)->pluck('pts')->all(),
            'and it is the v2 entry\'s row — the v1 drain through the seam contributed nothing, '
            .'exactly as the zero-assertion above measures',
        );
    }

    /**
     * The consumer the daemon's bus drain builds.
     *
     * `$mirrorIngester` defaults to null, which is EXACTLY what
     * `DaemonCommand::buildBusDrain()` passes as of D-040 — that is the point
     * of this helper, not an omission. The gate test below passes a counting
     * seam instead, because a consumer with no seam cannot show the version
     * discriminator refusing one. The daemon's real wiring is pinned
     * separately, on the real builder, in
     * `tests/Bus/BusProductionDrainTest::test_the_daemons_bus_drain_carries_no_mirror_ingester`.
     */
    private function daemonBusConsumer(ArrayRedis $redis, ?callable $mirrorIngester = null): IngestConsumer
    {
        return new IngestConsumer(
            $redis,
            null,
            null,
            $mirrorIngester,
        );
    }

    /** The daemon's default mirror-ingester seam, as the bus would resolve it. */
    private function mirrorSeam(): callable
    {
        $seam = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
        self::assertIsCallable($seam);

        return $seam;
    }

    /** The provider's `BackfillCommand::INGESTER_KEY` batch writer, bound for one account. */
    private function providerBatchIngest(): callable
    {
        $factory = $this->app->make(BackfillCommand::INGESTER_KEY);
        self::assertIsCallable($factory);

        $batch = $factory(self::ACCOUNT);
        self::assertIsCallable($batch);

        return $batch;
    }

    /**
     * @return array<string, mixed>
     */
    private function message(int $id, string $text): array
    {
        return [
            '_' => 'message',
            'id' => $id,
            'peer_id' => ['_type' => 2, '_id' => 900],
            'date' => 1726000000 + $id,
            'message' => $text,
            'out' => false,
        ];
    }

    /**
     * A seq-less update carrying children, so the control can show a purge.
     *
     * @return array<string, mixed>
     */
    private function update(int $pts): array
    {
        return ['_' => 'updateNewMessage', 'pts' => $pts, 'pts_count' => 1, 'qts' => $pts + 1];
    }
}
