<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Backfill\MemoryCursorStore;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Console\BackfillCommand;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;
use MeRezaRezaei\Teleframe\Tests\Ingest\MysqlFaultInjectingConnection;
use MeRezaRezaei\Teleframe\Tests\Support\FakeUserScope;

/**
 * THE BUCKET THAT WAS COMPUTED AND THEN THROWN AWAY: `valueClues` must reach
 * the operator's console, under its OWN label, on the path a real run takes.
 *
 * ## THE DEFECT THIS EXISTS FOR
 *
 * `MirrorFactWriter::write()` classifies a per-row "this value does not fit the
 * column the DDL gives it" fault into a FOURTH bucket, `valueClues` — MySQL
 * 1265 / 1364 / 1366 / 1406 and Postgres SQLSTATE 22001. That bucket is
 * produced, returned, and then dropped by every layer between the writer and
 * the terminal, because each of those layers REBUILDS a narrower array instead
 * of forwarding the writer's.
 *
 * The result is the exact defect class this cycle exists to eliminate: a row
 * that was dropped for a value fault shows up only as a LOWER `inserted`, which
 * is honest but anonymous. "Silently dropped, and you may infer why" is not a
 * named reason, and an operator holding a page of `stored dialogs page
 * account=7 {statements:9}` has no way to learn that one row failed because a TL
 * field arrived as an object where a `long` column is declared.
 *
 * `MirrorFactWriterValueNotStorableDegradesTest` already proves the BUCKET is
 * computed correctly. This proves the bucket SURVIVES the trip to the console.
 *
 * ## THE PATH EXERCISED IS THE REAL ONE, END TO END
 *
 *     teleframe:backfill --discover
 *       → dialogsPagePersister()                 (BackfillCommand)
 *         → MirrorIngesterFactory::buildResultRoute()
 *           → MirrorResultIngester::ingest()     ← rebuilt the array, dropped the key
 *             → MirrorFactWriter::write()        ← produced `valueClues`
 *
 * Every one of those frames is production code. The page comes from the
 * `SCOPE_RESOLVER_KEY` seam, exactly as `BackfillDiscoveryPersistTest` binds
 * it, so the walk, the decompose, the transaction and both clue loops are the
 * shipped ones.
 *
 * ## HOW A MySQL DRIVER CODE IS PRODUCED HERE, STATED NOT GLOSSED
 *
 * `MysqlFaultInjectingConnection` (tests/Ingest/) runs the REAL shipped DDL,
 * lets the REAL insert genuinely FAIL against it, and rewrites only the driver
 * code and message at the connection boundary — the exact layer where a real
 * MySQL driver supplies its own code in place of sqlite's.
 *
 * The failure below is NOT fabricated. A `users` companion vector carrying
 * `first_name` as an OBJECT rather than a `long` makes the decomposer omit the
 * column, and `tf_users_first_name.first_name` is `NOT NULL` — measured, not
 * assumed: sqlite refuses that insert with
 * `SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint
 * failed: tf_users_first_name.first_name`. Only the code and message are
 * rewritten, to MySQL's own 1366, which is what makes the writer's value arm
 * (and not its NOT NULL arm) fire — 1048 is the not-null code, and 1366's
 * message never contains the phrase `NOT NULL`.
 */
final class ValueClueReachesOperatorOutputTest extends IngestTestCase
{
    /**
     * The tenant this fixture writes under.
     *
     * MEASURED, not assumed: every row inserted here carries
     * `account_id = 7` (self::ACCOUNT).
     *
     * It was undeclared. With foreign-key enforcement on, every insert was
     * refused at the DATABASE rather than at the code under test — the test
     * failed on its own fixture and reported nothing about the behaviour it
     * exists to prove.
     *
     * Declared HERE rather than by relaxing seedIngestTenant(), because that
     * helper contracts that each test declares the tenants it means to use, so
     * a test writing under an unplanted id fails LOUDLY. These were failing
     * loudly, which is the mechanism working.
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    private const ACCOUNT = 7;

    /**
     * The one table whose inserts this suite faults: the child table the
     * `user.first_name` declared edge lands in, reached from the dialogs page's
     * companion `users` vector through the SAME `BackfillDiscoveryPersistTest`
     * walk. `access_hash` would have been the obvious choice and is the WRONG
     * one — `DialogDiscovery::accessHashIndex()` reads it, so an object there
     * raises `Array to string conversion` at DialogDiscovery.php:131, one layer
     * ABOVE the mirror route, and the fault never reaches the writer at all.
     * `first_name` is read by nothing on the discovery path, so the malformed
     * value travels all the way to the insert.
     */
    private const FAULT_TABLE = 'tf_users_first_name';

    /** MySQL ER_INVALID_CHARACTER_STRING — the operator's live 1366. */
    private const DRIVER_CODE = 1366;

    private const DRIVER_MESSAGE = "Incorrect string value '\\xCE\\xC8\\xCF\\xD2\\xC0' for column 'first_name' at row 1";

    /**
     * THE CLEAN PATH'S OUTPUT, CAPTURED FROM THIS TREE BEFORE ANY CHANGE.
     *
     * Byte-for-byte, including the `{statements:10}` count and the four-line
     * discovery block. This literal is the contract: a run with no value faults
     * must print exactly this, so threading the bucket through must not add a
     * row, a label, a heading or a trailing newline to a healthy run. That is
     * why the console changes add NOTHING on the empty path — the value-clue
     * table row and line are rendered only when the bucket is non-empty, which
     * is also better reporting: the presence of the line IS the signal.
     */
    // CORRECTED 2026-10-04: this constant pinned `{facts:10}`, which told an
    // operator the page carried ten stored facts. `MirrorFactWriter::write()`
    // counts STATEMENTS PLACED — measured on the shipped DDL, three facts
    // colliding on one primary key give `inserted=3` with four empty clue
    // buckets and one stored row — so the label named the wrong quantity.
    // It is now `{statements:10}`: same number, and the byte-for-byte
    // contract these two files share still pins it exactly. NOT WEAKENED —
    // the assertion is still an exact full-line match, and this literal is
    // still duplicated across both files so a drift fails one of them.
    private const CLEAN_OUTPUT = <<<'OUT'
        stored dialogs page account=7 {statements:10}
        discovered channel:1915503741 {3:1915503741, top_message:12424}
        discovered user:8329480050 {1:8329480050, top_message:103356}
        discovered chat:444000555 {2:444000555, top_message:7}
        discovered 3 dialog(s) for account=7
        OUT;

    /**
     * RED, THEN GREEN — the reason reaches the terminal under its own label.
     *
     * Before the threading this run printed the honest lower count
     * (`{statements:9}`) and NOTHING else: the row was skipped, `valueClues` held
     * the sentence explaining exactly why, and no layer between the writer and
     * the terminal carried it.
     */
    public function test_a_value_fault_reaches_the_operator_under_its_own_label(): void
    {
        $scope = $this->bind(new ValueNotStorableDiscoveryScope);

        $this->faultFirstNameInserts();

        $exit = Artisan::call('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true]);
        $out = Artisan::output();

        // Preconditions, so this cannot pass vacuously. The page really was
        // routed through the real mirror write path, and the refused row really
        // was the ONLY casualty — its well-formed sibling on the same page still
        // landed, which is exactly what a rolled-back transaction would lose
        // and exactly what per-row degradation exists to preserve.
        self::assertSame(
            1,
            $scope->dialogsPages,
            'precondition: one dialogs page was routed through the real mirror write path',
        );
        self::assertSame(
            1,
            (int) $this->app->make('db')->connection()->table('tf_users_access_hash')->count(),
            'precondition: the well-formed access_hash on the same page still landed, so the page was not dropped whole',
        );
        self::assertSame(
            0,
            (int) $this->app->make('db')->connection()->table(self::FAULT_TABLE)->count(),
            'precondition: the row that could not be stored left nothing half-written behind it',
        );

        self::assertSame(
            0,
            $exit,
            'a per-row value fault is a degradation, not a failed run — discovery must complete and the page must commit',
        );

        // THE REASON, under a label of its own that says which axis failed. A
        // value fault is NOT a foreign-key failure and NOT a modelling gap, so
        // it must not borrow either of their words: folding it in is what sends
        // the next reader to the relationship graph instead of the column.
        self::assertStringContainsString(
            'mirror value clue:',
            $out,
            'the value clue must reach the operator, labelled as a value fault',
        );
        self::assertStringContainsString(
            (string) self::DRIVER_CODE,
            $out,
            'the clue must carry the driver code, so the next reader knows which fault it was',
        );
        self::assertStringContainsString(
            self::FAULT_TABLE,
            $out,
            'the clue must name the table whose row was refused',
        );

        // AND IT MUST NOT BE FOLDED. This is the property that costs real time
        // when it is broken: a binary/encoding fault reported as a foreign-key
        // failure sends the reader to the wrong graph entirely.
        self::assertStringNotContainsString(
            'mirror FK clue:',
            $out,
            'a value fault is not a relationship fault — it must not be reported as one',
        );
        self::assertStringNotContainsString(
            'mirror unmapped field:',
            $out,
            'a value fault is not a modelling gap — the shipped DDL has the column',
        );

        // The count stays honest and LOWER: the refused row is not counted as
        // placed. 10 statements minus the one row that could not be stored.
        //
        // CORRECTED 2026-10-04, the label with it: this asserted `{facts:9}`, and
        // `facts` read as stored rows rather than statements placed — see the note
        // on CLEAN_OUTPUT above for the measurement. The assertion itself is
        // unchanged in strength: still an exact substring of the line, now naming
        // the quantity the counter actually holds.
        self::assertStringContainsString(
            'stored dialogs page account=7 {statements:9}',
            $out,
            'inserted must still count only statements PLACED — the refused row is not one of them',
        );
    }

    /**
     * THE COMMON PATH IS BYTE-FOR-BYTE UNCHANGED.
     *
     * A well-formed page (real `access_hash` and `first_name`, the shape `DiscoveryScope`
     * ships) through the SAME command, with the SAME seams bound, must print
     * exactly the output this tree printed before `valueClues` was threaded.
     *
     * This is the assertion that stops the fix from becoming a new defect: a
     * `value clues | 0` row added unconditionally to the gate table would
     * change every healthy run's output forever, to say nothing.
     */
    public function test_the_common_path_output_is_byte_identical_when_there_are_no_value_faults(): void
    {
        $this->bind(new DiscoveryScope);

        $exit = Artisan::call('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true]);
        $out = Artisan::output();

        self::assertSame(0, $exit);
        self::assertSame(
            self::CLEAN_OUTPUT."\n",
            $out,
            'with no value faults the operator must see EXACTLY the output this tree produced before — '
            .'no added table row, no added label, no trailing newline',
        );
        self::assertStringNotContainsString(
            'value clue',
            $out,
            'the value label must not appear at all on a clean run',
        );
    }

    /**
     * The console seams, bound exactly as `BackfillDiscoveryPersistTest` binds
     * them. Only the scope differs between the two tests above.
     */
    private function bind(FakeUserScope $scope): FakeUserScope
    {
        $this->app->instance(BackfillCommand::SCOPE_RESOLVER_KEY, static fn (): UserAccountScope => $scope);
        $this->app->bind(
            BackfillCommand::INGESTER_KEY,
            static fn (): callable => static fn (int $accountId): callable => static fn (array $batch): array => ['ingested' => 0],
        );
        $this->app->bind(
            BackfillCommand::CURSOR_STORE_KEY,
            static fn (): callable => static fn (int $accountId): MemoryCursorStore => new MemoryCursorStore,
        );

        return $scope;
    }

    /**
     * Make the app's DEFAULT connection the fault-injecting one, so the
     * `DB::connection()` inside `MirrorIngesterFactory::buildResultRoute()` is
     * the connection that reports the MySQL code.
     *
     * The PDO is the one `IngestTestCase` already migrated, so the shipped DDL
     * is present and the rewrite touches only the offending table's inserts —
     * every PRAGMA the writer uses for `getColumnListing()`/`getIndexes()` runs
     * against sqlite unmodified, and the other nine facts on the page insert
     * normally.
     */
    private function faultFirstNameInserts(): void
    {
        $manager = $this->app->make('db');
        $pdo = $manager->connection()->getPdo();

        // `DatabaseManager::extend()` on the DRIVER, not a `db.connection.*`
        // container binding: this Laravel version's `ConnectionFactory::make()`
        // consults neither, and `makeConnection()` reaches an extension by
        // connection name first and then by driver name — so this is the only
        // seam that actually swaps the resolved connection class.
        $manager->extend(
            'sqlite',
            static fn (): MysqlFaultInjectingConnection => new MysqlFaultInjectingConnection(
                $pdo,
                self::FAULT_TABLE,
                self::DRIVER_CODE,
                self::DRIVER_MESSAGE,
            ),
        );

        $manager->purge('ingest');
    }
}

/**
 * The discovery page with EXACTLY ONE field changed from `DiscoveryScope`:
 * `users[0].access_hash` is an OBJECT where the shipped
 * `tf_users_access_hash.access_hash` column is declared `bigInteger NOT NULL`.
 *
 * That is the real shape of this fault class — a TL `long` field decoded as an
 * object — and it is what makes the decomposer omit the column and the database
 * genuinely refuse the row. Nothing else differs, so the two tests in this file
 * differ by the injected fault and by nothing else.
 */
final class ValueNotStorableDiscoveryScope extends FakeUserScope
{
    /** How many dialogs pages this scope served, so the walk is provable. */
    public int $dialogsPages = 0;

    /** @param array<string, mixed> $params */
    public function call(string $method, array $params = []): array
    {
        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method !== 'messages.getDialogs') {
            return match ($method) {
                'messages.getHistory' => ['_' => 'messages.messagesSlice', 'count' => 0, 'messages' => []],
                default => parent::call($method, $params),
            };
        }

        $this->dialogsPages++;

        return $this->page();
    }

    /**
     * `DiscoveryScope`'s page with EXACTLY ONE field changed: `users[0].first_name`
     * is an OBJECT where the shipped `tf_users_first_name.first_name` column is
     * declared `NOT NULL`.
     *
     * The page is reused rather than re-typed, deliberately: a hand-written
     * second copy of the fixture is a second thing to keep in sync, and this
     * suite's whole claim is that the two runs differ by the injected fault
     * alone. Every other field — the channel hash, the basic-group dialog, all
     * three dialogs — is byte-identical to the clean scope's.
     */
    public function page(): array
    {
        $page = (new DiscoveryScope)->page();

        $page['users'][0]['first_name'] = ['_' => 'accountPassword', 'has_recovery' => true];

        return $page;
    }
}
