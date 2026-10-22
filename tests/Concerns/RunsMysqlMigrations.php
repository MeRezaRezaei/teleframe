<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Concerns;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PDO;
use RuntimeException;

/**
 * MySQL track plumbing: real connection config, a disposable DATABASE per test
 * class, and the FULL curated dial.
 *
 * ## WHY THIS EXISTS — the gap it closes
 *
 * Every foreign-key claim in this repository, until this trait, was proven on
 * SQLite with `PRAGMA foreign_keys = ON`. That pragma is real, and
 * `tests/Schema/Mirror/RelationConstraintTestCase.php` is right that it must be
 * read back. But SQLite is not the engine that will hold the data, and the two
 * disagree in ways a pragma cannot arbitrate:
 *
 *   - error 3780 — referencing and referenced columns differ in signedness.
 *     SQLite does not model column signedness; MySQL refuses the constraint.
 *   - error 6125 / 1059 — a missing unique key on the parent, or an
 *     auto-generated constraint name above 64 characters.
 *   - error 1170 — a TEXT/BLOB column inside a PRIMARY KEY.
 *   - error 1101 — TEXT NOT NULL DEFAULT ''.
 *
 * All four are catalogued in `tests/Schema/MySqlDialPortabilityTest.php`, and
 * that suite exists precisely because they are "untestable against the default
 * (SQLite) run". It is a SOURCE SCANNER. A source scanner is not a gate: it
 * proves the migration text avoids four known shapes, and proves nothing about
 * whether the resulting schema is accepted by the engine.
 *
 * ## THE DESIGN, and why it defaults to SKIP
 *
 * Mirrors `RunsPostgresMigrations` exactly, including the default-SKIP: a local
 * `composer verify` on a machine without MySQL must stay green, while an opt-in
 * run that cannot reach the database must FAIL rather than skip. A track that
 * silently skips is a track nobody reads.
 *
 * Environment:
 *   TELEFRAME_MYSQL=1              force the track ON
 *   TELEFRAME_MYSQL_HOST           default 127.0.0.1
 *   TELEFRAME_MYSQL_PORT           default 3306
 *   TELEFRAME_MYSQL_DATABASE       default teleframe_mysql_track
 *   TELEFRAME_MYSQL_USER           default teleframe_track
 *   TELEFRAME_MYSQL_PASSWORD       default TrackLocal!2026
 *   TELEFRAME_MYSQL_SKIP           default 0 (set to 1 to skip explicitly)
 *
 * ## WHY THE PASSWORD DEFAULT IS NOT `tracklocalonly`
 *
 * Measured 2026-10-04 on this host: `SHOW VARIABLES LIKE 'validate_password%'`
 * reports `policy=MEDIUM`, `length=8`, `mixed_case_count=1`,
 * `number_count=1`, `special_char_count=1`, so the plan's literal
 * `tracklocalonly` is refused with `ERROR 1819 (HY000)` at CREATE USER. The
 * default is therefore a policy-compliant loopback throwaway. It is still a
 * throwaway on a socket bound to 127.0.0.1, in the same category as the
 * `nf5prooflocal` already committed in bin/prove-mysql-curated-dial.php, and CI
 * never uses it — CI runs as `root` with an empty password. See
 * `.brain/decisions/D-014-mysql-track-scratch-password.md`. No real credential
 * appears here, and `validate_password` was NOT relaxed to make the fixture fit.
 */
trait RunsMysqlMigrations
{
    /** @var array{host:string,port:string,database:string,username:string,password:string}|null */
    protected static ?array $mysqlConfigCache = null;

    /** @return array{host:string,port:string,database:string,username:string,password:string} */
    protected static function mysqlConnectionConfig(): array
    {
        if (self::$mysqlConfigCache !== null) {
            return self::$mysqlConfigCache;
        }

        // WHY THE PASSWORD IS NOT RESOLVED LIKE ITS FOUR NEIGHBOURS.
        //
        // The other four use `?:`, which is correct for them: an empty host, port,
        // database or username is meaningless, so falling back to the local value
        // is the only sane reading. A password is DIFFERENT — an empty password is
        // a REAL, DELIBERATE configuration, and it is exactly what the CI service
        // uses (`mysql:8.4` with MYSQL_ALLOW_EMPTY_PASSWORD=yes gives root no
        // password at all).
        //
        // MEASURED on pipeline 1592, where this bit and produced 56 errors:
        //
        //   PDOException: SQLSTATE[HY000] [1045] Access denied for user
        //   'root'@'2001:db8:1::6' (using password: YES)
        //
        // `getenv()` returns `false` when a variable is UNSET and `''` when it is
        // set to empty. `?:` collapses those two cases together, so CI's
        // `TELEFRAME_MYSQL_PASSWORD: ""` was indistinguishable from "not configured"
        // and was silently replaced by the committed loopback throwaway
        // `TrackLocal!2026`. The harness then presented a local-only credential to a
        // server whose root has none — hence `using password: YES` against an
        // empty-password account.
        //
        // The fix is to distinguish UNSET from EMPTY, which is the only distinction
        // that carries meaning here. `getenv()` returning `false` still selects the
        // local default, so a developer's unconfigured run is unchanged.
        $password = getenv('TELEFRAME_MYSQL_PASSWORD');

        return self::$mysqlConfigCache = [
            'host' => (string) (getenv('TELEFRAME_MYSQL_HOST') ?: '127.0.0.1'),
            'port' => (string) (getenv('TELEFRAME_MYSQL_PORT') ?: '3306'),
            'database' => (string) (getenv('TELEFRAME_MYSQL_DATABASE') ?: 'teleframe_mysql_track'),
            'username' => (string) (getenv('TELEFRAME_MYSQL_USER') ?: 'teleframe_track'),
            'password' => $password === false ? 'TrackLocal!2026' : $password,
        ];
    }

    protected static function mysqlDsn(?string $database = null): string
    {
        $cfg = self::mysqlConnectionConfig();

        return sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $cfg['host'],
            $cfg['port'],
            $database ?? $cfg['database'],
        );
    }

    /** Fresh PDO outside any testbench app — database lifecycle only. */
    protected static function mysqlLifecyclePdo(): PDO
    {
        $cfg = self::mysqlConnectionConfig();

        // No dbname: a connection whose database was just dropped cannot drop it.
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $cfg['host'], $cfg['port']);

        return new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    /**
     * TRUE when the MySQL lane must run.
     *
     * Forced by TELEFRAME_MYSQL=1 — an unreachable database under a forced run
     * is a FAILURE, never a skip, because an opt-in run that silently passes
     * proves nothing. Otherwise the track runs only when the configured server
     * actually answers.
     */
    protected static function mysqlTrackEnabled(): bool
    {
        if ((string) (getenv('TELEFRAME_MYSQL_SKIP') ?: '0') === '1') {
            return false;
        }

        if ((string) (getenv('TELEFRAME_MYSQL') ?: '') === '1') {
            return true;
        }

        try {
            self::mysqlLifecyclePdo()->query('SELECT 1');

            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Drop and recreate the scratch database, so a class never inherits
     * another class's tables. MySQL has no per-class schema the way Postgres
     * uses search_path, so the database IS the disposable unit.
     */
    protected function dropAndCreateMysqlDatabase(?string $database = null): void
    {
        $database ??= self::mysqlConnectionConfig()['database'];

        // Backtick-escaped and validated: this string reaches DDL.
        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException("refusing to use an unsafe database name: {$database}");
        }

        $pdo = self::mysqlLifecyclePdo();
        $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
        $pdo->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    /**
     * THE SENTINEL TABLE — how the track proves the schema on disk is the dial
     * THIS code expects.
     *
     * The alternative — rebuilding unconditionally — is what made this track
     * cost 100+ seconds per test (MEASURED: `drop+create` 7.8s + all 53
     * migrations 102-107s, and `2026_10_04_000700` alone is 38-43s of it), so
     * 41 tests could not finish inside any sane budget. Reusing the schema
     * makes that cost once per PROCESS instead of once per test.
     *
     * Reuse is only honest if it can be proven stale when it should be, so the
     * sentinel carries a FINGERPRINT of the dial: the migration count and a hash
     * of the sorted migration basenames. Add, remove or rename a migration and
     * the fingerprint changes, the sentinel no longer matches, and the next test
     * drops and rebuilds from scratch. A developer who edits a migration body
     * without renaming it keeps a stale schema — so the fingerprint deliberately
     * hashes the FILE NAMES, which is what decides WHICH migrations run, and the
     * rule for changing a migration body is to give it a new name (the repo's
     * dated-filename convention already requires this).
     *
     * The NAME deliberately does NOT start with `tf_`. `2026_10_04_000700`
     * discovers its tenant tables with a `LIKE 'tf_%'` sweep and pins the count
     * it found, so a harness table named `tf_...` would inflate a count that
     * another migration asserts on. A harness fixture must not be able to move
     * a dial number.
     */
    protected const SENTINEL_TABLE_NAME = 'teleframe_harness_dial_fingerprint';

    /**
     * The dial's constraint set, as rows.
     *
     * A table rather than a column in the sentinel above, because the reset has
     * to compute a set DIFFERENCE ("which live constraints are not the dial's?"),
     * and a hash cannot be differenced. Like the sentinel it does NOT start with
     * `tf_`, so it can never be counted by a migration's `LIKE 'tf_%'` sweep.
     */
    protected const SENTINEL_FK_TABLE_NAME = 'teleframe_harness_dial_fk';

    /**
     * Record a constraint's identity, not just its name.
     *
     * MySQL scopes constraint names to a SCHEMA, not to a table, so a name alone
     * is ambiguous and dropping by name alone would be ambiguous too.
     *
     * The row shape is `array|object` because `mysqlLifecyclePdo()` sets no
     * default fetch mode, so `fetchAll()` hands back arrays — while the test
     * suite's own connections, which do set one, hand back objects. Typing this
     * as `object` alone was a live bug: it raised a TypeError inside the reset,
     * which meant the sentinel was never stamped and every test rebuilt.
     *
     * @param  array<string,mixed>|object  $row
     * @return array{table:string, constraint:string}
     */
    private static function fkIdentity(array|object $row): array
    {
        $get = static function (string $key) use ($row): string {
            if (is_array($row)) {
                return (string) ($row[$key] ?? '');
            }

            return (string) ($row->{$key} ?? '');
        };

        return [
            'table' => $get('t'),
            'constraint' => $get('c'),
        ];
    }

    /**
     * Every FOREIGN KEY constraint currently in the database, as a sorted list
     * of `table|constraint` identities.
     *
     * This is the second half of the staleness check. The dial fingerprint says
     * "the same migrations are installed"; it does NOT say "the schema still
     * looks the way the migrations left it", and a test that mutates the schema
     * makes those two diverge. That is not hypothetical: measured on real MySQL
     * 8.4.11, `PolymorphicRelationsResolveThroughTfPeersOnMysqlTest` adds a
     * constraint named `tf_peers_probe_pts_fk` and never drops it, so with a
     * reused schema every later run died on
     *
     *     PDOException: SQLSTATE[HY000]: General error: 1826
     *       Duplicate foreign key constraint name 'tf_peers_probe_pts_fk'
     *
     * — a failure caused entirely by the harness, reporting a defect that is not
     * in the code under test.
     *
     * @return list<string>
     */
    protected static function mysqlForeignKeyIdentities(?PDO $pdo = null): array
    {
        $pdo ??= self::mysqlLifecyclePdo();

        $rows = $pdo->query(
            'SELECT TABLE_NAME AS t, CONSTRAINT_NAME AS c FROM information_schema.TABLE_CONSTRAINTS '
            ."WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
        )->fetchAll();

        $identities = array_map(self::fkIdentity(...), $rows);
        sort($identities);

        return array_map(
            static fn (array $i): string => $i['table'].'|'.$i['constraint'],
            $identities,
        );
    }

    /**
     * Identity of the dial this code would install: how many migrations, and a
     * hash over their sorted basenames.
     *
     * WHY BASENAMES AND NOT PATHS: the fingerprint answers "is the same SET of
     * migrations installed?", and a migration set is identified by which files
     * run, not by the directory they happen to sit in. Hashing basenames also
     * keeps the fingerprint identical when the same tree is checked out
     * elsewhere — which matters, because an absolute-path hash would make the
     * sentinel from one checkout look stale in another and force a needless
     * rebuild on a perfectly good schema.
     */
    protected static function mysqlDialFingerprint(): string
    {
        $names = array_map(basename(...), self::mysqlMigrationFiles());

        if ($names === []) {
            return 'empty';
        }

        sort($names);

        return count($names).'-'.substr(sha1(implode('|', $names)), 0, 16);
    }

    /**
     * TRUE when the sentinel proves THIS dial is the one installed.
     *
     * This is the cheap half of the staleness question: it compares the dial
     * fingerprint and nothing else. It deliberately does NOT compare the live
     * foreign-key set — see `prepareMysqlSchemaForTest()` for why the ordering
     * of the two checks matters so much.
     *
     * A missing sentinel table, a different fingerprint, or a database that does
     * not answer are all FALSE — never an exception. This is a cache-validity
     * question, and a cache question must never be the thing that fails a run.
     */
    protected static function mysqlDialIsInstalled(?string $database = null): bool
    {
        $database ??= self::mysqlConnectionConfig()['database'];

        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            return false;
        }

        try {
            $pdo = self::mysqlLifecyclePdo();
            $found = $pdo->query(
                'SELECT TABLE_NAME AS name FROM information_schema.TABLES '
                ."WHERE TABLE_SCHEMA = '".addslashes($database)."' AND TABLE_NAME = '".self::SENTINEL_TABLE_NAME."'",
            )->fetchAll(PDO::FETCH_COLUMN);

            if ($found === []) {
                return false;
            }

            $pdo->exec("USE `{$database}`");
            $row = $pdo->query('SELECT fingerprint FROM `'.self::SENTINEL_TABLE_NAME.'` WHERE id = 1')->fetchColumn();

            return is_string($row) && $row === self::mysqlDialFingerprint();
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * The fingerprint of the schema the dial left behind, as recorded then.
     */
    protected static function mysqlRecordedForeignKeyDigest(PDO $pdo): ?string
    {
        $row = $pdo->query('SELECT fk_digest FROM `'.self::SENTINEL_TABLE_NAME.'` WHERE id = 1')->fetchColumn();

        return is_string($row) && $row !== '' ? $row : null;
    }

    /**
     * A stable digest of the foreign keys currently in the database.
     *
     * HASHED rather than stored as a list because the shipped dial installs 477
     * of them (measured) and a 477-line column in a bookkeeping table is a
     * second copy of the schema that nobody will read. The digest changes if one
     * constraint is added or removed, which is the only question asked of it.
     */
    protected static function mysqlForeignKeyDigest(?PDO $pdo = null): string
    {
        return substr(sha1(implode("\n", self::mysqlForeignKeyIdentities($pdo))), 0, 32);
    }

    /**
     * Erase every ROW, keeping the schema, so the next test starts from empty
     * data without paying for 53 migrations again.
     *
     * ## WHY `DELETE` AND NOT `TRUNCATE` — measured, not preferred
     *
     * `TRUNCATE` is the obvious choice and it does not work here. MySQL refuses
     * it with **error 1701** ("Cannot truncate a table referenced in a foreign
     * key constraint") for 265 of the 269 tables in this dial, and it refuses
     * EVEN WITH `FOREIGN_KEY_CHECKS = 0` — that session variable governs DML
     * reference checking, not the DDL-level refusal. Measured on 8.4.11:
     *
     *     TRUNCATE, one statement per table : 25.9s   (fails at the first FK
     *     TRUNCATE, batched 60 per round trip: 24.7s  (same failure)
     *     DELETE,  batched 80 per round trip : 0.23s  (269 tables, x3 runs)
     *
     * So `DELETE` is not a slower fallback here, it is the only thing that works,
     * and it is two orders of magnitude faster. Batched into multi-statement
     * round trips because 269 separate round trips cost 0.2s of pure latency.
     *
     * ## WHY A ROW RESET IS EQUIVALENT TO A FRESH SCHEMA FOR THIS SUITE
     *
     * That equivalence is a claim, so it is checked rather than assumed — and
     * the first version of this claim was WRONG, which is why the second half of
     * this method exists.
     *
     *   - No test keeps state between test methods: there is no
     *     `setUpBeforeClass`, and no test writes a `static` property. Each
     *     method builds its own fixtures.
     *   - No test depends on an AUTO_INCREMENT-assigned id. Only 6 of the 269
     *     tables carry an AUTO_INCREMENT counter at all
     *     (`telegram_accounts`, `telegram_apps`, `tf_backfill_cursors`,
     *     `tf_store_backup_runs`, `tg_update_routing`, `tl_user_bindings`) and
     *     the suite inserts explicit ids throughout. A counter left advanced is
     *     therefore inert here; it is recorded rather than assumed away.
     *
     * ## AND WHY "NO TEST EXECUTES DDL" WAS WRONG
     *
     * A first draft asserted that no test mutates the schema, on the strength of
     * a grep for `Schema::create` / `Schema::drop` / `Schema::table` that came
     * back empty. The grep was too narrow: tests issue raw
     * `ALTER TABLE … ADD CONSTRAINT` through `$db->statement()`, and through
     * `$schema->table(…, fn ($b) => $b->foreign(…))`, neither of which those
     * three names match. Two tests do it, and one of them —
     * `PolymorphicRelationsResolveThroughTfPeersOnMysqlTest` — never drops what
     * it adds. Reusing the schema therefore carried that stray constraint
     * forward and every repeat run failed with driver error 1826 (duplicate
     * constraint name), blaming the code under test for a harness artefact.
     *
     * So this method restores the SCHEMA as well as the data: any foreign key
     * the dial did not install is dropped. That is what makes the equivalence
     * true rather than merely plausible, and it costs nothing on the normal path
     * — usually there is nothing to drop.
     *
     * ## WHAT THIS DOES NOT DO
     *
     * It does not make a stale schema look fresh — that is the sentinel's job,
     * and a changed dial forces a rebuild. It restores FOREIGN KEYS, which is
     * the only kind of schema change this suite makes; a test that created a
     * TABLE or changed a COLUMN would not be undone by it, and the fingerprint
     * would not catch it either. That limit is stated rather than left to be
     * discovered. And it does not touch `dropAndCreateMysqlDatabase()`, which
     * keeps its full-rebuild meaning for its other caller in
     * `tests/Schema/Mirror/RepeatedEntityConvergesTest.php`.
     */
    protected function resetMysqlData(?string $database = null): array
    {
        $database ??= self::mysqlConnectionConfig()['database'];

        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException("refusing to use an unsafe database name: {$database}");
        }

        $pdo = self::mysqlLifecyclePdo();
        $pdo->exec("USE `{$database}`");

        $tables = $pdo->query(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES '
            ."WHERE TABLE_SCHEMA = '".addslashes($database)."' AND TABLE_TYPE = 'BASE TABLE' "
            ."AND TABLE_NAME NOT IN ('".self::SENTINEL_TABLE_NAME."','".self::SENTINEL_FK_TABLE_NAME."')",
        )->fetchAll(PDO::FETCH_COLUMN);

        // Undo any FOREIGN KEY a test added and did not drop, BEFORE the rows
        // go: an extra constraint can block the DELETEs below just as surely as
        // it would block a TRUNCATE.
        $dropped = $this->dropForeignKeysTheDialDidNotInstall($pdo);

        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach (array_chunk($tables, 80) as $chunk) {
                $statements = array_map(
                    static fn (string $t): string => "DELETE FROM `{$t}`",
                    $chunk,
                );
                $pdo->exec(implode(';', $statements));
            }
        } finally {
            // Restored in a finally, so a failed reset cannot leave the next
            // test running with DML reference checking switched OFF — which
            // would make every orphan-refusal assertion in this track green for
            // the wrong reason.
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $dropped;
    }

    /**
     * Drop every FOREIGN KEY the dial did not install.
     *
     * The shipped constraint set is stored as ROWS in a side table, not as a
     * digest, precisely because this has to be a set DIFFERENCE: "which live
     * constraints are not in the dial's set" cannot be answered from a hash. A
     * digest alone would have made the obvious implementation — drop everything,
     * compare hashes — which would destroy all 477 of the dial's own
     * constraints on the first dirty run and leave nothing to compare against.
     *
     * A constraint that is MISSING (a test dropped one) is not re-added here —
     * that needs the migrations, which is the cost being avoided. It is caught
     * from the other side instead: the digest this method finally compares
     * against is the one `prepareMysqlSchemaForTest()` acts on, so a constraint
     * that is missing leaves the digest unmatched and the next test rebuilds
     * from nothing. Extra constraints are removed here; missing ones force a
     * rebuild there. Between the two, every test sees the shipped set.
     *
     * @return list<string> identities dropped, `['__digest_mismatch__']` if the
     *                      schema still is not the dial's afterwards
     */
    private function dropForeignKeysTheDialDidNotInstall(PDO $pdo): array
    {
        $recordedDigest = $pdo->query(
            'SELECT fk_digest FROM `'.self::SENTINEL_TABLE_NAME.'` WHERE id = 1',
        )->fetchColumn();

        // No recorded digest means the schema is not the one we stamped, so
        // there is nothing to restore it to. The caller rebuilds in that case.
        if (! is_string($recordedDigest) || $recordedDigest === '') {
            return [];
        }

        // The dial's constraints, as an exact `table`+`constraint` pair set.
        $dial = [];
        foreach ($pdo->query(
            'SELECT table_name, constraint_name FROM `'.self::SENTINEL_FK_TABLE_NAME.'`',
        )->fetchAll() as $row) {
            $row = is_array($row) ? $row : (array) $row;
            $dial[(string) ($row['table_name'] ?? '')."\0".(string) ($row['constraint_name'] ?? '')] = true;
        }

        if ($dial === []) {
            return [];
        }

        $dropped = [];

        foreach (self::mysqlForeignKeyIdentities($pdo) as $identity) {
            [$table, $constraint] = explode('|', $identity, 2);

            if (isset($dial[$table."\0".$constraint])) {
                continue;
            }

            // Both halves reach DDL, so both are validated — the same rule
            // `dropAndCreateMysqlDatabase()` applies to a database name.
            if (! preg_match('/^[A-Za-z0-9_]+$/', $table) || ! preg_match('/^[A-Za-z0-9_]+$/', $constraint)) {
                continue;
            }

            try {
                $pdo->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
                $dropped[] = $identity;
            } catch (\PDOException) {
                // Left in place; the digest check below decides what happens.
            }
        }

        if (self::mysqlForeignKeyDigest($pdo) !== $recordedDigest) {
            return ['__digest_mismatch__'];
        }

        return $dropped;
    }

    /**
     * Record the dial fingerprint and the exact constraint set it installed, so
     * the NEXT test — and the NEXT process — can skip the rebuild and can put
     * back anything a test disturbed.
     *
     * Called only immediately after a successful full migrate, which is why the
     * constraint set it records IS the dial's own.
     */
    protected function stampMysqlDialSentinel(?string $database = null): void
    {
        $database ??= self::mysqlConnectionConfig()['database'];

        if (! preg_match('/^[A-Za-z0-9_]+$/', $database)) {
            throw new RuntimeException("refusing to use an unsafe database name: {$database}");
        }

        $pdo = self::mysqlLifecyclePdo();
        $pdo->exec("USE `{$database}`");

        $identities = self::mysqlForeignKeyIdentities($pdo);

        $pdo->exec(
            'CREATE TABLE `'.self::SENTINEL_FK_TABLE_NAME.'` ('
            .'table_name VARCHAR(64) NOT NULL, '
            .'constraint_name VARCHAR(64) NOT NULL, '
            .'PRIMARY KEY (table_name, constraint_name)'
            .') ENGINE=InnoDB',
        );
        $insert = $pdo->prepare(
            'INSERT INTO `'.self::SENTINEL_FK_TABLE_NAME.'` (table_name, constraint_name) VALUES (?, ?)',
        );
        foreach ($identities as $identity) {
            [$table, $constraint] = explode('|', $identity, 2);
            $insert->execute([$table, $constraint]);
        }

        $pdo->exec(
            'CREATE TABLE `'.self::SENTINEL_TABLE_NAME.'` ('
            .'id TINYINT UNSIGNED NOT NULL PRIMARY KEY, '
            .'fingerprint VARCHAR(64) NOT NULL, '
            .'fk_digest VARCHAR(64) NOT NULL, '
            .'stamped_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
            .') ENGINE=InnoDB',
        );
        $stmt = $pdo->prepare(
            'INSERT INTO `'.self::SENTINEL_TABLE_NAME.'` (id, fingerprint, fk_digest) VALUES (1, ?, ?)',
        );
        $stmt->execute([
            self::mysqlDialFingerprint(),
            self::mysqlForeignKeyDigest($pdo),
        ]);
    }

    /**
     * Give the next test in this process a clean slate, cheaply.
     *
     * ## THE ORDER OF THE TWO CHECKS IS THE WHOLE DESIGN, AND IT WAS WRONG ONCE
     *
     * A single combined "is the schema exactly right?" predicate looks tidier and
     * is wrong. It compares the live foreign-key digest against the recorded one,
     * and a test that adds a constraint without dropping it makes those differ —
     * so the predicate says "stale" and the caller REBUILDS. Measured with that
     * shape: the 56-test suite blew a 25-minute timeout, because every test
     * rebuilt a 100-second schema to clear a problem the reset removes in
     * milliseconds.
     *
     * So the checks are separated, and each answers the question it can:
     *
     *   1. Is this the same DIAL?  (fingerprint)  → no ⇒ rebuild; there is
     *      nothing to reuse and no cheap repair.
     *   2. Did a test leave a constraint behind? → YES ⇒ the reset drops it, at
     *      no meaningful cost.
     *   3. Is the schema back to the dial's? → no ⇒ rebuild. This is the only
     *      check that can demand the expensive path, and by then the cheap
     *      repairs have already been tried.
     *
     * A test that needs a pristine schema still gets one: the first test of a
     * process, every test after a changed migration set, and every test after an
     * unrepairable schema all rebuild from nothing.
     */
    protected function prepareMysqlSchemaForTest(): void
    {
        if (! self::mysqlDialIsInstalled()) {
            $this->dropAndCreateMysqlDatabase();

            return;
        }

        $dropped = $this->resetMysqlData();

        if ($dropped === ['__digest_mismatch__']) {
            $this->dropAndCreateMysqlDatabase();
        }
    }

    /**
     * Every migration file the shipped dial runs, in apply order, as ABSOLUTE
     * paths.
     *
     * Shared by the fingerprint and by `MySqlTestCase` so the two can never
     * disagree about what "the dial" is.
     *
     * ## WHY ABSOLUTE, AND WHY THIS FUNCTION IS NOT NAIVE
     *
     * `UpdateIngestor::migrationPaths()` returns an ABSOLUTE path (measured:
     * `/…/src/Laravel/Migrations`), derived from the autoloader's idea of where
     * the package lives — which is NOT necessarily where this file lives. An
     * earlier version of this method tried to store repository-RELATIVE paths by
     * chopping a fixed prefix off the front, and that silently produced garbage
     * (`ents/projects/teleframe/src/Laravel/Migrations/…`) whenever the prefix
     * did not actually match: the paths were stable, so nothing failed, and the
     * damage only surfaced as a migration that was never `require`d. Absolute
     * paths have no such assumption in them.
     *
     * @return list<string>
     */
    public static function mysqlMigrationFiles(): array
    {
        $root = dirname(__DIR__, 2);
        $files = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $absolute = str_starts_with($path, '/') ? $path : $root.'/'.$path;

            if (is_dir($absolute)) {
                $found = glob($absolute.'/*.php') ?: [];
                sort($found);
                foreach ($found as $file) {
                    $files[] = $file;
                }

                continue;
            }

            $files[] = $absolute;
        }

        return $files;
    }
}
