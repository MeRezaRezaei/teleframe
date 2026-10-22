<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;

/**
 * Boots the ingest database surface for logic tests (plan Task 1):
 * sqlite :memory: connection + the shipped curated dial (provider-registered
 * migrations/) migrated together with the off-dial root-namespace entity
 * anchors the ingest surface writes to (User first; grows with the walker).
 *
 * ## ISOLATION IS TRUNCATION, NOT ROLLBACK
 *
 * The dial is 61 migration files and used to be re-run from `setUp()` on every
 * one of `tests/Ingest`'s 557 test methods — the 2201s. Migrating once and
 * wrapping each test in a transaction is the obvious next step and it is
 * WRONG: `UpdateStored` declares `ShouldDispatchAfterCommit`, so a listener
 * queued on a transaction that never commits never runs, and its writes are
 * rolled back with the row. That attempt broke 126 of 557 tests.
 *
 * So this trait uses `Illuminate\Foundation\Testing\DatabaseTruncation`:
 * migrate ONCE per process, TRUNCATE between tests, and enclose nothing in a
 * transaction. After-commit listeners therefore fire exactly as they do in
 * production, and their writes are durable for the rest of the test.
 * `tests/Ingest/IngestDatabaseIsolationTest.php` pins both halves.
 */
trait RunsMigrations
{
    use DatabaseTruncation;

    /**
     * The migrated `:memory:` database this process is running tests against,
     * and the connection config it was migrated from.
     *
     * `DatabaseTruncation` decides "have I migrated yet?" from the framework's
     * process-global `RefreshDatabaseState::$migrated`, and it has no handling
     * for in-memory databases at all: it assumes the tables outlive the
     * connection, which is true of a file or a real server and false of
     * `:memory:`. Both assumptions are wrong here. `$migrated` is shared with
     * `RefreshDatabase`, so in a full-suite run it can already be true against
     * a database this trait never migrated, and `:memory:` gets a brand new
     * empty database for every application instance. Hence the local statics,
     * keyed by the connection config so a subclass that overrides
     * `defineIngestDatabase()` (a real sqlite FILE, as `tests/Bus` does) is
     * never handed another connection's data.
     *
     * @var array{config: string, pdo: \PDO}|null
     */
    private static ?array $migratedIngestDatabase = null;

    /**
     * MIGRATE-ONCE / TRUNCATE-EVERY-TIME, replacing the per-method migrate.
     *
     * Testbench calls this from `parent::setUp()` — its
     * `setUpTheTestEnvironmentTraits()` runs `truncateDatabaseTables()` for any
     * class whose trait graph contains `DatabaseTruncation`
     * (`class_uses_recursive`), so nothing here needs to call it by hand.
     *
     * Overridden rather than inherited because the framework's own first-run
     * branch re-runs `migrate:fresh` whenever its global flag is unset, which is
     * the 2201s again (a stale flag, or a flag another suite set).
     */
    protected function truncateDatabaseTables(): void
    {
        $this->beforeTruncatingDatabase();

        if ($this->restoreMigratedIngestDatabase()) {
            $this->truncateTablesForAllConnections();
        } else {
            $this->freshIngestSurface();
        }

        $this->afterTruncatingDatabase();
    }

    /**
     * Hand this method the already-migrated database, if there is one to hand.
     *
     * Returns false when there is nothing reusable, or when the cached database
     * no longer holds the dial (a PDO can outlive the schema that was built on
     * it) — in which case the caller migrates again.
     */
    private function restoreMigratedIngestDatabase(): bool
    {
        $cached = self::$migratedIngestDatabase;

        if ($cached === null
            || ! $this->ingestDatabaseIsInMemory()
            || $cached['config'] !== $this->ingestConnectionConfig()) {
            return false;
        }

        DB::connection('ingest')->setPdo($cached['pdo']);

        // A `migrations` TABLE is not a migrated database. The question has always
        // been the wrong one, and the probe proves the gap: at the moment this
        // guard runs, `ingest.migrations` exists and holds ZERO rows —
        //
        //     BEFORE-truncate pre rows=0
        //     AFTER-truncate  rows=0  pdo=1735
        //
        // so `hasTable()` answered true and this method handed back a database
        // that records no migrations at all. A later test that reads the ledger
        // then reports the truth about a database that was never populated —
        // MEASURED 2026-10-18 (goal 46.87), where
        // `RelationIntegrityTest::test_migration_is_in_the_curated_ship_dial`
        // failed on CI shard-3 and passed alone:
        //
        //     ===PROBE conn=ingest db=:memory: rows=0 hasFk=NO===
        //
        // The empty ledger is why it was order-dependent: whatever populated it
        // is gone by the time a second class reuses the cached PDO, and the reuse
        // is exactly what let the emptiness survive instead of forcing a
        // re-migrate.
        //
        // So the guard asks the question it means: does the ledger carry ROWS.
        try {
            $connection = DB::connection('ingest');
            $migrated = $connection->getSchemaBuilder()->hasTable('migrations')
                && $connection->table('migrations')->count() > 0;
        } catch (\Throwable) {
            $migrated = false;
        }

        if ($migrated) {
            return true;
        }

        // Nothing usable to hand back: drop the cache so the caller's else-branch
        // re-migrates for real, rather than re-using this same empty ledger.
        self::$migratedIngestDatabase = null;

        return false;
    }

    /**
     * Wipe and re-migrate the dial, then keep the database for the next method.
     *
     * `migrate:fresh` rather than `migrate` because a connection that is not
     * `:memory:` (a real sqlite FILE, which `tests/Bus` overrides in) can carry
     * tables and rows from a previous method, and the ledger alone would not
     * make that state disappear.
     *
     * ## WHY A NON-IN-MEMORY CONNECTION IS NEVER CACHED
     *
     * Those overrides `unlink()` their file in `setUp()` and rely on getting a
     * fresh one each method; handing them the previous method's PDO would point
     * them at an unlinked inode. Two files in the suite pay one `migrate:fresh`
     * per method for that, which is what they cost today, and every other test
     * in the area gets the fast path.
     */
    private function freshIngestSurface(): void
    {
        $this->artisan('migrate:fresh', array_merge($this->migrateFreshUsing(), [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]));

        $this->app[Kernel::class]->setArtisan(null);

        if ($this->ingestDatabaseIsInMemory()) {
            self::$migratedIngestDatabase = [
                'config' => $this->ingestConnectionConfig(),
                'pdo' => DB::connection('ingest')->getPdo(),
            ];
        }
    }

    private function ingestDatabaseIsInMemory(): bool
    {
        return config('database.connections.ingest.database') === ':memory:';
    }

    /**
     * Everything that decides WHICH database this is, so a cached PDO is only
     * ever handed back to a connection that asked for the same one.
     */
    private function ingestConnectionConfig(): string
    {
        $config = config('database.connections.ingest');

        return (string) json_encode([
            'driver' => $config['driver'] ?? null,
            'database' => $config['database'] ?? null,
            'prefix' => $config['prefix'] ?? null,
            'paths' => UpdateIngestor::migrationPaths(),
        ]);
    }

    /**
     * SQLite :memory: for logic tests — plan Tech Stack: PG-only paths
     * (deferrable FKs, uuid native) are exercised by the CI/live gate.
     *
     * ## `foreign_key_constraints => true` IS LOAD-BEARING, NOT A TIDY-UP
     *
     * `Illuminate\Database\Connectors\SQLiteConnector::configureForeignKeyConstraints()`
     * opens with `if (! isset($config['foreign_key_constraints'])) { return; }`
     * — a bare early-out. SQLite's own default is `PRAGMA foreign_keys = 0`,
     * so omitting this key leaves enforcement OFF: no exception, no warning,
     * no log line. Every "no FK clue" reading anywhere in this suite was
     * therefore a reading about a database that was not checking, which is
     * exactly the failure mode the operator named — "it can be used as a sign
     * of failure" — applied to the test suite itself.
     *
     * The key is asserted ON, not merely set: see
     * `tests/Ingest/IngestFkEnforcementIsOnTest.php`. A config key nobody
     * reads is a comment, and this defect already survived once.
     *
     * @param  Application  $app
     */
    protected function defineIngestDatabase($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * Migrate the shipped curated dial (112 migrations) plus
     * UpdateIngestor's entity anchors (off-dial root-namespace tables) in
     * one pass. `--path` replaces registered package paths, so the dial
     * dir is part of the path list.
     */
    protected function migrateIngestSurface(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ]);
    }

    /**
     * The tenant ids this test writes under, for `seedIngestTenant()`.
     *
     * ## WHY THIS IS A DECLARATION AND NOT A BLANKET AUTO-SEED
     *
     * The obvious alternative — seed whatever account id the test happens to
     * mention, discovered by scanning its own source — would make tenancy
     * something the harness infers instead of something the test states, and
     * would silently rob any test that means to plant an ORPHAN of the ability
     * to do so. A test that writes under an id it did not declare here fails
     * loudly, which is the behaviour worth having now that the database is
     * actually enforcing.
     *
     * The default is the empty list, so a test that touches no tenant FK needs
     * no change — and a test that starts needing one says so.
     *
     * @return list<int>
     */
    protected function ingestTenantIds(): array
    {
        return [];
    }

    /**
     * Plant the parent row every tenant FK points at, for `$userId`.
     *
     * ## WHY A TEST THAT INGESTS MUST CALL THIS
     *
     * Since `2026_10_04_000700` every tenant-bearing `tf_*` table carries a
     * foreign key to `telegram_accounts`, and the key is `user_id` — the
     * TELEGRAM user id — NOT the vault row's own `id`. That is settled by
     * `docs/decisions.md` D-022 (and D-005 as cited by the migration that
     * shipped the constraint) and stated by the shipped code itself:
     * `MirrorIngesterFactory.php:160` writes the caller's Telegram id straight
     * into `account_id`, and `TeleframeClient::resolveVaultAccount()` resolves
     * a numeric reference by `$vault->accountByUserId($userId)`. So in
     * production the parent row ALWAYS exists first: `VaultAddAccountCommand`
     * writes `user_id` when the account is added, long before any update is
     * ingested. A test that ingests under an account id with no such row is
     * describing a state the ingest surface cannot reach.
     *
     * ## WHY IT IS A CALL AND NOT A BLANKET AUTO-SEED
     *
     * Seeding every account id a test might mention, without being asked, would
     * quietly remove the need to think about tenancy — and the tests that DO
     * assert an orphan is refused would stop being able to plant one. Each test
     * declares the tenants it means to use, so a test that writes under an
     * unplanted id fails loudly instead of being rescued.
     *
     * Idempotent per id, because several tests use two tenants and calling it
     * twice for one of them must not violate `telegram_accounts_user_id_unique`.
     *
     * Synthetic values only — no session string, no bot token.
     */
    /**
     * Plant a PEER the way the mirror writes it: the entity row first, then its
     * access hash, because `tf_users_access_hash (account_id, id) -> tf_users` and
     * `tf_chats_access_hash (account_id, id) -> tf_chats` are composite foreign keys
     * and the hash cannot exist without the entity it hashes.
     *
     * Added because fixtures across tests/Console had been growing these rows by
     * hand, and each one silently skipped a parent until foreign-key enforcement
     * was switched on — at which point the test failed on its own fixture rather
     * than on the behaviour it exists to prove. Centralising the PAIR means the next
     * fixture cannot get only half of it, which is exactly the failure that happened.
     *
     * $accessHash is int|string because a Telegram access_hash is a 64-bit value
     * that does not always fit a PHP int and may be negative on the wire; the
     * column is a string and the fixtures must be able to express that.
     *
     * Synthetic values only — no session string, no real access hash.
     */
    protected function seedIngestPeer(int $accountId, int $id, string $kind = 'chat', int|string $accessHash = 5512378901644): void
    {
        $entity = $kind === 'user' ? 'tf_users' : 'tf_chats';
        $hashes = $kind === 'user' ? 'tf_users_access_hash' : 'tf_chats_access_hash';

        if (! DB::table($entity)->where('account_id', $accountId)->where('id', $id)->exists()) {
            DB::table($entity)->insert([
                'account_id' => $accountId,
                'id' => $id,
                'constructor' => $kind,
            ]);
        }

        DB::table($hashes)->insert([
            'account_id' => $accountId,
            'id' => $id,
            'access_hash' => $accessHash,
        ]);
    }

    protected function seedIngestTenant(int ...$userIds): void
    {
        foreach ($userIds as $userId) {
            $exists = DB::table('telegram_accounts')->where('user_id', $userId)->exists();

            if ($exists) {
                continue;
            }

            DB::table('telegram_accounts')->insert([
                'user_id' => $userId,
                'label' => 'ingest-fixture-'.$userId,
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
    }
}
