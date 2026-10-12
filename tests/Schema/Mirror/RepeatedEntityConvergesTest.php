<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\SQLiteBuilder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsMysqlMigrations;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PDOException;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * A user that appears on TWO dialog pages must converge, not collide.
 *
 * MEASURED 2026-10-04 on the operator's live account, after `migrate:fresh`:
 * 39 pages stored, 10 discarded. Two of the failures were
 *   Duplicate entry '1724372757-5633995061' for key 'tf_users.PRIMARY'
 * with the insert being
 *   insert into `tf_users` (`account_id`, `id`, `constructor`, ...) values (1724372757, 5633995061, user, ...)
 *
 * `tf_users` is keyed `(account_id, id)` and Telegram's own `users` vector repeats a user
 * across pages freely, so a repeat is NORMAL traffic, not an edge case. A page that raises
 * takes every other fact on that page with it.
 *
 * ## THE DIAGNOSIS, measured rather than assumed
 *
 * The plan offered two candidates. Both were tested; ONE is the cause and the other is dead.
 *
 * **DEAD — "the two writes used different keys".** `primaryKeyColumns()` returns the CORRECT
 * key. Measured against the shipped DDL:
 *   primaryKeyColumns('tf_users') === ["account_id","id"]
 *   primaryKeyColumns('tf_chats') === ["account_id","id"]
 * The derived `$key` at the moment of failure is the pair the database itself reported,
 * `(1724372757, 5633995061)` — the same pair as `tf_users.PRIMARY`. It also matters that
 * `tf_users` declares exactly ONE index, the PRIMARY (`getIndexes('tf_users')` returns one
 * entry), so there is no second unique index for a repeat to collide on, and nothing for the
 * plan's Step-4 outline to collect. Widening the key would have changed a correct key to fix
 * a race.
 *
 * **CAUSE — a non-locking existence read racing a locking write, inside a page
 * transaction.** `placeRow()` ended in `updateOrInsert($key, $row)`, and Laravel's
 * `updateOrInsert()` is a TIME-OF-CHECK/TIME-OF-USE pair
 * (vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php:4411):
 *   $exists = $this->where($attributes)->exists();   // NON-LOCKING consistent read
 *   if (! $exists) { return $this->insert(...); }    // LOCKING current read
 * Those two statements need not agree about the table. The page write runs inside a
 * transaction (src/Laravel/MirrorIngesterFactory.php:237,
 * src/Teleframe/Ingest/MirrorChunkSync.php:63) and MySQL's default isolation is REPEATABLE
 * READ, where a consistent read is pinned to the snapshot the transaction took at its first
 * statement while the INSERT is not. A row another writer committed AFTER that snapshot is
 * INVISIBLE to `exists()` and VISIBLE to the INSERT: the writer concludes "not there", takes
 * the INSERT branch, and the database raises the duplicate key. `SELECT` misses, `INSERT`
 * collides — precisely the live shape.
 *
 * A second writer existed. Line 1 of the recorded run (/tmp/nf5-discover.log) is
 *   mirror account=1724372757 {method:messages.getSavedDialogs, inserted:537}
 * — a `messages.getSavedDialogs` mirror wrote 537 rows while discovery walked pages, into the
 * same account and therefore the same `tf_users`. The FIRST duplicate in the log is at line
 * 7, BEFORE the first `stored dialogs page` line at line 110: an ordering a single sequential
 * writer cannot produce and a concurrent one does.
 *
 * ## THE FIX, and how it was proved on the live engine
 *
 * `placeRow()` now resolves a repeat with ONE atomic statement — `upsert()` reusing the SAME
 * `$primary` as the conflict target — so the conflict is detected by the INSERT itself,
 * which is a locking current read that sees the newest committed row. One statement cannot
 * disagree with itself, so the window closes without touching the transaction boundary, the
 * key, or any surrounding arm. `primaryKeyColumns()` remains the only introspection in this
 * path; no second query was added.
 *
 * MEASURED on the live engine (MySQL 8.4.11, default isolation REPEATABLE-READ), same window,
 * old shape vs new shape:
 *   OLD updateOrInsert -> SQLSTATE[23000] 1062 Duplicate entry
 *                         '1724372757-5633995061' for key 'tf_users.PRIMARY'
 *   NEW upsert         -> CONVERGED {"inserted":1,"fkClues":[],"unmappedClues":[],"valueClues":[]}
 *
 * ## WHY THE GATES BELOW ARE SHAPED THE WAY THEY ARE
 *
 * The obvious test — write the same user twice on one connection — PASSES before the fix as
 * well as after, and the measurement says why: with a single writer there is no window
 * between `exists()` and the INSERT, so `exists()` finds the row and the write converges.
 * A green test that was always green guards nothing, so it is kept only for what it does
 * pin (the derived KEY is right), and the gates that carry the regression are the other two.
 *
 * The race itself CANNOT be written on sqlite: sqlite has no MVCC snapshot isolation, so a
 * stale snapshot is not expressible there — a probe that tried it deadlocked with `database is
 * locked` instead of reproducing a duplicate key. So the fences are split by what each engine
 * can honestly assert:
 *
 *  - test_a_repeated_row_costs_one_statement_not_a_check_then_write_pair runs on the DEFAULT
 *    sqlite engine and therefore in EVERY CI run. It asserts the structural property that
 *    actually closes the race — a repeat is resolved by one statement, never by a
 *    non-locking existence check followed by a write. It went RED on the old shape and is
 *    GREEN on the new one, on any engine.
 *  - test_a_user_repeated_while_a_concurrent_writer_commits_converges opens the real MVCC
 *    window on the LIVE engine and asserts the plan's own assertion at full strength. It is
 *    opt-in via TELEFRAME_MYSQL_* because the live engine needs credentials this repository
 *    does not and must not carry; it is skipped, loudly and with the reason, when none are
 *    supplied. It is NOT part of the default CI gate, and this file does not pretend
 *    otherwise: the CI matrix runs postgres:17 and no MySQL service
 *    (.gitlab-ci.yml, the `services:` block), so on CI the full-convergence assertion is the
 *    one that does not execute. The sqlite fence above is what runs there.
 *
 * ## FIXTURE CORRECTIONS (measured 2026-10-04, NOT assertion weakening)
 *
 * The plan's literal test code did not exercise the writer at all, and both
 * corrections below leave every assertion byte-identical — the assertion
 * STRENGTH is unchanged and was not lowered to fit the code.
 *
 *  1. **The migration named in the plan does not create `tf_users`.** Measured:
 *     `grep -c "tf_users" src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php`
 *     returns `0`, and `grep -rn "Schema::create\('tf_users'" src/Laravel/Migrations/`
 *     names `2026_09_14_200001_create_tf_users_tables.php:63` as the ONLY creator.
 *     A harness that boots the bots migration and then writes to `tf_users`
 *     proves nothing about `tf_users`.
 *  2. **`write()` does not take a table=>rows map.** Its real signature is
 *     `write(ConnectionInterface $db, array $rows)` and it destructures
 *     `foreach ($rows as ['table' => $table, 'row' => $row])`
 *     (`MirrorFactWriter.php:523`) — a flat LIST of `{table, row}` pairs, which
 *     is exactly what `MirrorFactDecomposer::decompose()` returns
 *     (`src/Schema/Mirror/MirrorFactDecomposer.php:307`, `return ['rows' => $rows, ...]`)
 *     and what the live caller passes (`src/Laravel/MirrorIngesterFactory.php:428`,
 *     `$writer->write($db, $fact_rows['rows'])`). Passing a map made `$table`
 *     null and raised
 *     `TypeError: MirrorFactWriter::columnListing(): Argument #2 ($table) must be
 *     of type string, null given` — a failure of the HARNESS, not of the writer.
 *
 * Running the plan's literal code produced exactly that TypeError, which is why
 * the fixture had to be corrected before the RED step could mean anything.
 */
final class RepeatedEntityConvergesTest extends TestCase
{
    // Phase 5 Step 1 moved the live-engine gate off its private
    // TELEFRAME_MYSQL_* reader and onto the SHARED MySQL track
    // (tests/Concerns/RunsMysqlMigrations.php), which is the single place that
    // decides whether this repository can reach a MySQL/InnoDB server. A second
    // reader is a second answer to the same question, and the two disagreed:
    // the old gate fired on TELEFRAME_MYSQL_USER alone (default-skip unless the
    // operator exported credentials by hand), while the track answers "can a
    // MySQL 8.4 server actually be reached on the configured host" — which is
    // what the CI job provides (.gitlab-ci.yml `services: mysql:8.4` plus
    // TELEFRAME_MYSQL=1). So this case now RUNS wherever the track runs, and
    // skips with the track's own reason everywhere else.
    use RunsMysqlMigrations;

    /** The user the live run collided on, verbatim from /tmp/nf5-discover.log line 158. */
    private const ACCOUNT_ID = 1724372757;

    private const USER_ID = 5633995061;

    /** The shipped migration that creates tf_users — verified, not assumed. */
    private const MIGRATION = __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php';

    private static function firstWrite(): array
    {
        return ['account_id' => self::ACCOUNT_ID, 'id' => self::USER_ID, 'constructor' => 'user', 'bot' => 0];
    }

    private static function secondWrite(): array
    {
        return ['account_id' => self::ACCOUNT_ID, 'id' => self::USER_ID, 'constructor' => 'user', 'bot' => 1];
    }

    /** A second, DIFFERENT user — the neighbour that must survive a collision beside it. */
    private static function neighbourWrite(): array
    {
        return ['account_id' => self::ACCOUNT_ID, 'id' => 5633995062, 'constructor' => 'user', 'bot' => 0];
    }

    /**
     * The plan's own assertion, at full strength, on the path that is NOT broken.
     *
     * One connection, no concurrent writer, so there is no window between the existence
     * read and the write and the write converges. This passed BEFORE the fix too: its job
     * is to pin that the derived KEY is right, which is what killed half the plan's
     * diagnosis. It is not the regression gate, and the class docblock says so.
     */
    public function test_a_user_written_twice_converges_to_one_row(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();

        $writer = new MirrorFactWriter;
        $writer->write($db, [['table' => 'tf_users', 'row' => self::firstWrite()]]);
        $writer->write($db, [['table' => 'tf_users', 'row' => self::secondWrite()]]);

        $this->assertConverged($db->table('tf_users')->where('account_id', self::ACCOUNT_ID)->where('id', self::USER_ID)->get());
    }

    /**
     * A repeat must cost ONE statement. Runs on the default engine, so it runs in CI.
     *
     * This is the property that closes the race, stated so that any engine can check it.
     * The old shape spent two statements on a repeat — a non-locking existence check, then
     * a locking write — and the window between them is the entire defect. A shape that
     * resolves the repeat with a single atomic statement has no window to leave open, so
     * this assertion holds on MySQL, postgres and sqlite alike.
     *
     * It is counted on the SECOND write only: the first write pays the schema introspection
     * (`columnListing()`, `primaryKeyColumns()`, the FK graph) that `write()` does once per
     * table per batch, and that cost is unrelated to the race.
     *
     * WHICH OF ITS TWO ASSERTIONS BITES, measured by reverting the fix:
     *  - on the default engine the COUNT alone does not bite, because with one writer
     *    `exists()` finds the row and the old shape takes its UPDATE branch — one placement
     *    statement, just the wrong one. The second assertion is what bites there: the
     *    placement must BE the insert.
     *  - the count bites whenever `exists()` misses, which is precisely the concurrent case.
     * Both are kept, because the defect is "a check-then-write pair", and each assertion
     * pins one half of that.
     */
    public function test_a_repeated_row_costs_one_statement_not_a_check_then_write_pair(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();

        $writer = new MirrorFactWriter;
        $writer->write($db, [['table' => 'tf_users', 'row' => self::firstWrite()]]);

        $db->flushQueryLog();
        $db->enableQueryLog();

        $writer->write($db, [['table' => 'tf_users', 'row' => self::secondWrite()]]);

        $statements = array_values(array_filter(
            array_map(
                static fn (array $q): string => strtolower(trim($q['query'])),
                $db->getQueryLog(),
            ),
            // `write()` re-introspects per call (its column/key/FK caches are per-batch),
            // so the fence counts PLACEMENT statements only: the ones that touch row data.
            static fn (string $sql): bool => (bool) preg_match('/^(insert|update|delete)\b/', $sql),
        ));

        self::assertCount(
            1,
            $statements,
            'a repeated row must be resolved by ONE atomic placement statement. More than one '
            .'means the write is a check-then-write pair, and the gap between a non-locking '
            .'existence check and a locking write is exactly the window a concurrent writer '
            .'slips through — the measured 2026-10-04 failure. Statements seen: '.implode(' ;; ', $statements),
        );
        self::assertStringStartsWith(
            'insert into',
            $statements[0],
            'the one statement that places a repeat must be the INSERT itself, because the '
            .'INSERT is the locking read that can see a row committed after the transaction '
            .'took its snapshot. Got: '.$statements[0],
        );
    }

    /**
     * A duplicate must cost ONE row, on the one path where a duplicate can still be raised.
     *
     * ## WHY THIS CASE EXISTS AT ALL, measured 2026-10-04 — and it is NOT the covered-key path
     *
     * After Task 1's `upsert()`, the ORIGINAL duplicate is gone, and that was measured across
     * the WHOLE shipped schema rather than assumed from `tf_users`:
     *
     *   - Every migration in `src/Laravel/Migrations/` was executed against sqlite, and the
     *     36 tables named by `src/Schema/schema/mirror-catalog.json` were introspected with the
     *     very `getIndexes()` call `primaryKeyColumns()` makes: **36 of 36 carry a
     *     discoverable primary key, and 36 of 36 declare NO non-primary unique index.** So on
     *     the covered-key path there is no second identity a repeat could collide on.
     *   - MySQL cannot raise one from `upsert()` at all: `MySqlGrammar::compileUpsert()`
     *     (vendor/.../Query/Grammars/MySqlGrammar.php:426) **discards `$uniqueBy`** and emits
     *     `on duplicate key update`, which fires on ANY unique key. postgres and sqlite keep
     *     the conflict target, but on those two drivers there is no other unique index to
     *     violate (measured above).
     *
     * ## THE PATH THAT IS STILL REACHABLE, and Task 1 did not touch it
     *
     * `placeRow()` has a second branch that Task 1 left exactly as it was:
     *
     *   if ($primary === null) { $db->table($table)->insert($row); return null; }
     *
     * `$primary` is null whenever the table's primary key cannot be introspected — which is
     * `primaryKeyColumns()`'s OWN documented fallback ("cannot introspect — let the insert
     * raise the real error"). On that branch the write is a BARE INSERT: nothing converges a
     * repeat, the database still enforces the PRIMARY, and the second row of the same
     * identity raises — taking every unrelated row queued behind it out of `write()` and,
     * through `MirrorIngesterFactory`'s transaction, out of the page.
     *
     * It is a real branch, not a hypothetical: `primaryKeyColumns()` returns null on ANY
     * `QueryException` from `getIndexes()`, and index introspection is an ordinary thing to
     * lose — a restricted `information_schema`/`pg_catalog` grant, a proxy that filters
     * catalog reads. The test reproduces it the way it actually happens, by making
     * `getIndexes()` raise on a connection that is otherwise entirely real.
     *
     * The claim that this arm is NARROW — it classifies a unique violation and nothing else —
     * is not this file's assertion to make. It is pinned by
     * `tests/Ingest/MirrorFactWriterValueNotStorableDegradesTest::test_an_unclassifiable_error_still_raises`
     * and `::test_a_bare_sqlite_constraint_failure_still_raises`, which assert that sqlite's
     * generic code `19` is NOT classified. This class's own
     * `test_an_unclassifiable_failure_still_raises_on_this_path` keeps the promise on the
     * branch it adds.
     */
    public function test_a_duplicate_key_costs_one_row_not_the_page(): void
    {
        $db = self::connectionWithoutIndexIntrospection();

        $report = (new MirrorFactWriter)->write($db, [
            ['table' => 'tf_users', 'row' => self::firstWrite()],
            // Same identity as the row above, different values: the collision.
            ['table' => 'tf_users', 'row' => self::secondWrite()],
            ['table' => 'tf_users', 'row' => self::neighbourWrite()],
        ]);

        // The neighbour must be there. This is the assertion the whole arm exists for: one
        // colliding row used to cost every row queued behind it.
        self::assertSame(
            2,
            (int) $db->table('tf_users')->where('account_id', self::ACCOUNT_ID)->count(),
            'a duplicate must cost ONE row. Losing the row queued behind it is the whole defect.',
        );

        // The duplicate is NOT a placed row, so it is not counted — the arm above
        // raises out of `placeRow()` and `$inserted++` never runs. On THIS branch
        // `inserted` coincides with rows held, and the assertion below is the
        // correct one; it is not the general contract.
        //
        // (Corrected 2026-10-04: this comment said "inserted is what every caller
        // advances a watermark and emits an event on", and the assertion message
        // said "inserted counts rows the mirror HOLDS". Neither is true in
        // general. `inserted` counts STATEMENTS PLACED, so on the converge path
        // `placeRow()` reaches with an `upsert()` it counts twice while the table
        // holds one row — measured, three facts sharing one shipped primary key
        // give `inserted=3`, four empty clue buckets, one stored row. And no
        // caller advances a watermark on it: `teleframe:ingest:gate` holds its
        // pts watermark on the four CLUE BUCKETS and the bus path holds none.
        // `tests/Schema/Mirror/InsertedCountsStatementsNotRowsTest.php` is the
        // gate for the real contract.)
        self::assertSame(
            2,
            $report['inserted'],
            'a row refused by the UNIQUE arm must not be counted: it reached neither '
            .'the upsert nor $inserted++, so this branch\'s count matches the rows '
            .'the mirror holds.',
        );

        // And it is REPORTED, naming the table and the identity that collided.
        $reported = array_merge(
            $report['fkClues'],
            $report['unmappedClues'],
            $report['valueClues'],
        );
        $duplicates = array_values(array_filter(
            $reported,
            static fn (string $clue): bool => str_contains($clue, 'tf_users') && str_contains($clue, 'UNIQUE constraint failed'),
        ));
        self::assertCount(
            1,
            $duplicates,
            'a refused duplicate must be reported EXACTLY once, naming the table and the driver\'s own '
            .'words, so the ledger says which identity collided. Buckets present: '
            .json_encode([
                'fk' => $report['fkClues'],
                'unmapped' => $report['unmappedClues'],
                'value' => $report['valueClues'],
            ], JSON_UNESCAPED_SLASHES),
        );

        // The collision must not half-apply either: the FIRST write's values stand, because the
        // second one was refused rather than merged.
        self::assertSame(
            0,
            (int) $db->table('tf_users')->where('account_id', self::ACCOUNT_ID)->where('id', self::USER_ID)->value('bot'),
            'a refused duplicate must leave the stored row untouched, not overwrite half its columns',
        );
    }

    /**
     * The arm this class adds must classify a UNIQUE violation and NOTHING else.
     *
     * The whole-batch defect it must NOT swallow is a fact table the schema never created —
     * `write()`'s own comment on this path says that error is meant to surface, "as it always
     * did", because a missing table is not a fact about one row. Without this assertion the
     * arm would be "catch every sqlite constraint on the introspection-fallback branch", and a
     * host whose migrations never ran would report "one row skipped" forever instead of raising.
     *
     * Measured, not assumed: sqlite's state for this failure is `HY000` and its driver code is
     * `5` (`SQLITE_ERROR`, "database is locked") or `1` ("no such table") — neither is a
     * constraint code at all, so the arm below cannot reach them even by accident.
     */
    public function test_an_unclassifiable_failure_still_raises_on_this_path(): void
    {
        $db = self::connectionWithoutIndexIntrospection();

        $raised = null;

        try {
            (new MirrorFactWriter)->write($db, [
                // No such table: the shipped DDL never created it.
                ['table' => 'tf_table_that_was_never_migrated', 'row' => ['account_id' => self::ACCOUNT_ID]],
            ]);
        } catch (QueryException $e) {
            $raised = $e;
        }

        self::assertNotNull(
            $raised,
            'a missing fact table is a whole-batch defect, not a duplicate. The unique arm must '
            .'not widen into a blanket catch on this branch, or a host whose migrations never ran '
            .'reports "one row skipped" forever instead of raising.',
        );
        self::assertStringNotContainsString(
            'UNIQUE constraint failed',
            $raised->getMessage(),
            'what raised is not a duplicate, so the arm must not have claimed it',
        );
    }

    /**
     * A connection carrying the SHIPPED `tf_users` DDL whose INDEX introspection raises.
     *
     * Real in every other respect — the same PDO handle, the same shipped migration, a genuine
     * `Illuminate\Database\Connection` subclass, so `primaryKeyColumns()`'s `instanceof` check
     * is satisfied and the failure lands where it lands in production: on `getIndexes()`.
     * Only that ONE call is denied, so the column listing still works and the writer's
     * unmodelled-field filter behaves exactly as it does normally.
     */
    private static function connectionWithoutIndexIntrospection(): SQLiteConnection
    {
        $shipped = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();

        return new class($shipped->getPdo(), ':memory:', '', $shipped->getConfig()) extends SQLiteConnection
        {
            public function getSchemaBuilder()
            {
                // The parent does this before building; skipping it left the grammar null and
                // failed in the harness, not in the writer.
                if (is_null($this->schemaGrammar)) {
                    $this->useDefaultSchemaGrammar();
                }

                $connection = $this;

                return new class($connection) extends SQLiteBuilder
                {
                    public function getIndexes($table)
                    {
                        throw new QueryException(
                            'sqlite',
                            'select * from pragma_index_list(?)',
                            [$table],
                            new PDOException('catalog access denied'),
                        );
                    }
                };
            }
        };
    }

    /**
     * THE LIVE-ENGINE GATE — a repeat while a concurrent writer commits must converge.
     *
     * This is the 2026-10-04 failure opened deliberately: writer A pins its REPEATABLE READ
     * snapshot while the table is empty, writer B stores the same user and COMMITS (the
     * `messages.getSavedDialogs` mirror running alongside discovery), then writer A — a
     * discovery page — stores the same user again.
     *
     * Opt-in: the live engine needs credentials this repository does not carry and must not
     * (see the class docblock). Supply TELEFRAME_MYSQL_HOST / _PORT / _DATABASE / _USER /
     * _PASSWORD to run it. Without them this SKIPS with the reason printed; it never passes
     * silently, and no assertion in it is weakened to accommodate a missing engine.
     */
    public function test_a_user_repeated_while_a_concurrent_writer_commits_converges(): void
    {
        if (! static::mysqlTrackEnabled()) {
            self::markTestSkipped(
                'Live-engine gate, decided by the shared MySQL track '
                .'(tests/Concerns/RunsMysqlMigrations.php): no MySQL 8.4 server answers on the '
                .'configured host, or TELEFRAME_MYSQL_SKIP=1. Set TELEFRAME_MYSQL=1 with the '
                .'TELEFRAME_MYSQL_* connection env to force it — a forced run that cannot reach '
                .'the database FAILS rather than skips. The default-engine fence '
                .'(test_a_repeated_row_costs_one_statement_not_a_check_then_write_pair) is what '
                .'runs without a MySQL server; sqlite cannot host this case at all: it has no '
                .'MVCC snapshot isolation, so the stale read it depends on is not expressible.',
            );
        }

        $db = $this->scratchMysql();

        // ---- Writer A opens FIRST and pins its snapshot while the table is empty. ----
        $a = self::mysqlCapsule($db)->getConnection();
        $a->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $a->beginTransaction();
        // The snapshot is taken by the transaction's FIRST statement, not by BEGIN, so it
        // has to be pinned by a real read while the table is still empty. The live page
        // transaction reads before it writes for the same reason and by the same means:
        // `write()` introspects the table's columns and its FK graph before placing a row.
        self::assertSame(0, $a->table('tf_users')->count(), 'writer A must take its snapshot while tf_users is empty, or this case is vacuous');

        // ---- Writer B stores the user and COMMITS, after A's snapshot. ----
        $b = self::mysqlCapsule($db)->getConnection();
        $b->transaction(function ($conn): void {
            (new MirrorFactWriter)->write($conn, [['table' => 'tf_users', 'row' => self::firstWrite()]]);
        });

        // The premise, asserted rather than assumed: A's non-locking read cannot see B's
        // committed row. Were it visible the case would prove nothing.
        self::assertFalse(
            $a->table('tf_users')->where('account_id', self::ACCOUNT_ID)->where('id', self::USER_ID)->exists(),
            'the premise of this case is a stale snapshot: writer A must NOT see the row writer B committed after A took its snapshot',
        );

        // ---- Writer A, the discovery page, stores the same user. ----
        $raised = null;

        try {
            (new MirrorFactWriter)->write($a, [['table' => 'tf_users', 'row' => self::secondWrite()]]);
        } catch (Throwable $e) {
            $raised = $e;
        }

        if ($raised !== null) {
            $a->rollBack();

            self::fail(
                'a user repeated while a concurrent writer commits must CONVERGE, not raise. '
                .'The write died with '.get_class($raised).': '.$raised->getMessage()
                .' — this is the live 2026-10-04 failure (Duplicate entry '
                .self::ACCOUNT_ID.'-'.self::USER_ID." for key 'tf_users.PRIMARY') and it "
                .'discards every other fact on the page.',
            );
        }

        $a->commit();

        $this->assertConverged($b->table('tf_users')->where('account_id', self::ACCOUNT_ID)->where('id', self::USER_ID)->get());
    }

    /**
     * The plan's assertion, verbatim, as one helper so every case is held to exactly the
     * same strength.
     *
     * @param  Collection<int, object>  $rows
     */
    private function assertConverged(mixed $rows): void
    {
        self::assertCount(
            1,
            $rows,
            'a user written twice must converge to ONE row; a second row means the two writes '
            .'disagree about identity',
        );
        self::assertSame(
            1,
            (int) $rows[0]->bot,
            'the SECOND write must win — convergence means the later fact is the current one',
        );
    }

    /**
     * A scratch database carrying the SHIPPED `tf_users` DDL.
     *
     * The DDL is executed by the SHIPPED migration, through Laravel's own mysql grammar, on
     * a throwaway database — so the table under test is the shipped table rather than a
     * hand-typed second copy of it, which is the drift this repo has been bitten by before.
     */
    private function scratchMysql(): string
    {
        // The TRACK's database, dropped and recreated through the track's own
        // lifecycle helper — NOT a private scratch database of this class's own
        // naming. Measured 2026-10-04 on the track's live server:
        // `show grants` for the track user is
        //   GRANT CREATE, DROP ON *.*            (schema lifecycle only)
        //   GRANT ALL PRIVILEGES ON `teleframe_mysql_track`.*  (the data)
        // so a class that invented its own database could create and drop it and
        // then be refused every SELECT on it — "1142 SELECT command denied to
        // user 'teleframe_track'@'localhost' for table 'tf_users'", measured, with
        // the case otherwise fully runnable. The track's own database is the one
        // the credentials can actually use.
        $this->dropAndCreateMysqlDatabase();

        $dbName = self::mysqlConnectionConfig()['database'];

        $capsule = self::mysqlCapsule($dbName);

        // The shipped migration resolves `Schema::` through the facade, so the facade needs
        // an application whose `db.schema` is THIS connection. Point it at the mysql
        // connection for the duration of the DDL, then put back whatever was there before —
        // the other cases in this class run on sqlite and would otherwise inherit it.
        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        $previousContainer = Container::getInstance();
        $previousFacade = Facade::getFacadeApplication();
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        try {
            $migration = require self::MIGRATION;
            $migration->up();
        } finally {
            Facade::setFacadeApplication($previousFacade);
            Facade::clearResolvedInstances();
            Container::setInstance($previousContainer);
        }

        return $dbName;
    }

    /**
     * A capsule on the live engine. Returned whole rather than as a bare connection
     * because the DDL below needs the DATABASE MANAGER to satisfy the facade.
     *
     * Reads the TRACK's connection config (username/password/host/port), never
     * its own env reader — see the `use RunsMysqlMigrations` note above.
     */
    private static function mysqlCapsule(string $dbName): Capsule
    {
        $config = self::mysqlConnectionConfig();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'database' => $dbName,
            'username' => $config['username'],
            'password' => $config['password'],
            'host' => $config['host'],
            'port' => $config['port'],
            'charset' => 'utf8mb4',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }

}
