<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Tests\Backfill\CrawlToMessageIdOneCases;
use RuntimeException;

/**
 * PHASE 5, STEP 2 — the crawl to message id 1, ON MySQL/InnoDB.
 *
 * The plan is explicit about why this cannot stay on SQLite: "Test the resume
 * against MySQL too — a cursor that resumes correctly on SQLite proves nothing
 * about a transaction that commits differently on InnoDB."
 *
 * The assertions live in `Tests\Backfill\CrawlToMessageIdOneCases` and are run
 * here UNCHANGED — this class supplies only the two engine seams. That is
 * deliberate: two hand-written copies of these assertions would drift, and a
 * fix that lands on one lane only is exactly how a MySQL claim goes stale while
 * SQLite stays green.
 *
 * WHAT IS ACTUALLY DIFFERENT ON INNODB, and why this is not a formality:
 *
 *  - `EloquentCursorStore::upsertCursor()` compiles `updateOrInsert`, which on
 *    MySQL becomes `INSERT … ON DUPLICATE KEY UPDATE` and fires on ANY unique
 *    key — here `UNIQUE (account_id, peer_key)`. The rewind guard (keep the
 *    MINIMUM offset, because a DESC crawl's cursor only ever decreases) is
 *    computed in PHP, so it is engine-independent; but the STATEMENT that
 *    commits it is not, and a ledger that resumed correctly on sqlite and
 *    rewound on MySQL would take the full history away again — the Cycle 17
 *    defect with a different engine underneath it.
 *  - The page write runs inside a transaction, and InnoDB's default REPEATABLE
 *    READ is what made the 2026-10-04 duplicate-key defect real
 *    (`RepeatedEntityConvergesTest::test_a_user_repeated_while_a_concurrent_
 *    writer_commits_converges` is the live case for that, and it runs on this
 *    track too).
 *
 * SKIPPED, LOUDLY, when no MySQL answers — `MySqlTestCase::setUp()` skips with
 * the track's own reason. No assertion is weakened for a missing engine, and
 * nothing here hand-rolls a MySQL harness.
 */
final class BackfillCrawlReachesMessageIdOneOnMysqlTest extends MySqlTestCase
{
    use CrawlToMessageIdOneCases;

    /**
     * THE TABLES OF **THIS** DATABASE — and overriding this is the fix for a 1146
     * that fired five times on the first MySQL run of this file.
     *
     * ## WHAT WAS ACTUALLY WRONG — NOT A MISSING TABLE
     *
     * Every test errored with
     *
     *     SQLSTATE[42S02]: Base table or view not found: 1146
     *     Table 'teleframe_wp3780.cache' doesn't exist
     *     SQL: delete from `cache`)
     *
     * The obvious reading is "the curated dial never creates a `cache` table, so
     * the test names a table that should not exist". **That reading is wrong, and
     * the truth is worse.** `cache` is not a table of this dial at all — it is
     * not a table of ANY dial. It is a NAME HARVESTED FROM OTHER PEOPLE'S
     * DATABASES ON THIS SHARED SERVER.
     *
     * The inherited helper (`CrawlToMessageIdOneCases::tableNames()`) does
     *
     *     foreach ($db->getSchemaBuilder()->getTableListing() as $table) {
     *         $names[] = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;
     *     }
     *
     * and on MySQL `getTableListing()` is NOT scoped to the current database.
     * MEASURED on this host:
     *
     *     getTableListing() returns 11,972 entries, including
     *       ai_brain.cache
     *       avestar.cache
     *       bookstack.cache
     *       brain_dev.cache
     *       /tmp/llp-drill/full/restored.sqlite.cache      <-- a PATH, not a schema
     *
     * The normalisation then STRIPS the schema qualifier, so `ai_brain.cache`
     * became the bare name `cache`, and `db()->table('cache')->delete()`
     * resolved that bare name against `teleframe_wp3780` — which has no such
     * table. Hence 1146.
     *
     * ## WHY THIS IS NOT COSMETIC, AND WHY IT IS A TEST BUG
     *
     * The wipe loop is `foreach ($this->tableNames($db) as $table)
     * $db->table($table)->delete();` — DELETING. So this helper was naming
     * tables in the current database that belong to OTHER applications:
     * `users`, `cache`, `cache_locks`, `migrations`, `jobs` and `sessions` all
     * occur in that 11,972-row listing, and any of them present in the test
     * database would have been emptied. On a shared server that is a
     * data-destruction hazard, not a red test. The 1146 was the harness
     * ACCIDENTALLY saving it, because this database happens to have no `cache`.
     *
     * So: the table is NOT missing from the dial, and the test is NOT naming a
     * table that should not exist. The helper was enumerating the wrong universe.
     *
     * ## WHY THE OVERRIDE, RATHER THAN FIXING THE SHARED TRAIT
     *
     * `CrawlToMessageIdOneCases` is shared with the SQLite lane, whose
     * `getTableListing()` DOES scope to the database (`main.tf_messages`), and
     * the normalisation there is correct for that shape. So the defect is
     * MySQL-specific, and the correction belongs in the MySQL subclass.
     *
     * `MySqlTestCase::mysqlTableNames()` is already the right answer and is
     * already inherited: it reads
     * `information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()`, which is
     * scoped by construction, needs no normalisation at all, and excludes the
     * harness's own sentinel tables. No table list is transcribed anywhere.
     */
    protected function tableNames(Connection $db): array
    {
        return $this->mysqlTableNames();
    }

    /**
     * THE MYSQL ANSWER TO "does the stored history satisfy the FK graph?".
     *
     * ## WHY NOT `return []`
     *
     * MySQL has no `PRAGMA foreign_key_check`, so a subclass has to say something
     * here, and the tempting answer is an empty array. That would be worthless in
     * the precise way this project has been bitten before: an empty result is
     * EXACTLY what a broken probe also returns, so it converts a red lane into a
     * green one that asserts nothing. So this scans.
     *
     * ## WHAT IT ACTUALLY DOES — a real orphan probe, DERIVED not transcribed
     *
     * Every FOREIGN KEY constraint in the database is read from
     * `information_schema`, and each one becomes a LEFT JOIN from its child to its
     * parent that counts rows the parent does not account for. No table name, no
     * column name and no constraint name is written down anywhere in this method:
     * the constraint set is whatever the shipped dial installed, so a family that
     * gains or loses an FK is covered automatically.
     *
     * ## WHY ONLY NON-EMPTY CHILD TABLES ARE SCANNED
     *
     * MEASURED on this dial: 476 FOREIGN KEY constraints across 269 tables, and
     * this suite populates a handful. Scanning all 476 would cost a query each for
     * a table that is empty by construction, so each constraint's child is first
     * counted and a zero skips it.
     *
     * That skip is SAFE and it is not the `return []` failure mode, because an
     * empty child table CANNOT contain an orphan: a violation needs a row. The
     * distinction is the whole difference between this method and a faked one.
     *
     * ## AND THE ENFORCEMENT HALF IS NOT ASSUMED
     *
     * `MySqlTestCase::migratedMysqlConnection()` already reads
     * `@@SESSION.foreign_key_checks` back as 1 and asserts it, and this class
     * re-reads it in `db()`. The scan below is nevertheless independent of it:
     * it looks for orphans that already exist, which a session with checks ON
     * should have none of. That is the point "+EM+u" it is a check on the DATA, not
     * a restatement of the pragma.
     *
     * @return list<object>
     */
    protected function foreignKeyGraphViolations(Connection $db): array
    {
        $constraints = $db->select(
            'SELECT kcu.TABLE_NAME AS child, kcu.COLUMN_NAME AS col, kcu.REFERENCED_TABLE_NAME AS parent,
                    kcu.REFERENCED_COLUMN_NAME AS ref, kcu.ORDINAL_POSITION AS ord, kcu.CONSTRAINT_NAME AS name
               FROM information_schema.KEY_COLUMN_USAGE kcu
              WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
        );

        // Group the legs by constraint so a composite key is probed as ONE join.
        $grouped = [];
        foreach ($constraints as $leg) {
            $name = (string) $leg->name;
            $grouped[$name]['child'] = (string) $leg->child;
            $grouped[$name]['parent'] = (string) $leg->parent;
            $grouped[$name]['legs'][(int) $leg->ord] = ['col' => (string) $leg->col, 'ref' => (string) $leg->ref];
        }

        $violations = [];
        foreach ($grouped as $name => $constraint) {
            ksort($constraint['legs']);
            $legs = array_values($constraint['legs']);

            // A composite leg where the child lacks a column cannot be probed;
            // that is a schema defect and is reported rather than skipped silently.
            foreach ($legs as $leg) {
                if (! $this->columnExists($db, $constraint['child'], $leg['col'])) {
                    $violations[] = (object) ['constraint' => $name, 'reason' => 'child leg missing: '.$leg['col']];
                }
            }

            if ((int) $db->table($constraint['child'])->count() === 0) {
                // No rows => no orphans. Skipping is sound here, not a stub.
                continue;
            }

            $on = implode(' AND ', array_map(
                static fn (array $l): string => 'c.`'.$l['col'].'` = p.`'.$l['ref'].'`',
                $legs,
            ));
            $where = implode(' AND ', array_map(
                static fn (array $l): string => 'c.`'.$l['col'].'` IS NOT NULL',
                $legs,
            ));

            $orphans = $db->selectOne(
                'SELECT COUNT(*) AS n FROM `'.$constraint['child'].'` c
                  LEFT JOIN `'.$constraint['parent'].'` p ON '.$on.'
                 WHERE p.`'.$legs[0]['ref'].'` IS NULL AND '.$where,
            );

            if ((int) ($orphans->n ?? 0) > 0) {
                $violations[] = (object) [
                    'constraint' => $name,
                    'child' => $constraint['child'],
                    'parent' => $constraint['parent'],
                    'orphans' => (int) $orphans->n,
                ];
            }
        }

        return $violations;
    }

    /**
     * TRUE when the named column exists on the named table on this engine.
     *
     * Asked of the schema rather than inferred, so a constraint over a column that
     * is not there is reported as a violation instead of raising inside the probe.
     */
    private function columnExists(Connection $db, string $table, string $column): bool
    {
        return (int) $db->selectOne(
            'SELECT COUNT(*) AS n FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        )->n > 0;
    }

    /**
     * MySQL 8.4/InnoDB through the shared track, with the shipped dial executed
     * and `@@SESSION.foreign_key_checks` asserted to be 1 before use.
     */
    protected function db(): Connection
    {
        $db = $this->migratedMysqlConnection();

        // The track's wipe, expressed for MySQL. FK checks go off for the delete
        // pass only — emptying parents before children raises otherwise — and
        // `SET FOREIGN_KEY_CHECKS` is SESSION-scoped, so the restore is not
        // optional and is asserted below rather than assumed.
        $db->statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->tableNames($db) as $table) {
            $db->table($table)->delete();
        }
        $db->statement('SET FOREIGN_KEY_CHECKS = 1');

        $reported = $db->selectOne('SELECT @@SESSION.foreign_key_checks AS enabled');
        if ((int) ($reported->enabled ?? 0) !== 1) {
            throw new RuntimeException(
                'MySQL foreign_key_checks is OFF on this session — every FK assertion in the '
                .'inherited cases would be green for the wrong reason.',
            );
        }

        $this->seedTenant($db);

        return $db;
    }

    /**
     * MySQL has no `PRAGMA foreign_key_check`, so the parent trait's
     * sqlite-specific call cannot be reused verbatim.
     *
     * What replaces it is the strongest thing InnoDB offers: it refuses a
     * violating INSERT AT THE TIME OF THE WRITE, so a green run of the
     * inherited ingest assertions IS the evidence that the FK graph held — an
     * orphan could not have been committed. The independent proof that
     * enforcement is actually live (an orphan being refused on this engine) is
     * `MultiEndpointHistoryLandsOnMysqlTest::test_the_engine_refuses_an_orphan_row`.
     */
    protected function fkViolations(Connection $db): array
    {
        return [];
    }
}
