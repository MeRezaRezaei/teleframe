<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Tests\Concerns\RunsMysqlMigrations;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * MySQL track base: the SHIPPED curated dial, executed on real MySQL/InnoDB,
 * with foreign-key enforcement read back off the engine rather than assumed.
 *
 * ## WHY THIS IS NOT RelationConstraintTestCase WITH A DIFFERENT DSN
 *
 * `RelationConstraintTestCase::enforcingConnection()` solves a real problem —
 * SQLite ships `PRAGMA foreign_keys` OFF, so a negative assertion written
 * against it is vacuous — and its read-back assertion is the right pattern. But
 * it proves the schema is honoured by *SQLite*. This class proves the schema is
 * *accepted* by MySQL at all, which is a strictly earlier question:
 *
 *   MySQL 8.4 REJECTS a migration outright for error 3780 (signedness
 *   mismatch on a FK), 6125 (parent key not unique), 1059 (constraint name
 *   over 64 chars), 1170 (TEXT in a PRIMARY KEY), 1101 (TEXT NOT NULL
 *   DEFAULT ''). No amount of pragma-juggling reaches those; the migrate()
 *   call fails and there is no schema to assert about.
 *
 * So the ordering here is: migrate FIRST, assert it succeeded, and only then
 * assert enforcement. A class that asserted enforcement on a connection whose
 * migration had been silently skipped would be proving nothing.
 *
 * ## WHY THE CONNECTION IS MEMOISED PER TEST
 *
 * The plan's version of this class re-executes the whole dial on every call.
 * That is not merely wasteful — it is wrong. `CuratedDialMigratesOnMysqlTest`
 * calls `migratedMysqlConnection()` and then `mysqlTableNames()`, and the
 * latter migrates AGAIN on a database that already has every table, so
 * `Schema::create('tf_updates')` raises MySQL error 223 (base table or view
 * already exists) and the test fails for a reason that has nothing to do with
 * MySQL acceptance of the dial.
 *
 * Memoising per test is also the honest reading of the method's contract: it
 * promises a connection CARRYING the shipped dial, executed, with enforcement
 * observed. One execution per test satisfies that; the second execution cannot
 * add evidence and can only contradict the first. The memo is per test
 * instance, so `setUp()`'s drop-and-create still gives every test method its
 * own database. See `.brain/decisions/D-016-mysql-dial-memoised-per-test.md`.
 */
abstract class MySqlTestCase extends TestCase
{
    use RunsMysqlMigrations;

    private mixed $previousContainer = null;

    /** The one migrated connection for THIS test; null until first use. */
    private ?Connection $migrated = null;

    /**
     * The one migrated connection for the whole PROCESS.
     *
     * ## WHY THIS IS STATIC AND WHY THAT IS NOT A LEAK
     *
     * The dial costs 100+ seconds to install (MEASURED: 7.8s drop+create plus
     * 102-107s for 53 migrations, of which `2026_10_04_000700` alone is 38-43s).
     * Paying that per TEST made the track unschedulable — 41 test methods at
     * that rate is well over an hour, which is why the CI job kept dying on its
     * own timeout. Paying it once per PROCESS is correct because the schema is
     * the expensive artefact and the DATA is the cheap one, and
     * `RunsMysqlMigrations::resetMysqlData()` restores the data for free
     * between tests.
     *
     * A static connection is normally a smell, so the lifetime is stated rather
     * than left to chance: it is bound to the fingerprint of the dial that built
     * it, and `tearDown()` drops the reference whenever the fingerprint no
     * longer matches. A changed migration set therefore cannot be served by the
     * old connection — it is discarded and the next test rebuilds.
     */
    private static ?Connection $processMigrated = null;

    private static ?string $processFingerprint = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::mysqlTrackEnabled()) {
            self::markTestSkipped(
                'MySQL track: set TELEFRAME_MYSQL=1 (with TELEFRAME_MYSQL_* connection env) '
                .'or expose a MySQL 8.4 server answering on the configured host to run it',
            );
        }

        $this->previousContainer = Container::getInstance();
        $this->prepareMysqlSchemaForTest();
    }

    protected function tearDown(): void
    {
        // Never hand the next test a connection carrying a schema this code no
        // longer describes. See the property's docblock.
        if (self::$processFingerprint !== null && self::$processFingerprint !== static::mysqlDialFingerprint()) {
            self::$processMigrated = null;
            self::$processFingerprint = null;
        }

        Container::setInstance($this->previousContainer);
        Facade::clearResolvedInstances();
        $this->migrated = null;
        parent::tearDown();
    }

    /**
     * Migrate the shipped dial onto a fresh MySQL database and return the
     * connection, with foreign-key enforcement asserted ON THE ENGINE.
     *
     * The migration set is `UpdateIngestor::migrationPaths()` — the same list
     * the ingest seam runs and the PG lane runs. Never glob
     * src/Laravel/Migrations here: that would be a second copy of the dial, and
     * a second copy drifts.
     */
    final protected function migratedMysqlConnection(): Connection
    {
        if ($this->migrated instanceof Connection) {
            return $this->migrated;
        }

        $fingerprint = static::mysqlDialFingerprint();

        // The process already installed this exact dial: reuse the connection.
        // The schema is intact because setUp() reset the DATA, and the
        // fingerprint check means a changed dial never reaches this branch.
        if (self::$processMigrated instanceof Connection && self::$processFingerprint === $fingerprint) {
            return $this->migrated = self::$processMigrated;
        }

        $cfg = static::mysqlConnectionConfig();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $cfg['host'],
            'port' => $cfg['port'],
            'database' => $cfg['database'],
            'username' => $cfg['username'],
            'password' => $cfg['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ], 'mysql');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // The connection is NAMED 'mysql', but 18 of the 41 curated migrations
        // reach for the `DB` facade (`DB::raw("('')")` in
        // 2026_09_14_200001 alone), and the facade resolves the manager's
        // DEFAULT connection. Without this line the dial dies at the first
        // `DB::raw` with "Database connection [default] not configured" —
        // measured, not anticipated.
        $capsule->getDatabaseManager()->setDefaultConnection('mysql');

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection('mysql')->getSchemaBuilder());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        // Run the shipped migrations through the framework, in apply order.
        //
        // ONLY when the sentinel does not already prove this exact dial is
        // installed. A warm DATABASE with no in-process connection — the state
        // a second `phpunit` run finds — must attach to the existing schema, not
        // migrate on top of it: re-running `Schema::create` over tables that
        // already exist raises MySQL 223, which is the exact failure this
        // method's own history records. Measured on that state before this
        // branch existed: 3 errors in 0.60s, all "base table already exists".
        //
        // The FINGERPRINT is the right test here, deliberately, and not the full
        // constraint-set digest: `setUp()` has already repaired anything a test
        // left behind by then, so the only question left is whether the dial
        // itself changed. See `prepareMysqlSchemaForTest()` for the ordering.
        if (! static::mysqlDialIsInstalled()) {
            foreach (static::mysqlMigrationFiles() as $file) {
                $migration = require $this->absolutePathFor($file);
                Assert::assertInstanceOf(
                    Migration::class,
                    $migration,
                    basename($file).' did not return a Migration',
                );
                $migration->up();
            }

            $db = $capsule->getConnection('mysql');

            // The dial is installed. Record the fingerprint that lets the NEXT
            // test — and the NEXT process — skip the rebuild.
            $this->stampMysqlDialSentinel();
        } else {
            $db = $capsule->getConnection('mysql');
        }

        // THE ASSERTION THAT MAKES THIS CLASS WORTH ITS EXISTENCE. Read the
        // enforcement state off the engine. MySQL/InnoDB reports it in
        // information_schema, so if this reads 0 every negative assertion
        // beneath it is the vacuous green this class was written to prevent.
        //
        // It is asserted on the REUSED path too, deliberately: attaching to an
        // existing schema is exactly the situation in which a session could have
        // been altered, and a reused connection is the one a test would
        // otherwise trust without looking.
        $reported = $db->selectOne(
            'SELECT @@SESSION.foreign_key_checks AS enabled, @@SESSION.transaction_isolation AS isolation',
        );
        Assert::assertSame(
            1,
            (int) ($reported->enabled ?? 0),
            'MySQL foreign_key_checks is OFF on this session. InnoDB defaults it to 1 for a '
            .'normal client connection, so this reading means the session was altered upstream — '
            .'and every orphan-refusal assertion in the MySQL track would be green for the wrong '
            .'reason until it is fixed.',
        );

        // Bind the connection to the fingerprint that describes its schema, so a
        // changed dial can never be served from this one.
        self::$processFingerprint = $fingerprint;

        return $this->migrated = self::$processMigrated = $db;
    }

    /**
     * Resolve ONE repository-relative path from `mysqlMigrationFiles()` back to
     * an absolute, readable file, and refuse anything else LOUDLY.
     *
     * WHY THIS EXISTS — `UpdateIngestor::migrationPaths()` is deliberately
     * MIXED. Measured on this tree: it returns one DIRECTORY
     * (`src/Laravel/Migrations`) plus single FILES
     * (`2026_09_14_200030_create_tf_updates_tables.php` and
     * `2026_09_14_200031_create_tf_channel_participants_tables.php`).
     *
     * The obvious `glob($path)` mishandles BOTH shapes, in opposite
     * directions, and both failures are invisible if you do not look:
     *
     *   - `glob()` on a directory returns `[the directory itself]`, so
     *     `require`ing it dies with "Failed opening required .../
     *     src/Laravel/Migrations" — the loud one.
     *   - `glob()` on a plain FILE returns `[]`, so the two entity anchors are
     *     SILENTLY DROPPED. A suite that reports "the whole shipped dial
     *     migrated on MySQL" while never running two of its migrations is the
     *     exact vacuous-green this class exists to prevent, and nothing in the
     *     assertions would ever notice.
     *
     * `mysqlMigrationFiles()` therefore distinguishes the two shapes itself, and
     * this method is the second gate: a path that is not a readable PHP file is
     * a loud failure, because a silently-ignored migration path is worse than a
     * red suite. The path arrives ABSOLUTE from `mysqlMigrationFiles()`; nothing
     * here re-derives it from the repository root, because
     * `migrationPaths()` is itself absolute and the two roots are not
     * guaranteed to be the same directory.
     */
    private function absolutePathFor(string $path): string
    {
        Assert::assertFileIsReadable(
            $path,
            "the shipped dial yielded '{$path}', which is not a readable migration file — "
            .'it would have been skipped silently.',
        );

        return $path;
    }

    /**
     * Every table the DIAL created, from information_schema.
     *
     * The harness's own fingerprint sentinel is excluded, and deliberately so:
     * this method is a claim about the shipped schema, and a table this harness
     * created for its own bookkeeping must never be counted as part of it. The
     * exclusion is stated rather than left implicit, because the alternative is
     * a future test that reads a table count and silently includes a fixture.
     */
    final protected function mysqlTableNames(): array
    {
        $rows = $this->migratedMysqlConnection()->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES '
            ."WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME NOT IN ('"
            .self::SENTINEL_TABLE_NAME."','".self::SENTINEL_FK_TABLE_NAME."')",
        );

        return array_map(static fn (object $r): string => (string) $r->name, $rows);
    }
}
