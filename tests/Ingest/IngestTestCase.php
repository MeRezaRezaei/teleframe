<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\RunsMigrations;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Base for ingest tests: testbench app (same providers as Schema\TestCase)
 * + sqlite :memory: + migrated P1 truth (dial + entity anchors).
 *
 * ## WHY THERE IS NO `migrate` CALL IN `setUp()`
 *
 * `parent::setUp()` is the whole database setup now. Testbench's
 * `setUpTheTestEnvironmentTraits()` runs `truncateDatabaseTables()` for any
 * class whose trait graph contains `Illuminate\Foundation\Testing\DatabaseTruncation`,
 * and `RunsMigrations` pulls it in — so the dial is migrated once per process
 * and TRUNCATED before each method, without this class calling anything.
 *
 * The method that used to be here re-ran all 61 migration files for each of the
 * 557 test methods in this area (2201s). The fix is NOT "migrate once and roll
 * back a transaction": `UpdateStored` declares `ShouldDispatchAfterCommit`, so a
 * listener queued on a transaction that never commits never runs and its writes
 * are rolled back — that attempt broke 126 of 557 tests. See
 * `tests/Ingest/IngestDatabaseIsolationTest.php`, which pins both halves.
 */
abstract class IngestTestCase extends TestCase
{
    use RunsMigrations;

    protected function getEnvironmentSetUp($app): void
    {
        $this->defineIngestDatabase($app);
    }

    protected function setUp(): void
    {
        // Runs the truncation hook: migrate once, then truncate. Nothing here
        // opens a transaction, which is what keeps after-commit listeners
        // running.
        parent::setUp();

        // AFTER the truncation, never before: `telegram_accounts` is one of the
        // truncated tables, so seeding first would insert into an empty table
        // that the truncation is about to wipe.
        $this->seedIngestTenant(...$this->ingestTenantIds());
    }

}
