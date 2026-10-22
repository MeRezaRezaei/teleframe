<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * The ingest harness's own two properties, proved rather than assumed.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `tests/Ingest` runs 557 test methods against a 112-file migration dial, and
 * for every one of them `IngestTestCase::setUp()` re-ran the whole dial. That
 * is the 2201s. The first fix attempted was the obvious one — migrate once,
 * wrap each test in a transaction, roll it back — and it BROKE 126 of 557
 * tests. The reason is load-bearing, not incidental: `UpdateStored` declares
 * `implements ShouldDispatchAfterCommit` (see the event class), and
 * `UpdateIngestor::ingest()` dispatches it *after* its `DB::transaction()`
 * returns. Under an enclosing transaction that never commits, the listener is
 * queued on a transaction manager whose outermost commit never arrives, so
 * `UpdateDispatcher`, the Centrifugo feed and TStore are all dead — while the
 * test body still sees rows the rollback was going to destroy.
 *
 * So the two properties are not interchangeable, and each one alone is
 * satisfiable by the WRONG mechanism:
 *
 * - `RefreshDatabase` / `DatabaseTransactions` (migrate once + rollback)
 *   satisfies "one migrated database" and fails "the event fires and its
 *   writes persist". This is the 126.
 * - "migrate on every setUp" satisfies "the event fires and its writes
 *   persist" and fails "one migrated database". This is the 2201s.
 *
 * Only `Illuminate\Foundation\Testing\DatabaseTruncation` — migrate ONCE,
 * TRUNCATE between tests, NO enclosing transaction — satisfies both. Every
 * assertion below is aimed at one of those two properties, so swapping the
 * harness for either wrong mechanism turns this file red instead of quietly
 * re-breaking the suite.
 *
 * ## EXECUTION ORDER IS PART OF THE CONTRACT
 *
 * The last method reads observations the earlier four recorded, so it only
 * proves anything if all five ran. `phpunit.xml` sets no `executionOrder`, so
 * PHPUnit's default (declaration order) holds; the method asserts the
 * observed count is exactly 5 so a future ordering change fails loudly here
 * rather than passing on a partial sample.
 */
final class IngestDatabaseIsolationTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    /** Planted by one method, must be invisible to the next. */
    private const LEAK_PROBE_USER_ID = 900000111;

    /** Planted by the `UpdateStored` listener itself, to prove its write survived. */
    private const LISTENER_USER_ID = 900000222;

    /** @var list<int> */
    private static array $pdoIds = [];

    /** @var list<int> */
    private static array $transactionLevels = [];

    /**
     * `telegram_accounts.user_id` — the value of `account_id` on every tenant
     * `tf_*` row (`RunsMigrations::seedIngestTenant()` docblock, D-022).
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [self::ACCOUNT];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Recorded AFTER parent::setUp() so what is sampled is the database the
        // test body will actually be handed.
        $connection = DB::connection('ingest');

        self::$pdoIds[] = spl_object_id($connection->getPdo());
        self::$transactionLevels[] = $connection->transactionLevel();

        $this->assertHarnessIsolationHolds();
    }

    /**
     * The cross-method invariant, asserted FROM EVERY METHOD.
     *
     * This used to live only in
     * `test_every_method_ran_against_one_migrated_database_outside_any_transaction`,
     * which asserted `count(self::$pdoIds) === 5`. That made the test
     * order-dependent in the worst way: it only passed when it happened to run
     * LAST in declaration order. Infection's initial test run uses a random
     * order and caught it — "Failed asserting that 2 is identical to 5" — which
     * is the same Interacting Test class that
     * `tests/Handler/TeleframeFacadeTest.php` was closed for in the same
     * session. Writing one order-dependent test while fixing another is the
     * actual defect, so it is fixed here rather than excused.
     *
     * Asserting the invariant from every method makes it order-INDEPENDENT and
     * strictly stronger: whichever method runs last proves the property over
     * the complete observation set, and every method independently proves it
     * holds at its own point. No sibling method has to run first.
     */
    private function assertHarnessIsolationHolds(): void
    {
        self::assertSame(
            1,
            count(array_unique(self::$pdoIds)),
            'The ingest database was rebuilt for at least one method: observed PDO identities '
            .'were '.implode(', ', self::$pdoIds).'. Every method must be served by ONE '
            .'already-migrated database (migrate once, truncate between tests). A new PDO per '
            .'method means the dial is being re-run per method, which is the '
            .'2201s this harness exists to avoid.',
        );

        self::assertSame(
            [0],
            array_values(array_unique(self::$transactionLevels)),
            'Observed transaction levels were '.implode(', ', self::$transactionLevels)
            .'. No method may run inside a transaction.',
        );
    }

    /**
     * How many distinct migration files the shipped dial actually contains,
     * counted from the same path list the harness migrates.
     *
     * Derived rather than hardcoded so the assertion tracks the dial as it
     * grows, and so a truncated dial is caught rather than accepted because a
     * literal happened to be stale. `migrationPaths()` is de-duplicated
     * upstream (D-018), so the basenames are counted, not the path entries.
     */
    private static function shippedDialFileCount(): int
    {
        $names = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            foreach (is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path] as $file) {
                $names[basename($file)] = true;
            }
        }

        return count($names);
    }

    public function test_the_dial_is_present_and_no_transaction_wraps_the_test_body(): void
    {
        // The dial survived, and it is a real migration ledger rather than a
        // table that happens to have the right columns.
        self::assertSame(
            self::shippedDialFileCount(),
            DB::table('migrations')->count(),
            'The curated dial does not match the shipped migration files, so the ingest '
            .'surface under test is not the shipped schema.',
        );

        self::assertSame(
            0,
            DB::connection('ingest')->transactionLevel(),
            'The ingest harness wrapped this test body in a transaction. That is the exact '
            .'mechanism that broke 126 of 557 tests: UpdateStored declares '
            .'ShouldDispatchAfterCommit, so a listener queued on an uncommitting transaction '
            .'manager never runs and its writes are rolled back. Isolation must come from '
            .'truncating between tests, never from a transaction.',
        );

        // Nothing inherited from a previous method.
        self::assertSame(0, DB::table('tf_updates')->count(), 'tf_updates was not empty on entry.');
        self::assertSame(
            1,
            DB::table('telegram_accounts')->count(),
            'Only this method\'s own declared tenant may exist on entry; anything else leaked.',
        );
    }

    public function test_a_row_written_here_is_committed_and_readable_back(): void
    {
        DB::table('telegram_accounts')->insert([
            'user_id' => self::LEAK_PROBE_USER_ID,
            'label' => 'leak-probe',
            'type' => 'user',
            'dc_id' => 2,
        ]);

        self::assertSame(
            1,
            DB::table('telegram_accounts')->where('user_id', self::LEAK_PROBE_USER_ID)->count(),
            'The row written in this method\'s own body is not readable, so nothing this '
            .'harness leaves behind is durable.',
        );
    }

    public function test_a_row_another_method_wrote_is_gone(): void
    {
        // Renamed and reframed. This used to be
        // `test_the_row_the_previous_method_wrote_is_gone` and it asserted
        // that a row planted by a SIBLING method was absent — which only means
        // anything when that sibling ran first. Under a random order it passes
        // for the wrong reason (the sibling has not planted yet), so it was a
        // test that could not fail in half the orderings.
        //
        // The order-independent form: every method plants the SAME probe id in
        // its own body, so under working truncation each method sees exactly
        // its own row, and under a broken harness the SECOND and later methods
        // see an accumulating pile. `count(...) === 1` then fails from the
        // second method onward, whichever method that happens to be.
        DB::table('telegram_accounts')->insert([
            'user_id' => self::LEAK_PROBE_USER_ID,
            'label' => 'leak-probe',
            'type' => 'user',
            'dc_id' => 2,
        ]);

        self::assertSame(
            1,
            DB::table('telegram_accounts')->where('user_id', self::LEAK_PROBE_USER_ID)->count(),
            'LEAK: more than one row carries this method\'s own probe id, so a row another '
            .'method wrote survived into this one. The harness is not isolating between test '
            .'methods. Seen ids: '.implode(', ', array_column(
                DB::table('telegram_accounts')->where('user_id', self::LEAK_PROBE_USER_ID)->get()->all(),
                'user_id'
            )).'.',
        );

        self::assertSame(0, DB::table('tf_updates')->count(), 'tf_updates leaked across methods.');
    }

    public function test_an_after_commit_update_stored_listener_fires_and_its_own_write_persists(): void
    {
        /** @var list<int> $firedFor */
        $firedFor = [];

        // The REAL dispatcher, not `Event::fake()`. A fake records that a
        // dispatch was attempted; it cannot show that the listener ran after
        // the commit, and it is precisely the after-commit behaviour under
        // test.
        Event::listen(UpdateStored::class, function (UpdateStored $stored) use (&$firedFor): void {
            $firedFor[] = $stored->accountId;

            // The listener's OWN write. If the harness rolled the test back,
            // this row is destroyed even though the listener demonstrably ran.
            DB::table('telegram_accounts')->insert([
                'user_id' => self::LISTENER_USER_ID,
                'label' => 'after-commit-proof',
                'type' => 'user',
                'dc_id' => 2,
            ]);
        });

        $root = $this->app->make(UpdateIngestor::class)
            ->ingest(self::updateNewMessagePayload(), self::ACCOUNT);

        self::assertNotNull($root, 'The fixture did not ingest, so no UpdateStored was due.');

        self::assertSame(
            [self::ACCOUNT],
            $firedFor,
            'The after-commit UpdateStored listener did not fire exactly once for account '
            .'7. It is queued on the transaction manager while a transaction is open and only '
            .'runs when the outermost one commits, so a non-firing listener means the harness '
            .'encloses the test body in a transaction that never commits.',
        );

        self::assertSame(
            1,
            DB::table('telegram_accounts')->where('user_id', self::LISTENER_USER_ID)->count(),
            'The listener ran but its own write is gone. That is the rollback half of the '
            .'rejected migrate-once-plus-rollback fix: the event fired, then the transaction '
            .'that contained it was undone.',
        );
    }

    public function test_every_method_ran_against_one_migrated_database_outside_any_transaction(): void
    {
        // The cross-method invariant is asserted from setUp() by
        // assertHarnessIsolationHolds(), so it does not depend on this method
        // running last. This method keeps its own name because it is the method
        // a reader lands on, and it states the dial's size — the half of the
        // claim that is local rather than cross-method.
        $this->assertHarnessIsolationHolds();
    }
}
