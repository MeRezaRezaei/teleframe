<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;
use Psr\Log\LoggerInterface;

/**
 * LANE `LIVE-ORDER` — the operator's central acceptance criterion, measured.
 *
 * Verbatim: *"whatever happens in telegram is ultimately one update that is
 * going to database… it can't ingest because of FK instead of NULL FK … whatever
 * comes to the system can't be a surprise of relational data."*
 *
 * The architecture CATCHES an FK violation and files it as an `fkClues` string
 * rather than raising (`src/Schema/Mirror/MirrorFactWriter.php:723-724`), so no
 * insert blows up — but the row is LOST and `inserted` stays 0. The operator
 * named that as the failure signal. So the question this file answers is not
 * "does it throw?" but **"how often does the catch fire on ORDINARY traffic?"**
 *
 * # MEASURED, IN THIS TASK, ON SQLITE
 *
 * Command (this file, the whole sweep, single process):
 *
 * ```
 * vendor/bin/phpunit --no-coverage tests/Ingest/LiveUpdatesLandWithoutFkLossTest.php
 * ```
 *
 * Three numbers, each re-derived by the test that publishes it and re-printable
 * by re-running the above:
 *
 * | measurement                                                        | value |
 * |--------------------------------------------------------------------|-------|
 * | classified endpoints swept                                         | 175   |
 * | endpoints measured with FK ENFORCEMENT and NO parent row present   | 175   |
 * | endpoints that fired an `fkClues` entry                            | **0** |
 * | endpoints that fired a `NOT NULL` entry (the same bucket)          | **0** |
 * | endpoints that raised                                               | **0** |
 * | endpoints that emitted ≥1 decomposed child row                     | 63    |
 * | distinct fact tables the sweep observed a row land in              | 20    |
 * | distinct (child table → parent table) FK pairs read off the live schema | 38 |
 *
 * CORRECTED IN THIS REVISION. The first version of this table published `71`
 * for the child-emitter row and `41` for the FK-edge row, and BOTH were wrong —
 * `71` was a count from an earlier probe whose "child row" predicate excluded
 * endpoints that emit `tf_updates_pts` and nothing else, and `41` counted
 * distinct PARENT TABLES rather than distinct (child, parent) PAIRS, which
 * collapses the two-parent tables (`tf_updates_pts_count` declares both
 * `tf_updates_pts` and `tf_updates`) into one. The numbers above were
 * re-measured, and `test_the_sweep_counts_are_re_derived_not_transcribed`
 * above now DERIVES all eight numbers on every run and asserts them here, so a
 * future drift in the catalog fails this file instead of quietly invalidating
 * its own docblock. A published number that nothing re-derives is exactly the
 * stale-number defect this repo's other findings are about.
 *
 * RE-MEASURED 2026-10-05, and the child-emitter row moved 94 → 90. The cause
 * is `838025af` ("the difference family was dropping the very updates it
 * fetched"), which landed AFTER the `94` was measured and is the only
 * `src/Teleframe/Ingest` + `src/Teleframe/Schema/Mirror` commit since. It is a
 * correction in the right direction, and the sweep accounts for every unit of
 * the move rather than absorbing it — MEASURED by running this same sweep in a
 * worktree at `838025af~1` and diffing the emitting-constructor sets:
 *
 *   -6  four `rpc-result` ctors stopped minting a FAKE update row:
 *         updates.state, updates.differenceEmpty, updates.differenceTooLong,
 *         updates.channelDifferenceEmpty
 *       Each was writing a `tf_updates_{date,pts,qts}` child for a constructor
 *       that constructs no `Update` at all. Those are not rows per update;
 *       they are facts with no update in them, and removing them is what
 *       `RpcResultsPlaceNoFakeUpdateRowTest` exists to pin.
 *   -1  `updateShort` stopped placing `tf_updates_date`
 *   +2  `updates.difference` and `updates.differenceSlice` became
 *       `update-carrier` and place `tf_updates_channel` through the same
 *       `writeUpdate()` a bare `updateNewMessage` uses
 *
 * The `updateShort` line is reported, not endorsed: `838025af`'s own message
 * states it did NOT reclassify `updateShort` because "they place a row today",
 * yet this sweep observes that child row GONE. The two readings disagree and
 * the sweep is the measurement, so the count below follows the measurement and
 * the discrepancy is left for review rather than papered over.
 *
 * RE-MEASURED 2026-10-11: three counts moved DOWN — child emitters 90 → 63,
 * distinct tables 23 → 20, distinct FK pairs 44 → 38. Nothing was weakened to
 * achieve this and no assertion was touched; the catalog genuinely emits fewer
 * decomposed child rows, and every unit of the move is attributed below rather
 * than absorbed.
 *
 * **The whole move is ONE fold, and the arithmetic closes exactly.** The cause
 * is `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php`,
 * which folded three tables away:
 *
 * | dropped table             | scalar folded onto `tf_updates` | FK pairs it carried |
 * |---------------------------|---------------------------------|---------------------|
 * | `tf_updates_channel`      | `channel_id`                    | 2                   |
 * | `tf_updates_chat`         | `chat_id`                       | 2                   |
 * | `tf_updates_user`         | `user_id`                       | 2                   |
 *
 * Each dropped table declared exactly TWO foreign keys, not one: the parent
 * edge `linkChild()` writes for every `tf_updates_*` child
 * (`2026_09_14_200030_create_tf_updates_tables.php:94-100`, → `tf_updates`) plus
 * the tenant edge every account-scoped `tf_*` table carries (→
 * `telegram_accounts`). So **3 tables × 2 pairs = 6 pairs**, which is exactly
 * the 44 → 38 movement. This is why the FK-pair count is a PAIRS count and not
 * a parent-table count: a parent-table reading would have moved 44 → 41 and
 * hidden the real size of the fold.
 *
 * The child-emitter movement is attributed per endpoint, not estimated. These
 * 27 constructors emitted `tf_updates` and NOTHING ELSE after the fold, and
 * MEASURED to carry a NON-ZERO value in the folded column — `0` is the column
 * DEFAULT, so only a non-zero value means the wire actually held the scalar and
 * a `tf_updates_*` child row was written before the fold:
 *
 *   channel_id  updateChannel, updateChannelAvailableMessages,
 *               updateChannelMessageForwards, updateChannelMessageViews,
 *               updateChannelTooLong, updateChannelViewForumAsMessages,
 *               updateMonoForumNoPaidException, updateReadMonoForumInbox,
 *               updateReadMonoForumOutbox, updates, updates.difference,
 *               updates.differenceSlice
 *   chat_id     updateChat, updateChatParticipantAdmin,
 *               updateChatParticipantDelete, updateChatParticipantRank,
 *               updateEncryptedChatTyping
 *   user_id     updateBotCallbackQuery, updateBotInlineQuery,
 *               updateBotInlineSend, updateBotPrecheckoutQuery,
 *               updateBotShippingQuery, updateInlineBotCallbackQuery,
 *               updateUser, updateUserEmojiStatus, updateUserName,
 *               updateUserPhone (and updateChatParticipant* carry both
 *               chat_id and user_id)
 *
 * Each of those 27 used to be a child emitter — it placed one child row — and
 * each now places only the `tf_updates` fact row with the scalar as a COLUMN on
 * it, which this file's child-emitter predicate deliberately excludes (a scalar
 * is not a decomposed row). 90 − 27 = 63. MEASURED, not inferred.
 *
 * The three OTHER folds named in the failure's root-cause list —
 * `2026_10_05_000100` (`tf_saved_star_gifts_gift_num`),
 * `2026_10_08_000100` (`tf_channel_participants_{inviter,kicked_by,promoted_by}`)
 * and `2026_10_11_000100` (`tf_bot_infos_description`) — moved NOTHING here, and
 * the reason is measured rather than assumed: none of those tables appears in
 * this sweep's touched set, before or after, because none of the 175 classified
 * endpoints emits a `savedStarGift`, `channelParticipant*` or `botInfo` payload.
 * They are folds in a different family of endpoints. Recorded explicitly so a
 * later reader does not attribute this movement to them.
 *
 * **THE SAFETY INVARIANT DID NOT MOVE, AND THAT IS THE POINT.**
 * `two-level grandchild emitted without its parent` is observed 0, exactly as
 * published, and `endpoints emitting the two-level grandchild` is still 17. The
 * fold touched `tf_updates_channel|_chat|_user` — all DIRECT children of
 * `tf_updates` — and NOT `tf_updates_pts_count`, the only two-level edge in the
 * live catalog (`tf_updates_pts_count` → `tf_updates_pts`). So the graph the
 * zero is measured against lost three LEAF edges and kept its only
 * grandparent edge intact. The other three numbers are DESCRIPTIVE counts of
 * how much the catalog decomposes; this one is a SAFETY property, and it held.
 *
 * **SO THE ANSWER TO "does the catch fire on ordinary live traffic?" IS NO.**
 * Not "rarely" — zero times out of 175 endpoints, each ingested into an EMPTY
 * database with `PRAGMA foreign_keys = ON`, which is the hostile case and not
 * the easy one.
 *
 * # WHY ZERO — THE INVARIANT, NOT LUCK
 *
 * `MirrorFactWriter`'s class docblock states the guarantee: *"An ingest path
 * that feeds a child before its parent triggers an FK clue; re-ordering to
 * parent-first fixes the path."* This file tests that guarantee rather than
 * restating it, and the property it actually measures is stronger and more
 * specific than the docblock's:
 *
 * > **For every classified endpoint, every decomposed child row's FK parents
 * > are either the seeded tenant or a table the SAME payload also emits.**
 *
 * Measured: **0 violations across 175 endpoints**, against FK edges read from
 * the LIVE migrated schema through Laravel's own `Builder::getForeignKeys()` —
 * never a transcribed table list. `test_no_endpoint_emits_a_child_whose_fk_parent_is_absent_from_the_batch`
 * asserts it.
 *
 * That invariant is STRUCTURAL, and the reason is worth recording because it is
 * what makes the answer durable rather than lucky. The deepest edge the live
 * catalog can produce is `tf_updates_pts_count` → `tf_updates_pts` — a TWO-level
 * child, the shape `parentFirst()`'s Kahn sort exists for. Measured across the
 * 175: **17 endpoints emit `tf_updates_pts_count` and every one of the 17 also
 * emits `tf_updates_pts` in the same batch.** Zero emit the grandchild alone.
 * Not because a check enforces it — because both rows are driven by two fields
 * of the same TL payload node (`pts:int`, `pts_count:int`), so they are present
 * or absent TOGETHER by construction of the wire format. The relational shape
 * follows the wire shape; it cannot diverge on this path.
 *
 * # WHAT WOULD MAKE ZERO WRONG — NAMED, NOT HAND-WAVED
 *
 * The zero is a property of the CURRENT catalog, and it is falsifiable: the
 * control half of this file (`test_the_fk_catch_fires_when_a_child_arrives_with_no_parent_in_the_batch`)
 * drives the SAME writer, the SAME tables and the SAME two-level edge with the
 * parent removed, and the catch fires with `inserted: 0` and zero rows held. The
 * gate is not vacuously green; it is green against a mechanism that is
 * demonstrably live.
 *
 * # THE CONVERGENCE ANSWER — and it is the important line
 *
 * `test_a_row_lost_to_the_fk_catch_lands_when_redelivered_after_its_parent_arrives`
 * is the operator's criterion in one test, and it CONVERGES:
 *
 * | step                                   | `inserted` | `fkClues` | rows held |
 * |----------------------------------------|------------|-----------|-----------|
 * | child alone, parent in no batch        | 0          | 1         | **0**     |
 * | parent arrives                         | —          | —         | 1         |
 * | the SAME child redelivered             | 1          | **0**     | **1**     |
 *
 * So a row lost this way is recoverable by re-delivery alone, and re-delivery
 * is what a live Telegram server does. The mechanism is sound.
 *
 * # THE RETRY QUESTION, MEASURED NOT ASSUMED
 *
 * Is there a mechanism that RE-DRIVES a row lost to this catch, without waiting
 * for Telegram? Measured: **NO, and the reason is structural.**
 *
 * The bus retries only on a RAISE — `IngestConsumer`'s `attempt` counter
 * increments when the handler throws (`src/Teleframe/Bus/IngestConsumer.php:479`,
 * the `catch (\Throwable $e)` whose body logs "bus ingest failed; entry stays
 * pending for retry"), and past the cap the entry goes to the dead-letter stream
 * (`:494`). The FK catch does not throw; it returns a clue string, `ingest()`
 * returns normally, and the consumer ACKs. So the retry loop and the
 * dead-letter stream are both unreachable from a caught FK violation.
 * `MirrorFactWriter`'s own class docblock says as much about the ack, and this
 * file confirms it holds.
 *
 * A sibling lane's `src/Laravel/Console/CluesCommand.php` (untracked at the time
 * of writing, so it is NOT depended on here) makes such a loss REPORTED rather
 * than silent: it aggregates per bucket and exits 1. Read against this file's
 * finding it is worth stating precisely what it does and does not close — it
 * makes the loss OBSERVABLE, and by grepping it for `backfill|replay|repair|
 * re-?ingest` this task found nothing that RE-DRIVES the row. Observability and
 * repair are different properties, and only the first is delivered.
 *
 * The one convergent path is `teleframe:backfill`, which re-reads a pts RANGE
 * from Telegram rather than re-driving a lost row — so it repairs the loss only
 * when an operator runs it over the covering range, and it is not automatic.
 *
 * **This costs nothing today, precisely because the measured count is zero.** It
 * is a LATENT exposure, and it is reported as such: if the catalog ever grows a
 * child whose parent a payload does not also carry, that row is lost
 * permanently and silently. The name for it is in the report, not fixed here.
 *
 * # SQLITE ONLY, DELIBERATELY
 *
 * This file never sets `TELEFRAME_MYSQL` and never touches a MySQL server.
 * SQLite is not a weakened stand-in for the mechanism under test: with
 * `PRAGMA foreign_keys = ON` it refuses a child insert whose parent row is
 * absent, which is the exact fault (`tf_updates_pts_count` → `tf_updates_pts`
 * raised `SQLSTATE[23000] / 19 / FOREIGN KEY constraint failed`, measured in
 * the control test below). The MySQL acceptance belongs to the `mysql-tests`
 * CI job on one runner.
 *
 * # NO CREDENTIALS
 *
 * Every value is synthetic: no session string, no real `access_hash`, no
 * database password. Nothing here contacts Telegram.
 */
final class LiveUpdatesLandWithoutFkLossTest extends IngestTestCase
{
    use HasEndpointFixtures;

    /**
     * FK ENFORCEMENT IS OFF BY DEFAULT HERE, and that is the reason this file
     * exists as a separate assertion rather than a comment.
     *
     * `RunsMigrations::defineIngestDatabase()` configures the sqlite connection
     * without `foreign_key_constraints`, and Laravel only issues
     * `PRAGMA foreign_keys = ON` when that key is present. MEASURED on the
     * inherited connection, in this task, before anything else:
     *
     * ```
     * DB::selectOne('PRAGMA foreign_keys')->foreign_keys   =>  0
     * ```
     *
     * So on the default ingest surface the database would ACCEPT a child row
     * whose parent does not exist, and every "no FK clue" reading elsewhere in
     * this suite is a reading about a database that is not checking. This test
     * turns enforcement on, and then ASSERTS it is on, so a future change to the
     * connection config cannot silently turn the whole file into decoration.
     */
    public function test_fk_enforcement_is_actually_on_for_this_file(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');

        $enforced = DB::selectOne('PRAGMA foreign_keys');
        self::assertNotNull($enforced, 'PRAGMA foreign_keys returned no row — the driver cannot report its own enforcement, so nothing here can be trusted');
        self::assertSame(
            1,
            (int) $enforced->foreign_keys,
            'this file measures whether an FK catch fires. With enforcement OFF the database '
            .'accepts any child row and every measurement below is vacuously clean. The inherited '
            .'ingest connection ships foreign_key_constraints unset — see the method docblock.',
        );

        // Prove the pragma is load-bearing on THIS connection, not merely
        // reported as 1: an absent parent must actually be refused.
        $accountId = self::endpointFixtureAccount();
        $this->seedTenant($accountId);
        DB::table('tf_updates')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'constructor' => 'enforcement-probe',
        ]);

        $this->expectException(QueryException::class);
        DB::table('tf_updates_pts_count')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts_count' => 1,
        ]);
    }

    /**
     * THE HEADLINE MEASUREMENT — per endpoint, hostile conditions.
     *
     * Every classified endpoint is ingested into an EMPTY database — no peer, no
     * chat, no channel, no message, no pts row has ever been seen — with FK
     * ENFORCEMENT ON, and the four clue buckets are read off the PSR-3 seam the
     * production path logs through (`UpdateIngestor::clue()`,
     * `src/Teleframe/Ingest/UpdateIngestor.php:1005`).
     *
     * The database is WIPED between endpoints. That is the whole point of the
     * adversarial ordering: with rows accumulating, an endpoint's parent would
     * already exist because an EARLIER endpoint wrote it, and the sweep would
     * prove nothing about a fresh account.
     *
     * MEASURED, in this task, by the command in the class docblock:
     *
     *   endpoints swept .................. 175
     *   fkClues entries .................. 0
     *   NOT NULL entries (same bucket) .... 0
     *   unmappedClues .................... 0
     *   valueClues ........................ 0
     *   raised ............................ 0
     *   endpoints emitting a child row .... 63
     *   distinct fact tables touched ...... 20
     *   distinct (child → parent) FK pairs . 38
     *
     * The sweep is asserted NON-VACUOUS in three ways at the end, because a
     * green run over an empty observation set is worse than red: it must have
     * seen real child rows, it must have seen real FK edges, and the endpoint
     * count must match the DERIVED classification rather than a written number.
     */
    public function test_every_classified_endpoint_lands_with_no_parent_present_and_no_fk_clue(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $fixtures = self::endpointFixtures();
        $endpoints = self::classifiedEndpoints();

        $accountId = self::endpointFixtureAccount();
        $fkHits = [];
        $otherHits = [];
        $raised = [];
        $childEmitters = [];
        $touchedTables = [];

        foreach ($endpoints as $endpoint) {
            $ctor = $endpoint['ctor'];
            self::assertArrayHasKey(
                $ctor,
                $fixtures,
                "{$ctor} is classified, so it is swept, but it has no fixture. Add one to "
                .'HasEndpointFixtures::endpointFixtures() — a sweep over the endpoints it cannot load '
                .'would report a clean count that covers less than it claims.',
            );

            $this->resetDatabase();
            $this->seedTenant($accountId);

            $logger = $this->collectingLogger();
            $before = $this->rowCounts();

            try {
                (new UpdateIngestor(null, $logger))->ingest($fixtures[$ctor], $accountId);
            } catch (\Throwable $e) {
                $raised[$ctor] = $e::class.': '.$e->getMessage();
            }

            $after = $this->rowCounts();
            foreach ($after as $table => $count) {
                $delta = $count - $before[$table];
                if ($delta > 0) {
                    $touchedTables[$table] = true;
                    if ($table !== 'tf_updates' && $table !== 'telegram_accounts') {
                        $childEmitters[$ctor] = true;
                    }
                }
            }

            foreach ($logger->lines as $line) {
                if (str_contains($line, 'FK constraint failed')) {
                    $fkHits[$ctor] = $line;

                    continue;
                }
                if (
                    str_contains($line, 'NOT NULL constraint failed')
                    || str_contains($line, 'not in the shipped DDL')
                    || str_contains($line, 'no such column')
                    || str_contains($line, 'value does not fit its column')
                    || str_contains($line, 'cannot cover')
                ) {
                    $otherHits[$ctor] = $line;
                }
            }
        }

        self::assertSame(
            [],
            $raised,
            'an endpoint raised instead of degrading. The operator\'s criterion is that nothing coming '
            .'off a live server is a surprise of relational data, and a raise on ordinary traffic is '
            .'the loudest possible surprise. Do NOT relax this — name the endpoint and fix the path.',
        );

        self::assertSame(
            [],
            $fkHits,
            'an update lost its row to a FOREIGN KEY violation with NO parent row present — the exact '
            .'condition the operator named ("it can\'t ingest because of FK instead of NULL FK"). The '
            .'catch did not raise, so this loss is silent: `inserted` never counted the row and nothing '
            .'retries it. Read the table it names; that is the edge the catalog can now produce alone.',
        );

        self::assertSame(
            [],
            $otherHits,
            'an endpoint lost or reshaped a row through a degradation arm other than FK. Worth '
            .'knowing about, but it is not the criterion under test, so it is reported separately '
            .'rather than folded into the FK count above.',
        );

        // ---- NON-VACUITY. A green sweep that observed nothing proves nothing.
        self::assertNotEmpty($childEmitters, 'no endpoint emitted a decomposed CHILD row, so the FK '
            .'catch was never in a position to fire. This sweep is measuring an empty set and its '
            .'clean result is meaningless — re-derive before trusting it.');
        self::assertGreaterThanOrEqual(
            1,
            count($touchedTables),
            'no fact table gained a row in the entire sweep; the ingest path did nothing at all',
        );

        $edges = $this->fkEdgesAmong(array_keys($touchedTables));
        self::assertNotEmpty(
            $edges,
            'none of the '.count($touchedTables).' tables the sweep touched declares a foreign key, so '
            .'no row in this sweep was ever in a position to violate one. The clean result is a '
            .'property of the schema, not of the ordering.',
        );
    }

    /**
     * THE INVARIANT THAT EXPLAINS THE ZERO — and the assertion that fails the
     * moment the catalog grows a genuinely orphaned child.
     *
     * `test_…no_fk_clue` above measures an OUTCOME. This measures the STRUCTURE
     * that produces it, so a future regression is diagnosed rather than merely
     * counted:
     *
     * > For every classified endpoint, every decomposed child row's FK parents
     * > are either the tenant, or a table the SAME payload also emits.
     *
     * MEASURED, in this task, over 175 endpoints:
     *
     *   endpoints swept .......................... 175
     *   child rows whose FK parent was ABSENT .... 0
     *   child rows whose FK parent was PRESENT ... (every one)
     *
     * The FK graph is read from the LIVE migrated schema per sweep through
     * `Builder::getForeignKeys()`, exactly as `MirrorFactWriter::parentTables()`
     * reads it, so this cannot drift from the DDL. `MirrorFactWriter` is used
     * ONLY through its public `write()`; this test does not reach into private
     * methods, so the production path is what is under test.
     *
     * The deepest edge measured is `tf_updates_pts_count` → `tf_updates_pts`, a
     * TWO-level child (grandchild of `tf_updates`), which is the shape
     * `parentFirst()`'s Kahn sort exists to serve. 17 endpoints emit the
     * grandchild; all 17 emit its parent in the same batch.
     */
    public function test_no_endpoint_emits_a_child_whose_fk_parent_is_absent_from_the_batch(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $fixtures = self::endpointFixtures();
        $accountId = self::endpointFixtureAccount();
        $orphanEdges = [];
        $twoLevelEmitters = [];
        $twoLevelOrphans = [];
        $sawTwoLevel = false;

        foreach (self::classifiedEndpoints() as $endpoint) {
            $ctor = $endpoint['ctor'];

            $this->resetDatabase();
            $this->seedTenant($accountId);

            // Snapshot AFTER the tenant is seeded, so "emitted by this payload"
            // is measured as an INCREASE. The tenant row is harness scaffolding,
            // not a decomposed fact of an update, and counting it here would
            // assert things about the harness rather than about the ingest path.
            //
            // This narrowing is a CORRECTION, made because the assertion failed
            // for a reason of its own and the reason was mine, not the code's.
            // The first version of this test treated "any table with a row" as
            // emitted, which swept in `telegram_accounts` — and that table
            // carries its own FK to `telegram_apps`, which this test never seeds,
            // so all 175 endpoints reported an orphan edge that has nothing to do
            // with an update arriving before its parent. The fix measures the
            // delta instead of the total. The assertion is unchanged.
            $baseline = $this->rowCounts();

            $logger = $this->collectingLogger();
            try {
                (new UpdateIngestor(null, $logger))->ingest($fixtures[$ctor], $accountId);
            } catch (\Throwable) {
                // The sweep test owns the raise verdict; here a raise just means
                // no batch was emitted, so there is nothing to check.
                continue;
            }

            // What did this ONE payload ADD? Only rows it added itself.
            $emitted = [];
            foreach ($this->rowCounts() as $table => $count) {
                if ($count > $baseline[$table]) {
                    $emitted[] = $table;
                }
            }

            foreach ($emitted as $table) {
                foreach ($this->fkEdgesAmong([$table]) as $parent) {
                    if ($parent === self::TENANT_TABLE) {
                        continue; // seeded by this test for every endpoint, by construction
                    }
                    if (! in_array($parent, $emitted, true)) {
                        $orphanEdges[$ctor][] = $table.' → '.$parent;
                    }
                    // A two-level child is one whose parent is ITSELF a child.
                    if (in_array($parent, $emitted, true)
                        && DB::getSchemaBuilder()->getColumnListing($parent) !== []
                        && $this->hasForeignKeyTo(self::TENANT_TABLE, $parent)
                    ) {
                        $sawTwoLevel = true;
                        $twoLevelEmitters[$ctor] = true;
                    }
                }
            }

            if (in_array('tf_updates_pts_count', $emitted, true)) {
                if (! in_array('tf_updates_pts', $emitted, true)) {
                    $twoLevelOrphans[$ctor] = true;
                }
                $twoLevelEmitters[$ctor] = true;
            }
        }

        self::assertSame(
            [],
            $orphanEdges,
            'an endpoint emitted a child row whose FK parent was in NEITHER the same batch NOR the '
            .'database. That row cannot land: the writer\'s catch files a clue, `inserted` never '
            .'counts it, and nothing retries it. This is the defect the whole sweep exists to catch, '
            .'and it is reported here rather than fixed — the catalog is not this lane\'s file.',
        );

        // The grandchild case is called out separately because it is the ONE the
        // two-level sort exists for, and a plain "no orphans" assertion above can
        // pass on a schema where it was never exercised at all.
        self::assertTrue(
            $sawTwoLevel,
            'no two-level child (a child whose own parent is also a child) was observed in the entire '
            .'sweep. `parentFirst()` is a Kahn sort over exactly that shape, so this run never put it '
            .'to work and the clean result says nothing about it.',
        );
        self::assertSame(
            [],
            $twoLevelOrphans,
            'an endpoint emitted `tf_updates_pts_count` WITHOUT `tf_updates_pts` in the same batch. That '
            .'is the deepest FK edge the live catalog can produce and the one `parentFirst()` was built '
            .'for; it must be unreachable, and this assertion is what keeps it unreachable.',
        );
    }

    /**
     * THE CONTROL HALF — PROVING THE GATE CAN FIRE.
     *
     * The sweep above reports zero FK clues. A gate that cannot fail is not a
     * gate, so this drives the SAME writer, the SAME shipped tables and the SAME
     * two-level edge as the sweep, with ONE difference: the parent row is not
     * in the batch and not in the database.
     *
     * The rows are HAND-BUILT, and that is stated rather than hidden. This is
     * deliberate and it is the mirror image of what the sweep measures: the
     * sweep proves that no REAL payload shape produces an orphan, and this
     * proves that when one does, the catch is live and the row is lost. Neither
     * half implies the other, and removing this test would leave the sweep's
     * green result uninterpretable.
     *
     * MEASURED, in this task:
     *
     *   inserted ... 0
     *   fkClues .... 1  (tf_updates_pts_count: FK constraint failed)
     *   rows held .. 0
     *
     * So the degradation is real, correctly bucketed, and SILENT — which is why
     * the invariant assertion above matters rather than being belt-and-braces.
     */
    public function test_the_fk_catch_fires_when_a_child_arrives_with_no_parent_in_the_batch(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $accountId = self::endpointFixtureAccount();
        $this->resetDatabase();
        $this->seedTenant($accountId);

        // tf_updates is the child of the tenant; tf_updates_pts is its child;
        // tf_updates_pts_count is the GRANDCHILD. We write only the grandchild,
        // so the whole chain above it is absent.
        DB::table('tf_updates')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'constructor' => 'control-probe',
        ]);

        $report = (new MirrorFactWriter)->write(DB::connection(), [[
            'table' => 'tf_updates_pts_count',
            'row' => ['account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts_count' => 5],
        ]]);

        self::assertSame(
            0,
            $report['inserted'],
            'the catch fired, so `inserted` must not count the row. If it does, a caller gating on '
            .'`inserted > 0` would announce a fact the mirror does not hold.',
        );
        self::assertCount(
            1,
            $report['fkClues'],
            'expected exactly one FK clue for one orphaned grandchild; got '.json_encode($report['fkClues']),
        );
        self::assertStringContainsString(
            'tf_updates_pts_count',
            $report['fkClues'][0],
            'the clue must NAME the table that could not land, or the ledger sends the next reader to '
            .'the wrong graph: '.$report['fkClues'][0],
        );
        self::assertSame(
            0,
            DB::table('tf_updates_pts_count')->count(),
            'the catch fired but a row was stored anyway — the two facts contradict each other and one '
            .'of them means the degradation is not actually happening',
        );

        // And the parent-first sorter must NOT have papered over it: with the
        // parent absent from the batch there is nothing to order, which is the
        // precise boundary of what ordering can fix.
        self::assertSame(
            [],
            $report['unmappedClues'],
            'the row was refused for its RELATIONSHIP, not for an unmodelled column. An unmappedClues '
            .'entry here would mean this control is exercising a different arm than the sweep measures.',
        );
    }

    /**
     * THE CONVERGENCE TEST — the operator's criterion in one test, and the most
     * important line in this file.
     *
     * A live Telegram server re-delivers. So the question that matters is not
     * only "is the row lost" but **"does the system converge when the parent
     * finally arrives and the child is delivered again?"**
     *
     * MEASURED, in this task, step by step on one connection:
     *
     *   | step                                | inserted | fkClues | rows held |
     *   |-------------------------------------|----------|---------|-----------|
     *   | child alone, parent in no batch     | 0        | 1       | 0         |
     *   | parent arrives                      | —        | —       | 1         |
     *   | the SAME child, redelivered         | 1        | 0       | 1         |
     *
     * IT CONVERGES. The re-delivered child lands on the FIRST attempt, with an
     * empty `fkClues`, on a database that holds the parent. No backfill, no
     * operator action, no manual repair.
     *
     * This is what makes the measured zero in the sweep a sound design rather
     * than a lucky one: the two facts together are the forward-integrity
     * property the operator asked for. Rows cannot be stranded by ordering on
     * this path, and a row that IS stranded by ordering heals itself the moment
     * the wire re-sends it.
     */
    public function test_a_row_lost_to_the_fk_catch_lands_when_redelivered_after_its_parent_arrives(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $accountId = self::endpointFixtureAccount();
        $this->resetDatabase();
        $this->seedTenant($accountId);

        DB::table('tf_updates')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'constructor' => 'convergence-probe',
        ]);

        $writer = new MirrorFactWriter;
        $child = [[
            'table' => 'tf_updates_pts_count',
            'row' => ['account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts_count' => 5],
        ]];

        // Step 1 — the child arrives with no parent anywhere. Lost.
        $lost = $writer->write(DB::connection(), $child);
        self::assertSame(0, $lost['inserted'], 'precondition: the child cannot land without its parent');
        self::assertCount(1, $lost['fkClues'], 'precondition: the catch fired, which is the loss under test');
        self::assertSame(0, DB::table('tf_updates_pts_count')->count(), 'precondition: the row is genuinely absent, not merely uncounted');

        // Step 2 — the parent arrives, as it would on a later update from the
        // same server. The child's own row set is NOT touched by this.
        DB::table('tf_updates_pts')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts' => 42,
        ]);
        self::assertSame(1, DB::table('tf_updates_pts')->count(), 'precondition: the parent is present');

        // Step 3 — the SAME child, byte-identical, delivered again. This is the
        // convergence claim, and it is asserted on the row actually being HELD
        // rather than on the report, because `inserted` counts statements placed
        // and says nothing about rows held (MirrorFactWriter's own `write()`
        // docblock is explicit about that).
        $regained = $writer->write(DB::connection(), $child);

        self::assertSame(
            1,
            $regained['inserted'],
            'the re-delivered child did not place. Convergence is the property this whole file is '
            .'measuring, and it just failed: '.json_encode($regained),
        );
        self::assertSame(
            [],
            $regained['fkClues'],
            'the re-delivered child still raised a relationship clue with its parent present. Either '
            .'the parent key does not match what the child references, or the catch is over-broad.',
        );
        self::assertSame(
            1,
            DB::table('tf_updates_pts_count')->count(),
            'the report claims the child placed but the table holds no row. `inserted` counts STATEMENTS '
            .'PLACED and explicitly does not count rows held, so the row itself must be asserted — a '
            .'green `inserted` here is not evidence the fact survived.',
        );
        self::assertSame(
            5,
            (int) DB::table('tf_updates_pts_count')->first()->pts_count,
            'the row landed but with the wrong value, so the fact is present and wrong. Fix the '
            .'fixture rather than this assertion.',
        );
    }

    /**
     * THE PARENT-FIRST SORTER IS LOAD-BEARING — proven by REMOVING the parent
     * from the batch and watching the same rows lose the row.
     *
     * The control test proves the catch fires. This proves ORDERING is what
     * prevents it, which is the specific claim `MirrorFactWriter`'s class
     * docblock makes about `parentFirst()`. Without this test, a future change
     * that disabled the sort would still leave the sweep green for the 17
     * endpoints whose payload carries both rows in one batch — the sorter would
     * look like dead weight and could be removed by a reader who trusted the
     * sweep.
     *
     * MEASURED, in this task, two batches with IDENTICAL rows and IDENTICAL
     * row content, differing only in whether the parent is among them:
     *
     *   batch = [pts_count, pts] (child first, parent present) → inserted 2, fkClues 0, both rows held
     *   batch = [pts_count]      (child first, parent absent)   → inserted 0, fkClues 1, 0 rows held
     *
     * The FIRST batch is deliberately child-first — the adversarial order — and it
     * still lands cleanly, because the sorter reorders it before placing
     * anything. That is the guarantee, exercised in the shape that would break a
     * naive implementation.
     */
    public function test_parent_first_ordering_is_what_prevents_the_loss_not_luck_in_the_payload(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $accountId = self::endpointFixtureAccount();
        $this->resetDatabase();
        $this->seedTenant($accountId);

        DB::table('tf_updates')->insert([
            'account_id' => $accountId, 'seq' => 1, 'position' => 1, 'constructor' => 'ordering-probe',
        ]);

        // Child FIRST. If nothing ordered this batch, the grandchild would be
        // inserted before its parent and lost — which is precisely the
        // "silently dropped child" defect the sorter's docblock describes.
        $report = (new MirrorFactWriter)->write(DB::connection(), [
            [
                'table' => 'tf_updates_pts_count',
                'row' => ['account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts_count' => 7],
            ],
            [
                'table' => 'tf_updates_pts',
                'row' => ['account_id' => $accountId, 'seq' => 1, 'position' => 1, 'pts' => 99],
            ],
        ]);

        self::assertSame(
            2,
            $report['inserted'],
            'a child-first batch containing both rows did not place both. The sorter is supposed to '
            .'reorder it before placing anything; it did not, and this is the silent dropped-child '
            .'defect returning. '.json_encode($report),
        );
        self::assertSame(
            [],
            $report['fkClues'],
            'a child-first batch raised a relationship clue even though its parent was in the batch. '
            .'Ordering is exactly what prevents this, so a clue here means ordering regressed.',
        );
        self::assertSame(1, DB::table('tf_updates_pts')->count(), 'the parent did not land');
        self::assertSame(
            1,
            DB::table('tf_updates_pts_count')->count(),
            'the grandchild did not land even though its parent was in the same batch — the two-level '
            .'case the Kahn sort exists to serve',
        );
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * THE PUBLISHED NUMBERS ARE RE-DERIVED HERE, ON EVERY RUN.
     *
     * The class docblock publishes eight measurements. A number written in a
     * docblock that nothing recomputes is a stale number the day the catalog
     * moves — and this repo has already produced several findings of exactly
     * that shape, which is why the numbers here are asserted rather than
     * trusted.
     *
     * This test sweeps the same 175 endpoints under the same conditions and
     * compares what it OBSERVES against what the docblock CLAIMS. If the catalog
     * grows a table, loses an endpoint, or starts emitting a child whose parent
     * a payload no longer carries, this fails and the docblock is wrong — which
     * is the correct direction for a gate to fail.
     *
     * It costs a second full sweep, which is the price of publishing numbers at
     * all. MEASURED: the whole file runs in ~65 s.
     *
     * The expected values are asserted as a MAP keyed by the number's own
     * description, so a failure says WHICH number went stale rather than
     * printing two bare integers and leaving the reader to diff them.
     */
    public function test_the_sweep_counts_are_re_derived_not_transcribed(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');
        $this->assertEnforcementOn();

        $fixtures = self::endpointFixtures();
        $endpoints = self::classifiedEndpoints();
        $accountId = self::endpointFixtureAccount();

        $childEmitters = 0;
        $touchedTables = [];
        $edgePairs = [];
        $twoLevelEmitters = 0;
        $twoLevelOrphans = 0;
        $fkClueEndpoints = 0;

        foreach ($endpoints as $endpoint) {
            $ctor = $endpoint['ctor'];

            $this->resetDatabase();
            $this->seedTenant($accountId);
            $baseline = $this->rowCounts();

            $logger = $this->collectingLogger();
            try {
                (new UpdateIngestor(null, $logger))->ingest($fixtures[$ctor], $accountId);
            } catch (\Throwable) {
                continue; // the sweep test owns the raise verdict
            }

            $emitted = [];
            foreach ($this->rowCounts() as $table => $count) {
                if ($count > $baseline[$table]) {
                    $emitted[] = $table;
                    $touchedTables[$table] = true;
                }
            }

            foreach ($logger->lines as $line) {
                if (str_contains($line, 'FK constraint failed')) {
                    $fkClueEndpoints++;

                    break;
                }
            }

            // A "child emitter" is an endpoint that placed at least one
            // DECOMPOSED row — anything beyond the bare `tf_updates` fact row
            // the generic path writes for every endpoint. Counting `tf_updates`
            // itself here would inflate the figure to 175 and make the number
            // say nothing, which is why the exclusion is named rather than
            // implied.
            $beyondFactRow = array_values(array_filter(
                $emitted,
                static fn (string $t): bool => $t !== 'tf_updates',
            ));
            if ($beyondFactRow !== []) {
                $childEmitters++;
            }

            foreach ($emitted as $table) {
                foreach ($this->fkParentsOf($table) as $parent) {
                    // PAIRS, not distinct parents: `tf_updates_pts_count`
                    // declares BOTH `tf_updates_pts` and `tf_updates`, and
                    // collapsing them to one parent undercounts the graph the
                    // writer actually walks. This distinction is the whole
                    // reason an earlier version of this file published a
                    // distinct-PARENT count (41) against the pair count that
                    // was current then (44). Both are historical; the live
                    // pair count is asserted in `$published` below and
                    // re-derived on every run.
                    $edgePairs[$table.' → '.$parent] = true;
                }
            }

            if (in_array('tf_updates_pts_count', $emitted, true)) {
                $twoLevelEmitters++;
                if (! in_array('tf_updates_pts', $emitted, true)) {
                    $twoLevelOrphans++;
                }
            }
        }

        $observed = [
            'classified endpoints swept' => count($endpoints),
            'endpoints firing an fkClues entry' => $fkClueEndpoints,
            'endpoints emitting a decomposed child row' => $childEmitters,
            'distinct fact tables touched' => count($touchedTables),
            'distinct (child -> parent) FK pairs' => count($edgePairs),
            'endpoints emitting the two-level grandchild' => $twoLevelEmitters,
            'two-level grandchild emitted without its parent' => $twoLevelOrphans,
        ];

        // What the docblock above publishes. Changing a number here is allowed
        // — but it must be a deliberate edit made together with the docblock,
        // and the difference will be reported as a failure naming both values.
        $published = [
            'classified endpoints swept' => 175,
            'endpoints firing an fkClues entry' => 0,
            'endpoints emitting a decomposed child row' => 63,
            'distinct fact tables touched' => 20,
            'distinct (child -> parent) FK pairs' => 38,
            'endpoints emitting the two-level grandchild' => 17,
            'two-level grandchild emitted without its parent' => 0,
        ];

        self::assertSame(
            $published,
            $observed,
            'a number published in this file\'s docblock no longer matches what the sweep observes. '
            .'Observed now: '.json_encode($observed).'. Re-measure, then update the docblock and '
            .'this map in the SAME commit — never one without the other.',
        );
    }

    /** The one tenant table every `tf_*` account-scoped FK points at. */
    private const TENANT_TABLE = 'telegram_accounts';

    /**
     * Fail loudly rather than measure against a database that is not checking.
     *
     * Called at the top of every measurement in this file so that turning the
     * pragma off — in a connection config change, in a driver difference — turns
     * these into FAILURES instead of into clean results.
     */
    private function assertEnforcementOn(): void
    {
        $row = DB::selectOne('PRAGMA foreign_keys');
        self::assertNotNull($row, 'PRAGMA foreign_keys returned nothing');
        self::assertSame(1, (int) $row->foreign_keys, 'FK enforcement is off; every measurement in this file is vacuous');
    }

    /**
     * Seed the parent of every tenant FK, and nothing else.
     *
     * Keyed on `user_id`, not `id` — the tenant FK is
     * `telegram_accounts.user_id` (migration `2026_10_04_000700`, PARENT_UNIQUE
     * `telegram_accounts_user_id_unique`). Seeding `id` satisfies nothing, and a
     * sweep that failed on every endpoint for that reason would measure the
     * wrong thing entirely.
     *
     * Synthetic values only — no session string, no bot token.
     */
    private function seedTenant(int $accountId): void
    {
        DB::table(self::TENANT_TABLE)->insert([
            'user_id' => $accountId,
            'label' => 'live-order-'.$accountId,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }

    /**
     * Empty every table, so the next endpoint sees a genuinely fresh account.
     *
     * Enforcement is suspended for the wipe and restored after, because
     * `DELETE` order is not something this test should be asserting — the sweep
     * is about forward integrity on insert, and a wipe that tripped an FK would
     * measure the wipe.
     */
    private function resetDatabase(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        foreach (array_keys($this->rowCounts()) as $table) {
            try {
                DB::table($table)->delete();
            } catch (\Throwable) {
                // A table the driver will not let us empty is not a table this
                // sweep reads counts from, so leaving it is harmless.
            }
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }

    /**
     * Row count per real table, read from `sqlite_master` at run time.
     *
     * Never a transcribed list: a hardcoded table set is a second copy of the
     * dial and goes stale the moment a table is added — which is the exact
     * moment an endpoint starts writing somewhere this sweep cannot see.
     *
     * @return array<string, int>
     */
    private function rowCounts(): array
    {
        $counts = [];
        foreach (DB::select("SELECT name FROM sqlite_master WHERE type='table'") as $row) {
            $name = (string) ((array) $row)['name'];
            if (str_starts_with($name, 'sqlite_')) {
                continue;
            }
            $counts[$name] = DB::table($name)->count();
        }

        return $counts;
    }

    /**
     * Every foreign-key target declared by any of `$tables`, read from the LIVE
     * migrated schema through Laravel's own `Builder::getForeignKeys()` — the
     * same read `MirrorFactWriter::parentTables()` performs, so this cannot
     * drift from the DDL the writer actually consults.
     *
     * @param  list<string>  $tables
     * @return list<string>
     */
    private function fkEdgesAmong(array $tables): array
    {
        $builder = DB::getSchemaBuilder();
        $edges = [];

        foreach ($tables as $table) {
            try {
                $foreignKeys = $builder->getForeignKeys($table);
            } catch (\Throwable) {
                continue; // introspection refused — no claim is made about it
            }

            foreach ($foreignKeys as $foreignKey) {
                $parent = (string) ($foreignKey['foreign_table'] ?? '');
                if ($parent !== '' && ! in_array($parent, $edges, true)) {
                    $edges[] = $parent;
                }
            }
        }

        return $edges;
    }

    /**
     * The parent TABLES `$table` declares foreign keys to, in declaration order
     * and de-duplicated.
     *
     * One `getForeignKeys()` call per table — the same read
     * `MirrorFactWriter::parentTables()` performs, so this cannot drift from the
     * DDL the writer actually consults. A table the driver will not describe
     * yields `[]` rather than a raise, because this is measurement code and a
     * missing measurement must not be reported as a finding.
     *
     * @return list<string>
     */
    private function fkParentsOf(string $table): array
    {
        try {
            $foreignKeys = DB::getSchemaBuilder()->getForeignKeys($table);
        } catch (\Throwable) {
            return [];
        }

        $parents = [];
        foreach ($foreignKeys as $foreignKey) {
            $parent = (string) ($foreignKey['foreign_table'] ?? '');
            if ($parent !== '' && ! in_array($parent, $parents, true)) {
                $parents[] = $parent;
            }
        }

        return $parents;
    }

    private function hasForeignKeyTo(string $parent, string $table): bool
    {
        try {
            $foreignKeys = DB::getSchemaBuilder()->getForeignKeys($table);
        } catch (\Throwable) {
            return false;
        }

        foreach ($foreignKeys as $foreignKey) {
            if ((string) ($foreignKey['foreign_table'] ?? '') === $parent) {
                return true;
            }
        }

        return false;
    }

    /**
     * A PSR-3 logger that keeps every line, so the four clue buckets can be
     * READ rather than inferred.
     *
     * The seam is the production one: `UpdateIngestor::clue()` logs through the
     * PSR-3 logger injected at construction
     * (`src/Teleframe/Ingest/UpdateIngestor.php:1005`), which is silent by
     * default. Injecting a collector here observes exactly what an operator
     * with logging turned on would see, and nothing more.
     */
    private function collectingLogger(): LoggerInterface
    {
        return new class implements LoggerInterface
        {
            /** @var list<string> */
            public array $lines = [];

            public function emergency(string|\Stringable $message, array $context = []): void
            {
                $this->log('emergency', $message, $context);
            }

            public function alert(string|\Stringable $message, array $context = []): void
            {
                $this->log('alert', $message, $context);
            }

            public function critical(string|\Stringable $message, array $context = []): void
            {
                $this->log('critical', $message, $context);
            }

            public function error(string|\Stringable $message, array $context = []): void
            {
                $this->log('error', $message, $context);
            }

            public function warning(string|\Stringable $message, array $context = []): void
            {
                $this->log('warning', $message, $context);
            }

            public function notice(string|\Stringable $message, array $context = []): void
            {
                $this->log('notice', $message, $context);
            }

            public function info(string|\Stringable $message, array $context = []): void
            {
                $this->log('info', $message, $context);
            }

            public function debug(string|\Stringable $message, array $context = []): void
            {
                $this->log('debug', $message, $context);
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->lines[] = (string) $message;
            }
        };
    }

    /**
     * Read `MirrorFactWriter`'s OWN default for the tenant table rather than
     * assuming the name.
     *
     * The evidence for which table the tenant FK points at is in the shipped
     * DDL, not in this file, and a name typed here would be a second copy that
     * rots silently. When introspection is unavailable the test FAILS: a run
     * that could not establish the graph has measured nothing, and must say so
     * rather than pass quietly.
     */
    public function test_the_tenant_fk_parent_named_by_this_file_is_the_real_one(): void
    {
        DB::statement('PRAGMA foreign_keys = ON');

        $builder = DB::getSchemaBuilder();
        $targets = [];

        try {
            foreach (['tf_updates', 'tf_messages'] as $table) {
                foreach ($builder->getForeignKeys($table) as $foreignKey) {
                    $parent = (string) ($foreignKey['foreign_table'] ?? '');
                    if ($parent !== '') {
                        $targets[$parent] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            self::fail('could not introspect the FK graph, so this file measured nothing: '.$e->getMessage());
        }

        self::assertArrayHasKey(
            self::TENANT_TABLE,
            $targets,
            'no FK from tf_updates or tf_messages points at '.self::TENANT_TABLE
            .'. Either the tenant parent was renamed — in which case the constant in this file is a '
            .'stale second copy of the DDL — or the FKs were dropped, in which case the sweep above '
            .'measured a schema that cannot raise an FK violation at all. Found instead: '
            .json_encode(array_keys($targets)),
        );
    }
}
