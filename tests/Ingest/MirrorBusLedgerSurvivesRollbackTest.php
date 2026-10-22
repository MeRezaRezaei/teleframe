<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use Psr\Log\AbstractLogger;
use RuntimeException;
use Stringable;

/**
 * THE BUS PATH'S LEDGER MUST SURVIVE THE ROLLBACK IT EXISTS TO EXPLAIN.
 *
 * ## THE DEFECT THIS EXISTS FOR (fix round 1/5 finding B2)
 *
 * Commit 714bfff1 wrapped the whole bus entry in `$db->transaction()` and called
 * `recordClues()` INSIDE that closure. The argument for it was sound and the
 * conclusion was not: a clue is raised BY the write, so any fault that raises out
 * of `write()` — a serialization fault, a dead connection, a disk error on the
 * transaction's own COMMIT — rolls the transaction back and destroys the clue
 * that would have explained the rollback, in the same event.
 *
 * That is the ledger being useless exactly when it is needed. A clue filed
 * beside `inserted > 0` is a per-row degradation the operator could have read
 * off the run; a clue filed beside a rolled-back entry is the ONLY surviving
 * account of why the entry is missing. 714bfff1's arrangement could only ever
 * produce the first kind.
 *
 * `IngestGateCommand` has always recorded its ledger post-commit, so the two
 * surfaces disagreed about the property the migration's own docblock promises.
 * This file pins the bus path to the gate's behaviour, so the two cannot drift
 * apart again.
 *
 * ## HOW THE ROLLBACK IS PRODUCED — A REAL ONE, NOT A SIMULATED ONE
 *
 * `Connection::beforeExecuting()` raises at the connection boundary, the same
 * boundary `MysqlFaultInjectingConnection` intercepts at and the same one a
 * driver-level fault surfaces from. The insert into `tf_messages` is therefore
 * genuinely attempted by the real writer against the real shipped DDL and
 * genuinely fails, the transaction genuinely rolls back, and the test asserts
 * the rows are gone — so "the clue survived the rollback" is checked against a
 * rollback that actually happened rather than a flag pretending one did.
 *
 * The clue itself comes from the NESTED-source pass, which runs BEFORE the
 * top-level write inside the same transaction: a `media` child whose constructor
 * the catalog does not know produces "cannot place the fact" and no row. That
 * ordering is what makes this file a test of the accumulation and not of luck —
 * the clue exists before the raise, so recording it afterwards is a decision
 * rather than a side effect.
 *
 * ## THE SCHEMA IS THE SHIPPED ONE
 *
 * `IngestTestCase` executes `UpdateIngestor::migrationPaths()`, so
 * `tf_ingest_clues` and `tf_messages` here are the shipped DDL.
 */
final class MirrorBusLedgerSurvivesRollbackTest extends IngestTestCase
{
    private const ACCOUNT = 42;

    private const RAISE_MARKER = 'simulated write fault (rollback under test)';

    /**
     * Tenant 42 (`ACCOUNT`) — the only one.
     *
     * Every arm in this file drives one ingest under `self::ACCOUNT` and then
     * asserts against `tf_ingest_clues` / `tf_messages` scoped by that same
     * id. That id is the caller's TELEGRAM user id, not a vault row id —
     * `MirrorIngesterFactory.php:160` writes it straight into
     * `tf_*.account_id`, and the FK on those tables targets
     * `telegram_accounts.user_id`. This matters more here than elsewhere: the
     * rollback arms raise at the connection boundary and assert the transaction
     * genuinely rolled back, and a foreign-key refusal at the tenant column
     * would be a DIFFERENT raise from the one under test, silently standing in
     * for it. So the parent has to be real before the fault is planted.
     *
     * Production always plants the parent first:
     * `VaultAddAccountCommand.php:80` writes `user_id` at account-add time.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [42];
    }

    /**
     * The CHILD table whose inserts the value-fault case faults: `from_rank` is
     * a direct child of `tf_messages`, declared `$table->text('from_rank')` with
     * NO default and NO nullable
     * (src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:141-146).
     * A row that covers the whole `(account_id, id)` key but omits it therefore
     * reaches the INSERT and is refused — the operator's live 1364.
     */
    private const CHILD_FAULT_TABLE = 'tf_messages_from_rank';

    /** MySQL ER_BAD_NULL_ERROR — "Field 'x' doesn't have a default value". */
    private const CHILD_DRIVER_CODE = 1364;

    private const CHILD_DRIVER_MESSAGE = "Field 'from_rank' doesn't have a default value";

    /**
     * The clue accumulated before the raise survives the raise, the rollback and
     * the re-raise out of the seam.
     */
    public function test_a_clue_accumulated_before_a_raise_is_still_recorded_after_the_rollback(): void
    {
        $db = DB::connection();
        $db->beforeExecuting(static function (string $query): void {
            if (str_starts_with(strtolower($query), 'insert into "tf_messages"')) {
                throw new RuntimeException(self::RAISE_MARKER);
            }
        });

        $seam = MirrorIngesterFactory::build();

        // THE RAISE MUST STILL PROPAGATE. Recording the ledger in a `finally` is
        // only correct if `finally` does not also swallow: a bus consumer that
        // saw a silent return here would believe a half-written mirror committed.
        $raised = null;

        try {
            $seam($this->payload(), self::ACCOUNT);
        } catch (\Throwable $e) {
            $raised = $e;
        }

        self::assertInstanceOf(
            RuntimeException::class,
            $raised,
            'the write fault must still propagate out of the bus seam — the ledger write is bookkeeping, not a new failure policy',
        );
        self::assertSame(self::RAISE_MARKER, $raised->getMessage());

        // PRECONDITION: the rollback was REAL. Without this the assertions below
        // would pass on a run that committed, and the file would be proving
        // nothing at all.
        self::assertSame(
            0,
            (int) DB::table('tf_messages')->count(),
            'precondition: the transaction really rolled back, so the clue below really did outlive it',
        );

        // THE ASSERTION. One row, in the bucket that says what it is, naming the
        // table it is about.
        $rows = DB::table('tf_ingest_clues')->where('account_id', self::ACCOUNT)->get();

        self::assertCount(
            1,
            $rows,
            'the clue accumulated before the raise must be recorded AFTER the transaction rolled back — '
            .'inside the transaction it died with the rows it was explaining',
        );
        self::assertSame(
            'clues',
            $rows[0]->bucket,
            'a constructor the catalog does not know is a coverage clue, not an FK or a value fault',
        );
        self::assertSame(
            'tf_message_medias',
            $rows[0]->table,
            'the ledger row must name the table the clue is about, parsed off its "{$table}: …" prefix',
        );
        self::assertStringContainsString(
            "constructor 'messageMediaBogus' is not in catalog ctors",
            $rows[0]->detail,
            'the persisted detail is the clue STRING, not a paraphrase of it',
        );
        self::assertNotNull(
            $rows[0]->seen_at,
            'a clue that outlived its transaction must still carry the time it was seen, or the resync window cannot use it',
        );
    }

    /**
     * THE CLEAN PATH RECORDS NOTHING, and does not lose anything either.
     *
     * The `beforeExecuting` hook is scoped to the failing test's connection
     * object, so this test runs on an untouched connection and proves the ledger
     * write is not unconditional: a fully placeable entry with no faults must
     * leave the ledger empty and the mirror populated. Without this control, a
     * `recordClues()` that filed a row for every bus entry would satisfy the
     * test above perfectly while making the table noise.
     */
    public function test_a_clean_entry_records_no_clue_and_still_stores_the_fact(): void
    {
        $seam = MirrorIngesterFactory::build();

        $seam([
            '_' => 'message',
            'id' => 12,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => 444000555],
            'date' => 2,
            'message' => 'clean',
        ], self::ACCOUNT);

        self::assertSame(
            0,
            (int) DB::table('tf_ingest_clues')->where('account_id', self::ACCOUNT)->count(),
            'a fully placeable entry records no clue, so the ledger stays a list of defects rather than a log',
        );
        self::assertSame(
            1,
            (int) DB::table('tf_messages')->count(),
            'and the fact itself still landed — moving the ledger write out of the transaction must not stop the mirror storing rows',
        );
    }

    /**
     * A VALUE FAULT ON A CHILD ROW IS FORWARDED ON THE PUBLIC INGEST PATH.
     *
     * ## THE DEFECT THIS EXISTS FOR (fix round 1/5 finding B6, pinned here in round 2)
     *
     * `UpdateIngestor::writeChildrenFromCatalog()` forwarded `fkClues` and
     * `unmappedClues` to the PSR-3 `clue()` seam and DROPPED `valueClues`
     * entirely — the same bucket, dropped at the same kind of hop, for the same
     * reason: the loop was simply never written. Round 1 added the loop, and
     * proved only that the suite stayed green. That is a DIFFERENT statement
     * from "the loop forwards the bucket", and an unpinned forwarding claim is
     * how this bug class recurs — so this case exists to fail if the loop is
     * deleted again.
     *
     * ## WHY THE FAULT IS INJECTED AT THE CONNECTION, NOT FAKED IN THE FIXTURE
     *
     * Same seam and same accounting as the two tests above: the real insert runs
     * against the real shipped DDL and only a failure the database actually
     * raised is re-reported with a MySQL driver code. `from_rank` is a CHILD
     * column of `tf_messages` (`text NOT NULL`, no default —
     * 2026_09_14_200010_create_tf_messages_tables.php:141-146) and the payload
     * gives it the OBJECT a TL `long` decodes into, which is the real shape of
     * this fault and makes the decomposer omit the column so the database
     * genuinely refuses the row. The parent `tf_messages` row still lands, so
     * the assertion below is about the CHILD and not about a whole-entry drop.
     *
     * ## WHY IT LIVES IN THIS FILE
     *
     * Because this is the third bucket-hop the project has lost at this
     * boundary, and the file is already the harness that drives a mirror write
     * through a real shipped schema with a real driver fault on one row. A
     * second harness would have been a second thing to keep in sync.
     */
    public function test_a_value_fault_on_a_child_row_is_forwarded_and_not_dropped(): void
    {
        $connection = $this->faultFromRankInserts();

        $notices = [];
        (new UpdateIngestor(logger: new ChildValueFaultClueLog($notices)))
            ->ingest(self::childValueFaultPayload(), self::ACCOUNT);

        // Preconditions, so this cannot pass vacuously: the child insert was
        // really attempted and really refused, it left nothing half-written, and
        // the parent row landed — so the clue below is about a CHILD row on a
        // successfully written entry, not about an entry that failed wholesale.
        self::assertSame(
            1,
            $connection->injectedFaults(),
            'precondition: the child row really was inserted and really was refused, so a value clue really exists to forward',
        );
        self::assertSame(
            0,
            (int) DB::table(self::CHILD_FAULT_TABLE)->count(),
            'precondition: the refused child row left nothing half-written behind it',
        );
        self::assertSame(
            1,
            (int) DB::table('tf_messages')->count(),
            'precondition: the parent row still landed — a per-row value fault degrades, it does not drop the entry',
        );

        // THE ASSERTION: the bucket was forwarded, as a value clue, naming the
        // table whose row was refused and the driver code that refused it.
        $values = array_values(array_filter(
            $notices,
            static fn (string $line): bool => str_contains($line, 'value does not fit its column'),
        ));

        self::assertCount(
            1,
            $values,
            'a value fault on a CHILD row must be reported to the clue seam — with the forwarding loop deleted, '
            .'the row is silently absent from the mirror and nothing says why',
        );
        self::assertStringStartsWith(
            self::CHILD_FAULT_TABLE.': ',
            $values[0],
            'the clue must name the table whose row was refused, off its "{$table}: …" prefix',
        );
        self::assertStringContainsString(
            'driver code '.self::CHILD_DRIVER_CODE,
            $values[0],
            'the forwarded clue is the clue STRING, so the reader learns which fault it was',
        );

        // AND THE BUCKETS STAY DISTINCT — a value fault must not be filed under
        // the relationship axis, which sends the next reader to the wrong graph.
        self::assertSame(
            [],
            array_values(array_filter(
                $notices,
                static fn (string $line): bool => str_contains($line, 'FOREIGN KEY constraint failed'),
            )),
            'a value fault is NOT a relationship fault — the buckets must not be conflated on the public path either',
        );
    }

    /**
     * Make the app's DEFAULT connection the fault-injecting one, so the
     * `DB::connection()` inside `writeChildrenFromCatalog()` — which hands the
     * child rows to `MirrorFactWriter` — is the connection that reports the MySQL
     * code.
     *
     * The PDO is the one `IngestTestCase` already migrated, so the shipped DDL is
     * present and the rewrite touches only the offending child table's inserts.
     */
    private function faultFromRankInserts(): MysqlFaultInjectingConnection
    {
        $manager = $this->app->make('db');
        $pdo = $manager->connection()->getPdo();

        $manager->extend(
            'sqlite',
            static fn (): MysqlFaultInjectingConnection => new MysqlFaultInjectingConnection(
                $pdo,
                self::CHILD_FAULT_TABLE,
                self::CHILD_DRIVER_CODE,
                self::CHILD_DRIVER_MESSAGE,
            ),
        );

        $manager->purge('ingest');

        return $manager->connection();
    }

    /**
     * One message whose `from_rank` CHILD column carries an object where the
     * shipped `NOT NULL` text column is declared.
     *
     * @return array<string, mixed>
     */
    private static function childValueFaultPayload(): array
    {
        return [
            '_' => 'message',
            'id' => 21,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => 444000555],
            'date' => 3,
            'message' => 'child value fault',
            'from_rank' => ['_' => 'accountPassword', 'has_recovery' => true],
        ];
    }

    /**
     * One entry: a top-level `message` whose `media` child carries a constructor
     * the catalog does not know.
     *
     * The child is what makes this entry produce a clue BEFORE anything raises:
     * `ingestNestedSources()` runs inside the transaction and ahead of the
     * top-level write, and a constructor the catalog does not know is the
     * decomposer's earliest possible exit — it returns no rows and one clue, so
     * the clue is accumulated and then the top-level insert raises.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            '_' => 'message',
            'id' => 11,
            'peer_id' => ['_' => 'peerChat', 'chat_id' => 444000555],
            'date' => 1,
            'message' => 'hi',
            'media' => ['_' => 'messageMediaBogus'],
        ];
    }
}

/**
 * The PSR-3 seam `UpdateIngestor::clue()` writes to, captured.
 *
 * It exists because the engine is SILENT BY DEFAULT: `clue()` calls
 * `$this->logger?->notice($message)`, so with no logger injected every clue in
 * this file's third test is discarded by the exact `?->` the defect lived
 * behind. Injecting a logger is therefore not a convenience here — it is the
 * only way the forwarding loop's output is observable at all.
 *
 * `notice` is the level `clue()` uses, and it is recorded verbatim rather than
 * stringified through a formatter, so the assertions read the clue string the
 * production code built.
 */
final class ChildValueFaultClueLog extends AbstractLogger
{
    /** @param list<string> $lines */
    public function __construct(private array &$lines) {}

    /**
     * @param  mixed  $level
     * @param  string|Stringable  $message
     * @param  array<mixed>  $context
     */
    public function log($level, $message, array $context = []): void
    {
        $this->lines[] = (string) $message;
    }
}
