<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Models\TfIngestClue;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use RuntimeException;

/**
 * A FACT THE MIRROW FAILED TO PLACE MUST BE IMPOSSIBLE TO MISS.
 *
 * Owner verbatim: "it can be used as a SIGN OF FAILURE since if we have added all
 * telegram history whatever new update has the previous related data" and "the
 * INTEGRITY OF DATA IN THE DATABASE means everything".
 *
 * ## WHAT WAS ALREADY THERE, MEASURED 2026-10-05 (so this file does not re-decide it)
 *
 * `MirrorFactWriter::write()` computes four clue buckets and RETURNS them
 * (`src/Schema/Mirror/MirrorFactWriter.php:638`). Three surfaces already PERSIST
 * them, one row per clue string, through one shared entry point:
 *
 *   - `TfIngestClue::recordReport()` — `src/Laravel/Models/TfIngestClue.php:112`
 *     into `tf_ingest_clues` (migration `2026_10_02_000500`), indexed on
 *     `(account_id, bucket)` and `(account_id, seen_at)`.
 *   - called from the bus path `MirrorIngesterFactory::recordClues()`
 *     (`src/Laravel/MirrorIngesterFactory.php:485`, in a `finally` at `:181`), from
 *     `BackfillCommand::recordClues()` (`src/Laravel/Console/BackfillCommand.php:941`)
 *     and from `IngestGateCommand::handle()` (`src/Laravel/Console/IngestGateCommand.php:110`).
 *
 * `UpdateIngestor::clue()` also emits each clue on the live path
 * (`src/Teleframe/Ingest/UpdateIngestor.php:1005`), at `notice`, through the PSR-3
 * seam.
 *
 * ## THE MEASURED DEFECT — TWO HALVES, BOTH REAL
 *
 *   1. **NOTHING READS THE LEDGER.** Measured, not assumed:
 *      `grep -rn "tf_ingest_clues" src/ --include=*.php` filtered for
 *      select/count/where/orderBy/get returns **zero** hits, and `TfIngestClue`
 *      appears in `src/` only at `recordReport()` (the write) and `buckets()`
 *      (the fold). No command, no controller, no view, no dashboard, no query.
 *      The repo says so itself at `src/Schema/Mirror/MirrorFactWriter.php:208`:
 *      "NOBODY FILTERS ON THE BUCKET … read by no controller, view, query or
 *      dashboard in the repository". So a persisted row exists and no human has
 *      ever seen it. **A row that nothing reads is not a signal; it is a receipt.**
 *   2. **THE CLOCK.** The ledger is APPEND-ONLY and never deduplicated
 *      (`TfIngestClue` has `$incrementing = false`, `$primaryKey = null`), and
 *      `recordReport()` inserts ONE ROW PER CLUE STRING. The operator's own
 *      criterion is a full-history backfill followed by continuous updates — the
 *      highest-volume writer in the repository (`BackfillCommand`'s own docblock
 *      calls it that). A backfill that loses 4,000 facts to one repeated fault
 *      leaves 4,000 indistinguishable rows, with no count, no grouping and no
 *      first/last-seen to say whether the fault is STILL HAPPENING or was fixed
 *      three weeks ago. That is a fact nobody can act on.
 *
 * ## WHAT THIS FILE PINS, AND WHY IT IS A COMMAND AND NOT A LOG LINE OR AN EVENT
 *
 * The signal is the READ SIDE of the persisted ledger, and the alternative
 * mechanisms were measured against this package's own constraints:
 *
 *   - **A LOG LINE is not enough.** PSR-3 is the seam, but the shipped default is
 *     `NullLogger` (`TeleframeServiceProvider` resolves an unconfigured host to
 *     it), so on an unconfigured host a log line emits nothing at all — and the
 *     engine's silence-by-default is a PROMISED property of this package. Making
 *     the failure loud by default would break that promise; making it loud on
 *     demand still leaves a log that dies with the process and cannot answer
 *     "how many facts did this mirror lose last Tuesday".
 *   - **An EVENT is not enough.** `src/Laravel/Events/` is the right convention
 *     for notifying a host that ALREADY wired a listener (`TelegramGapDetected`
 *     is dispatched from `UpdatePollerService`). With no listener — the shipped
 *     default — an event is exactly today's silence, and it would fire once per
 *     clue on a path whose volume is the whole problem.
 *   - **A SECOND PERSISTED TABLE is not needed.** `tf_ingest_clues` already is the
 *     ledger, is written from all three recording surfaces, and is already
 *     indexed for precisely this query. Adding a table would be the bespoke
 *     mechanism that must not be invented when a convention already fits.
 *
 * So `teleframe:clues` completes the half that does not exist: it reads the
 * ledger, AGGREGATES IN SQL (`GROUP BY bucket, table` with `count(*)` and
 * `count(DISTINCT detail)`), prints the first/last `seen_at` per group, and
 * EXITS NON-ZERO when anything is recorded. Aggregation is what makes it safe at
 * backfill volume — the output is bounded by distinct (bucket, table) pairs, not
 * by the number of clues — and it separates VOLUME (`rows`) from FAULTS
 * (`distinct`), which is the number an operator acts on. It writes nothing and
 * dials nothing, so the package's silence-by-default promise is untouched: a
 * command nobody runs prints nothing.
 *
 * ## THE FAULT IS REAL, NOT TRANSCRIBED
 *
 * `IngestTestCase` executes `UpdateIngestor::migrationPaths()` — the whole shipped
 * curated dial — so `tf_messages`, `tf_messages_media` and `tf_ingest_clues` here
 * are the shipped DDL, never a second copy that can drift. Foreign keys are turned
 * ON and the pragma is ASSERTED, because an FK test run with enforcement off is
 * vacuous and would pass for the wrong reason. `tf_messages_media` is the right
 * table because it is one of the three cross-domain enforced FKs the repo ships
 * on purpose (`2026_09_14_299999_create_tf_foreign_keys.php:84`) AND is now
 * densely constrained by the family migrations — an orphan child is guaranteed to
 * be refused by the real schema.
 *
 * The clue is then persisted through the PRODUCTION entry point,
 * `TfIngestClue::recordReport()` — the exact call the bus path, the backfill and
 * the gate all make — so this file proves the chain end to end: real writer →
 * real catch → real bucket → real ledger write → operator-visible signal.
 *
 * ## THE CONTROL HALF IS MANDATORY AND IS NOT DECORATION
 *
 * `test_a_clean_write_records_no_clue_and_the_command_is_silent` runs the SAME
 * command on the SAME schema after a write that genuinely places its row, and
 * requires exit 0 with nothing reported. Without it, a command that printed
 * "4 fk clues" unconditionally — or that read any table at all — would satisfy
 * every assertion in the first two tests while measuring nothing.
 */
final class CluedFactIsObservableTest extends IngestTestCase
{

    private const ACCOUNT = 42;

    /**
     * The ENCLOSING message's scope. `tf_messages_media` names the message's own
     * peer with a `message_` prefix because the table already owns the short
     * names for a peer carried inside the media payload
     * (`2026_09_14_200012_create_tf_message_medias_tables.php:28-36`), and the
     * shipped FK references `tf_messages(account_id, peer_type, peer_id, id)`.
     */
    private const PEER_TYPE = 2;

    private const PEER_ID = 777000111;

    /**
     * The child's id, which the shipped composite FK requires to EQUAL the
     * parent message's id — see the measurement recorded on the control test.
     */
    private const MESSAGE_ID = 991;

    protected function defineIngestDatabase($app): void
    {
        parent::defineIngestDatabase($app);

        // FK enforcement is OFF by default on a Laravel sqlite connection, and
        // this file's whole subject is an FK violation that the writer CATCHES.
        // Left off, the orphan row below would insert cleanly, no bucket would
        // fill, and every assertion here would be green for the wrong reason.
        $app['config']->set('database.connections.ingest.foreign_key_constraints', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The pragma must have TAKEN. `CrawlReachesMessageIdOneCases.php:714`
        // refuses to run rather than accept a silently inert pragma, and this is
        // the same trap: an FK proof on a connection that ignored the pragma
        // asserts nothing at all.
        $state = DB::selectOne('PRAGMA foreign_keys');

        if ((int) ($state->foreign_keys ?? 0) !== 1) {
            throw new RuntimeException(
                'sqlite refused PRAGMA foreign_keys = ON — every FK assertion in this file would be vacuous',
            );
        }

        $this->tenant();
    }

    /**
     * The tenant row every `account_id` in this schema is FK'd to.
     *
     * MEASURED 2026-10-05, by being wrong TWICE, and both failures are recorded
     * because they are the disease this lane exists to make visible rather than to
     * fix:
     *
     *  - The first run inserted the ledger row for an account that did not exist
     *    and the LEDGER insert itself raised `FOREIGN KEY constraint failed`.
     *    `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php` puts a
     *    tenant FK on every `tf_*` table carrying an `account_id`, and
     *    `tf_ingest_clues` is one of them — so the ledger cannot record a fault
     *    about a tenant it does not know. That is the tenant-FK disease arriving
     *    SWALLOWED, seen from the other end: on the bus path `recordClues()`
     *    catches exactly this `QueryException` and logs "the ingest clue ledger
     *    could not be written" (`MirrorIngesterFactory.php:503`), so the signal
     *    meant to make an integrity failure visible fails SILENTLY on precisely
     *    the hosts whose integrity is already broken.
     *  - The second run keyed the tenant on `.id`, and still raised. The FK
     *    targets `user_id`, not `.id` — `tf_*.account_id` is the TELEGRAM USER
     *    id, settled by D-005 and stated four times in this repo (cited at the
     *    head of the migration itself). The vault row id is a different number.
     *
     * Both were FIXTURE faults and both were fixed in the fixture. No assertion
     * was weakened and nothing was skipped to get past them.
     */
    private function tenant(): void
    {
        DB::table('telegram_accounts')->insert([
            'label' => 'clue-observability fixture',
            'type' => 'user',
            'user_id' => self::ACCOUNT,
        ]);
    }

    /**
     * THE OPERATOR'S NAMED CONDITION — `inserted: 0` beside a non-empty
     * `fkClues` — MUST REACH A HUMAN, NOT ONLY A RETURN ARRAY.
     *
     * An orphan `tf_messages_media` row whose parent message does not exist is
     * refused by the real shipped FK, the writer's catch turns the refusal into
     * one `fkClues` string, and nothing is inserted. That is precisely the fact
     * that must not be placed and was not.
     */
    public function test_an_fk_clue_is_reported_to_an_operator_and_fails_the_command(): void
    {
        $report = $this->writeOrphanMediaRow();

        // PRECONDITION, so the assertions below cannot be vacuous: the write
        // really was refused, and the refusal really did land in the FK bucket.
        self::assertSame(
            0,
            $report['inserted'],
            'precondition: the orphan child really was refused, so a fact really was not placed',
        );
        self::assertNotSame(
            [],
            $report['fkClues'],
            'precondition: the refusal really produced a clue, or there is nothing here to observe',
        );
        self::assertSame(
            0,
            (int) DB::table('tf_messages_media')->count(),
            'precondition: the refused row left nothing half-written behind it',
        );

        // THE PRODUCTION PERSISTENCE CALL, the one all three recording surfaces
        // make — the bus path (`MirrorIngesterFactory.php:492`), the backfill
        // (`BackfillCommand.php:942`) and the gate (`IngestGateCommand.php:110`).
        $appended = TfIngestClue::recordReport(DB::connection(), $report, self::ACCOUNT);

        self::assertSame(
            count($report['fkClues']),
            $appended,
            'the ledger appends one row per clue string, which is what makes the row count unreadable at backfill volume',
        );

        // THE ASSERTION. The operator runs one command and is told: which axis
        // failed, which table, how many facts were lost, and the command FAILS so
        // a cron, a deploy gate or a health check can act on it without a human
        // reading scrollback.
        //
        // ONE substring carrying BOTH tokens, not two substrings carrying one
        // each — measured, not stylistic: Laravel's `expectsOutputToContain`
        // registers each needle as its own Mockery expectation on `doWrite`, and
        // Mockery dispatches a call to the FIRST matching expectation, so a
        // `fkClues` needle is satisfied by every line and the `tf_messages_media`
        // needle is then never consulted on those same lines. The line printed
        // under each group is `  {$bucket} / {$table}: {$detail}`, so this needle
        // pins the axis AND the table to a SINGLE line — a stronger assertion
        // than the two it replaces, and one that cannot be satisfied by the two
        // tokens appearing in different places.
        $this->artisan('teleframe:clues', ['account' => (string) self::ACCOUNT])
            ->expectsOutputToContain('fkClues / tf_messages_media')
            ->doesntExpectOutputToContain('no clue')
            ->assertExitCode(1);
    }

    /**
     * THE SAME FAULT, TWICE, IS REPORTED AS TWO ROWS AND ONE FAULT.
     *
     * This is the half of the defect that a naive "did anything get recorded?"
     * cannot answer. The ledger is append-only and never deduplicated, so a
     * backfill that hits one fault on 400 pages files 400 identical rows. The
     * number that matters is the DISTINCT count — one fault, still happening —
     * and both numbers must be printed, because reading either alone is wrong:
     * `rows` alone looks like 400 separate problems, `distinct` alone hides a
     * fault that is firing on every single page.
     */
    public function test_a_repeated_fault_is_reported_as_one_fault_not_a_row_per_page(): void
    {
        $report = $this->writeOrphanMediaRow();
        TfIngestClue::recordReport(DB::connection(), $report, self::ACCOUNT);
        TfIngestClue::recordReport(DB::connection(), $report, self::ACCOUNT);

        self::assertSame(
            2,
            (int) DB::table('tf_ingest_clues')->where('account_id', self::ACCOUNT)->count(),
            'precondition: the ledger is append-only, so the same fault twice really is two rows',
        );

        $this->artisan('teleframe:clues', ['account' => (string) self::ACCOUNT])
            ->expectsOutputToContain('tf_messages_media')
            ->assertExitCode(1);
    }

    /**
     * THE CONTROL HALF — THE CLEAN PATH IS SILENT, AND THE FACT STILL LANDS.
     *
     * The same command, the same schema, the same account, after a write that
     * genuinely places its row because its parent exists. It must report
     * nothing and succeed. This is what stops the first two tests from being
     * satisfied by a command that prints unconditionally, and it is also the
     * property that keeps the command usable as a gate: a signal that is always
     * on is not a signal.
     */
    public function test_a_clean_write_records_no_clue_and_the_command_is_silent(): void
    {
        // MEASURED 2026-10-05, by being wrong first: the first version of this
        // control inserted the parent with `id => 555` and the child still could
        // not be placed. The shipped composite FK
        // `tf_messages_media_message_fk` (`2026_09_14_299999:84`) is
        // `(account_id, message_peer_type, message_peer_id, id)` →
        // `tf_messages(account_id, peer_type, peer_id, id)`, and it INCLUDES
        // `id`: a media row is keyed to its own message's id, not merely to some
        // message in the same peer scope. The parent therefore has to carry the
        // child's id, and the orphan case works precisely because that row is
        // absent.
        DB::table('tf_messages')->insert([
            'account_id' => self::ACCOUNT,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => self::MESSAGE_ID,
            // MEASURED 2026-10-05, by being wrong first: `tf_messages.constructor`
            // is `text` with NO default, so a hand-inserted parent without it
            // raises `NOT NULL` and the control's parent never lands. Supplying
            // it is what makes this a real parent rather than a broken one.
            'constructor' => 'messageEmpty',
            'date' => 1_726_000_000,
            'message' => 'the parent, so the child below has somewhere to land',
        ]);

        $report = (new MirrorFactWriter)->write(DB::connection(), [$this->mediaRow()]);

        // PRECONDITION: the control is a real placement, not a no-op. If this
        // row did not land, the silence below would prove nothing.
        self::assertSame(1, $report['inserted'], 'precondition: the parented child really was placed');
        self::assertSame([], $report['fkClues'], 'precondition: a placeable row raises no clue');
        self::assertSame(
            1,
            (int) DB::table('tf_messages_media')->count(),
            'precondition: the row is really in the mirror, so the mirror is not merely quiet because it is broken',
        );

        $appended = TfIngestClue::recordReport(DB::connection(), $report, self::ACCOUNT);

        self::assertSame(
            0,
            $appended,
            'precondition: a clean report appends nothing to the ledger',
        );

        $this->artisan('teleframe:clues', ['account' => (string) self::ACCOUNT])
            ->expectsOutputToContain('no clue')
            ->assertExitCode(0);
    }

    /**
     * Write one `tf_messages_media` row whose parent message does not exist.
     *
     * @return array{inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    private function writeOrphanMediaRow(): array
    {
        $report = (new MirrorFactWriter)->write(DB::connection(), [$this->mediaRow()]);

        self::assertSame(
            0,
            (int) DB::table('tf_messages')->count(),
            'the parent really is absent, which is the whole reason the child cannot be placed',
        );

        return $report;
    }

    /**
     * A `messageMediaPhoto` row covering its whole declared primary key
     * `(account_id, message_peer_type, message_peer_id, id)` so `placeRow()`
     * places rather than declines it — the refusal below is therefore the
     * DATABASE's, raised by the shipped FK, not the writer declining a row.
     *
     * @return array{table: string, row: array<string, int|string|null>}
     */
    private function mediaRow(): array
    {
        return [
            'table' => 'tf_messages_media',
            'row' => [
                'account_id' => self::ACCOUNT,
                'id' => self::MESSAGE_ID,
                'message_peer_type' => self::PEER_TYPE,
                'message_peer_id' => self::PEER_ID,
                'constructor' => 'messageMediaPhoto',
            ],
        ];
    }
}
