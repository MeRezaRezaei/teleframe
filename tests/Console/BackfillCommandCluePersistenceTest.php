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
 * A BACKFILL PAGE THAT COULD NOT PLACE A FACT MUST BE RECORDED, NOT ONLY PRINTED.
 *
 * ## THE DEFECT THIS EXISTS FOR (fix round 1/5 finding B1)
 *
 * `BackfillCommand` reads three clue buckets off every mirror write it makes —
 * `mirrorAccountProfile()`, `mirrorAccountCollections()` and the
 * `dialogsPagePersister()` closure — and each of those three sites did NOTHING
 * WITH THEM except `$this->components->warn(...)`. A print is not a record.
 * The run that printed the clue ends, the terminal scrolls, and the coverage gap
 * is gone: nothing in the database says the mirror ever failed to place those
 * rows.
 *
 * The backfill is the worst surface for that, not a neutral one. It is the
 * HIGHEST-VOLUME writer in the repository — a discovery run places thousands of
 * facts over hundreds of pages — so the path that wrote the most rows is the
 * path that accounted for the fewest of them. `IngestGateCommand` gates; this
 * command is expected to keep making progress on a schema it knows is
 * incomplete, which is precisely the case where an unrecorded gap costs the most.
 *
 * ## WHY THE FAULT IS INJECTED AT THE CONNECTION, NOT FAKED IN THE FIXTURE
 *
 * `ValueClueReachesOperatorOutputTest` proves the same bucket reaches the
 * TERMINAL, using the same `DiscoveryScope` page and the same
 * `MysqlFaultInjectingConnection` seam. This file proves the other half — that
 * it reaches the LEDGER — and so it reuses both rather than inventing a second
 * fixture: the page is `DiscoveryScope`'s (well-formed, ten facts), and the only
 * thing this test adds is the driver-level fault on one of its inserts.
 *
 * Nothing about the failure is fabricated. `MysqlFaultInjectingConnection` lets
 * the REAL insert run against the REAL shipped DDL and intercepts only a real
 * failure, rewriting the driver code to MySQL's 1366 at the connection
 * boundary — the layer where a real MySQL driver supplies its own code in place
 * of sqlite's. See that class's docblock for the full accounting.
 *
 * ## THE SCHEMA IS THE SHIPPED ONE
 *
 * `IngestTestCase` executes `UpdateIngestor::migrationPaths()` — the whole
 * curated dial plus the off-dial entity anchors — so `tf_ingest_clues` here is
 * created by the shipped migration, never by a transcribed second copy that
 * could drift from it.
 */
final class BackfillCommandCluePersistenceTest extends IngestTestCase
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
     * companion `users` vector. Same choice, same reason as
     * `ValueClueReachesOperatorOutputTest::FAULT_TABLE` — `access_hash` is read
     * by `DialogDiscovery` one layer ABOVE the mirror route and would raise
     * there instead, never reaching the writer.
     */
    private const FAULT_TABLE = 'tf_users_first_name';

    /** MySQL ER_INVALID_CHARACTER_STRING — the operator's live 1366. */
    private const DRIVER_CODE = 1366;

    private const DRIVER_MESSAGE = "Incorrect string value '\\xCE\\xC8\\xCF\\xD2\\xC0' for column 'first_name' at row 1";

    /**
     * The clean page's output, byte for byte.
     *
     * Duplicated from `ValueClueReachesOperatorOutputTest` rather than read from
     * it on purpose: that constant is private to its file, and widening another
     * test's visibility to make a byte-for-byte contract shareable would edit a
     * file this lane does not own. A literal that can drift is only a problem if
     * something reads it; here BOTH files assert the same bytes against the same
     * command and the same `DiscoveryScope` page, so a drift shows up as one of
     * the two failing.
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
     * The ledger must say the row was refused, in the bucket that says WHY, with
     * the driver code attached.
     */
    public function test_a_backfill_page_that_cannot_place_a_fact_is_persisted_to_the_ledger(): void
    {
        $scope = $this->bind(new ObjectFirstNameDiscoveryScope);

        $this->faultFirstNameInserts();

        $exit = Artisan::call('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true]);

        // Preconditions, so this cannot pass vacuously: the page really went
        // through the shipped mirror write path, and the well-formed sibling on
        // the SAME page still landed — which is exactly what per-row degradation
        // exists to preserve and what a rolled-back transaction would have lost.
        self::assertSame(1, $scope->dialogsPages, 'precondition: one dialogs page was routed through the real mirror write path');
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
        self::assertSame(0, $exit, 'a per-row value fault is a degradation, not a failed run — discovery must still complete');

        // THE ASSERTION. One row per clue, in the bucket that says why it
        // happened, naming the table whose row was refused.
        $rows = $this->app->make('db')->connection()->table('tf_ingest_clues')
            ->where('account_id', self::ACCOUNT)
            ->get();

        self::assertNotCount(
            0,
            $rows,
            'a backfill page that could not place a fact must leave a row in tf_ingest_clues — '
            .'the warn() lines it printed are not a record',
        );

        $value = $rows->first(static fn ($row): bool => $row->bucket === 'valueClues');

        self::assertNotNull(
            $value,
            'the refused row must be filed under the VALUE bucket — "the value does not fit its column" is a '
            .'column-shape fault, and filing it as a foreign-key fault sends the reader to the wrong graph',
        );
        self::assertSame(
            self::FAULT_TABLE,
            $value->table,
            'the ledger row must name the table whose row was refused, parsed off the clue\'s "{$table}: …" prefix',
        );
        self::assertStringContainsString(
            (string) self::DRIVER_CODE,
            $value->detail,
            'the persisted detail is the clue STRING, so the next reader learns which fault it was',
        );

        // AND THE BUCKETS STAY DISTINCT — the anti-conflation guard. One value
        // fault must not manufacture an FK clue or a modelling gap; a ledger
        // that files one under another sends the next reader to the wrong place.
        self::assertSame(
            0,
            (int) $this->app->make('db')->connection()->table('tf_ingest_clues')
                ->where('account_id', self::ACCOUNT)
                ->where('bucket', 'fkClues')
                ->count(),
            'a value fault is NOT a relationship fault — the buckets must not be conflated',
        );
    }

    /**
     * THE COMMON PATH IS BYTE-FOR-BYTE UNCHANGED.
     *
     * The ledger write is an INSERT, and a test that only ever looks at rows
     * would pass even if it emitted a line, a heading or a warning on every
     * healthy run forever. So the clean path is pinned against the EXACT output
     * `ValueClueReachesOperatorOutputTest` already proved for this command and
     * this page: byte-for-byte, with no added ledger row either.
     */
    public function test_a_clean_backfill_records_no_clue_and_prints_nothing_new(): void
    {
        $this->bind(new DiscoveryScope);

        $exit = Artisan::call('teleframe:backfill', ['--account' => self::ACCOUNT, '--discover' => true]);
        $out = Artisan::output();

        self::assertSame(0, $exit);
        self::assertSame(
            self::CLEAN_OUTPUT."\n",
            $out,
            'with no clue the operator must see EXACTLY the output this tree produced before the ledger call — '
            .'no added line, no added label, no trailing newline',
        );
        self::assertSame(
            0,
            (int) $this->app->make('db')->connection()->table('tf_ingest_clues')
                ->where('account_id', self::ACCOUNT)
                ->count(),
            'a clean page records no clue, so the ledger is a list of defects rather than noise',
        );
    }

    /**
     * The console seams, bound exactly as `ValueClueReachesOperatorOutputTest`
     * binds them. Only the injected fault differs between the two tests above.
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
     * `DB::connection()` inside `MirrorIngesterFactory::buildResultRoute()` —
     * and the ledger write `BackfillCommand::recordClues()` now makes on that
     * same connection — is the connection that reports the MySQL code.
     *
     * The PDO is the one `IngestTestCase` already migrated, so the shipped DDL is
     * present and the rewrite touches only the offending table's inserts.
     */
    private function faultFirstNameInserts(): void
    {
        $manager = $this->app->make('db');
        $pdo = $manager->connection()->getPdo();

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
 * `DiscoveryScope`'s page with EXACTLY ONE field changed: `users[0].first_name`
 * is an OBJECT where the shipped `tf_users_first_name.first_name` column is
 * declared `NOT NULL`.
 *
 * That is the real shape of this fault class — a TL `long` field decoded as an
 * object — and it is what makes the decomposer omit the column so the database
 * genuinely refuses the row. It has to be a real refusal, not an injected one:
 * `MysqlFaultInjectingConnection` actually attempts the insert first and only
 * rewrites a failure it really got, so a well-formed `first_name` would insert
 * cleanly and there would be no fault to classify.
 *
 * The page is REUSED from `DiscoveryScope` rather than re-typed, exactly as
 * `ValueNotStorableDiscoveryScope` does: a hand-written second copy of a
 * ten-fact fixture is a second thing to keep in sync, and this suite's whole
 * claim is that the failing run and the clean run differ by the fault alone.
 * The class is declared here rather than imported from
 * `ValueClueReachesOperatorOutputTest`'s file because that file's copy is
 * private to it and this lane does not own it.
 */
final class ObjectFirstNameDiscoveryScope extends FakeUserScope
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
     * @return array<string, mixed>
     */
    public function page(): array
    {
        $page = (new DiscoveryScope)->page();
        $page['users'][0]['first_name'] = ['_' => 'accountPassword', 'has_recovery' => true];

        return $page;
    }
}
