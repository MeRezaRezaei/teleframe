<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A rejected row must cost ONE row, not the page — and the duplicate-key case of that is
 * only reachable while the shipped schema keeps a property this class ASSERTS rather than
 * assumes.
 *
 * ## WHAT THIS IS, and what it is NOT
 *
 * It is a CHARACTERISATION test, and it says so plainly: the property already holds. It does
 * not reproduce a bug and was never expected to. Its job is to make the day it STOPS holding
 * loud, because nothing else in the suite would notice.
 *
 * ## WHY THE PROPERTY MATTERS, stated as the mechanism
 *
 * `placeRow()` converges a repeated identity with ONE atomic statement —
 * `$db->table($table)->upsert([$row], $primary)` (src/Schema/Mirror/MirrorFactWriter.php:1394),
 * with `$primary` coming from `primaryKeyColumns()`. The conflict target is the table's own
 * declared PRIMARY. A duplicate can therefore still be raised past that statement if, and only
 * if, the table enforces a UNIQUE identity the conflict target does not name — a SECOND unique
 * index. `write()`'s catch chain classifies such a raise (the `isUniqueViolation()` arm) and
 * continues, so the cost is one row; but the raise is a real data loss, and this gate is what
 * proves it cannot happen.
 *
 * ## MEASURED 2026-10-04 — the whole shipped schema, not a sample of it
 *
 * Command (executed schema, never the migration source):
 *
 *   every file in src/Laravel/Migrations/ run in filename order against a fresh
 *   sqlite (:memory:) Capsule via tests/Store/ShippedStoreSchema::capsuleFor(),
 *   then getSchemaBuilder()->getIndexes($table) read for every table
 *
 * Result, and these are the numbers this class asserts against:
 *
 *   - 52 migration files executed, 269 tables created.
 *   - 6 of those 269 declare a unique index beyond their primary key
 *     (7 indexes in total): telegram_accounts, telegram_apps, tf_backfill_cursors,
 *     tf_store_backup_runs, tg_update_routing, tl_user_bindings.
 *   - 0 of those 6 are named by src/Schema/schema/mirror-catalog.json (35 tables).
 *   - All 35 catalogued tables declare a discoverable primary key, so none can take
 *     `placeRow()`'s bare-insert branch (the one branch where nothing converges a repeat).
 *
 * RE-MEASURED by RESIDUE-RECONCILE 2026-10-05T11:31Z, every number above, by EXECUTING the
 * dial through ShippedStoreSchema::capsuleFor() and reading the built schema — not by grep:
 *
 *   migration files globbed : 52   (`glob('src/Laravel/Migrations/*.php')`; the dial grew
 *                               42 -> 52 when ten FK-family migrations landed, 322f385f
 *                               "move both dial pins 42 -> 52")
 *   tables created          : 269  (sqlite_master, excluding sqlite_% internals)
 *   catalogued tables       : 35   (array_keys of mirror-catalog.json)
 *   non-primary uniques     : 6 tables / 7 indexes — UNCHANGED, same six names,
 *                               telegram_accounts carrying 2 of the 7
 *   of the 6, named by the catalog : 0 — every catalogued name starts with `tf_`, and
 *                               none of the six is one of them
 *   catalogued tables without a discoverable primary key : 0 of 35
 *
 * ## WHY THE NUMBERS ARE STAMPED TWICE, AND WHY THE FIRST STAMP WAS WRONG
 *
 * This block was corrected 41 -> 42 on 2026-10-05 and is now corrected 42 -> 52 on the same
 * day, because the dial moved ten files under it in between. Both corrections are recorded
 * rather than overwritten silently, because the failure mode is specific and it recurs: a
 * number published in a docblock is GREEN BY CONSTRUCTION — no test fails when it drifts, and
 * this gate does not assert the count, it only observes it. 41 was never measured at all
 * (`git ls-tree -r 4fa73657 -- src/Laravel/Migrations/` already listed 42). 42 was measured
 * correctly and then went stale within hours. Neither drift can fail a build; only re-running
 * the measurement catches it, which is why the command that produced each number is recorded
 * beside it rather than the number alone.
 *
 * NOTE for the next reader: these are counts, not assertions. `test_no_catalogued_table_
 * declares_a_second_unique_index` and `test_every_catalogued_table_exposes_a_discoverable_
 * primary_key` still FAIL if a second unique index or a missing primary key ever appears —
 * those two are gates. The counts below are provenance for the humans reading this file, and
 * a stale count here is a documentation defect, never a false green.
 *
 * So on the covered-key path of every table the writer actually feeds, there is no second
 * unique index for a repeat to collide on. The six are real tables with real second
 * identities — `telegram_accounts_user_id_unique` and
 * `tg_update_routing_account_id_peer_type_peer_id_unique` among them — and they are reached
 * by Eloquent models and routing writes, never by `MirrorFactWriter::write()`. Every one of
 * the four production callers passes decomposer output, and the decomposer iterates the
 * catalogue.
 *
 * ## WHAT THIS GATE CAN AND CANNOT OBSERVE ON THE DEFAULT ENGINE
 *
 * It observes the SCHEMA, on sqlite. That is engine-independent for the question asked:
 * a unique index is declared in the migration, and `getIndexes()` reports what the migration
 * declared. It is a property of the shipped DDL, not of the isolation level.
 *
 * It observes nothing about the RACE that `upsert()` was introduced to close. sqlite has no
 * MVCC snapshot isolation, and a sequential probe of the `updateOrInsert` TOCTOU pair
 * deadlocks with `database is locked` rather than reproducing a duplicate key — that is
 * measured and recorded in docs/decisions.md D-002. The convergence behaviour under a second
 * writer is gated separately and only runs against a live MySQL (`TELEFRAME_MYSQL_*`), which
 * CI does not provide.
 *
 * The corollary, and it is a real limit: this gate proves no second unique index EXISTS. It
 * cannot prove the driver would honour the conflict target — MySQL in fact ignores
 * `$uniqueBy` entirely and emits `on duplicate key update`, which fires on ANY unique key, so
 * on MySQL a second unique index would be handled rather than missed. That is a second
 * reason not to add one.
 */
final class RejectedRowCostsOneRowTest extends TestCase
{
    /**
     * The catalogue's table names, read from the catalogue rather than transcribed.
     *
     * @return list<string>
     */
    private static function catalogTables(): array
    {
        $raw = file_get_contents(__DIR__.'/../../../src/Schema/schema/mirror-catalog.json');
        self::assertIsString($raw, 'mirror-catalog.json must be readable — a transcribed table list would be a second copy that drifts');

        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded, 'mirror-catalog.json must decode');

        $tables = $decoded['tables'] ?? $decoded;
        self::assertIsArray($tables, 'mirror-catalog.json must carry a table map');

        $names = [];
        foreach (array_keys($tables) as $name) {
            $names[] = (string) $name;
        }

        return $names;
    }

    /**
     * Every shipped migration, in filename order.
     *
     * @return list<string>
     */
    private static function everyShippedMigration(): array
    {
        $files = glob(__DIR__.'/../../../src/Laravel/Migrations/*.php');
        self::assertIsArray($files);

        sort($files);

        return array_values($files);
    }

    /**
     * Unique indexes on $table that are NOT its primary key.
     *
     * The primary is identified by the `primary` FLAG, never by the index NAME, and that is
     * not a style choice — it is the only correct test on sqlite, where a composite PRIMARY
     * is reported as `sqlite_autoindex_<table>_1` with `primary => true` rather than under the
     * name `primary`. It is exactly what `primaryKeyColumns()` reads
     * (src/Schema/Mirror/MirrorFactWriter.php:1421), so the gate and the writer agree on what
     * "the primary key" means; a name-based gate would call 35 of the 36 catalogued tables
     * offenders, which is how a green gate over a red schema gets shipped.
     *
     * @return list<array{name: string, columns: list<string>}>
     */
    private static function nonPrimaryUniqueIndexes(object $builder, string $table): array
    {
        $found = [];

        foreach ($builder->getIndexes($table) as $index) {
            if (($index['primary'] ?? false) === true) {
                continue;
            }

            if (($index['unique'] ?? false) === true) {
                $found[] = [
                    'name' => (string) ($index['name'] ?? ''),
                    'columns' => array_values($index['columns'] ?? []),
                ];
            }
        }

        return $found;
    }

    /**
     * THE GATE. No catalogued table may enforce a unique identity the writer's conflict target
     * does not name.
     */
    public function test_no_catalogued_table_declares_a_second_unique_index(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(...self::everyShippedMigration());
        $builder = $capsule->getConnection()->getSchemaBuilder();

        $offenders = [];

        foreach (self::catalogTables() as $table) {
            $indexes = self::nonPrimaryUniqueIndexes($builder, $table);

            if ($indexes !== []) {
                $described = array_map(
                    static fn (array $i): string => $i['name'].'('.implode(',', $i['columns']).')',
                    $indexes,
                );
                $offenders[] = $table.' -> '.implode(' | ', $described);
            }
        }

        self::assertSame(
            [],
            $offenders,
            "these catalogued tables declare a unique index the writer's upsert() conflict "
            ."target does not name, so a repeated identity can raise past it: \n  "
            .implode("\n  ", $offenders),
        );
    }

    /**
     * Every catalogued table must expose a primary key, because `placeRow()` falls back to a
     * BARE INSERT when it does not (src/Schema/Mirror/MirrorFactWriter.php:1318-1325), and on
     * that branch nothing converges a repeat.
     */
    public function test_every_catalogued_table_exposes_a_discoverable_primary_key(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(...self::everyShippedMigration());
        $builder = $capsule->getConnection()->getSchemaBuilder();

        $without = [];

        foreach (self::catalogTables() as $table) {
            $primary = null;

            foreach ($builder->getIndexes($table) as $index) {
                // Read exactly as primaryKeyColumns() reads it: the primary FLAG, not the
                // name. On sqlite a composite PRIMARY arrives as sqlite_autoindex_*.
                if (($index['primary'] ?? false) === true && ($index['columns'] ?? []) !== []) {
                    $primary = $index['columns'];

                    break;
                }
            }

            if ($primary === null) {
                $without[] = $table;
            }
        }

        self::assertSame(
            [],
            $without,
            'these catalogued tables expose no discoverable primary key, so placeRow() takes '
            .'its bare-insert branch where a repeat is never converged: '.implode(', ', $without),
        );
    }

    /**
     * THE GATE'S OWN ANTI-VACUITY PROOF, and it is the RED step this class actually shipped with.
     *
     * A gate that asserts "nothing was found" is worthless unless it can be shown to FIND
     * something. This case pins the detector against a schema built to be caught: a table
     * whose composite primary it must NOT report, plus a second unique index it MUST report.
     *
     * It is not decorative. The first draft of this class identified the primary key by NAME,
     * which is correct on MySQL and catastrophically wrong on sqlite, where a composite
     * primary arrives as `sqlite_autoindex_<table>_1`. That version was run and it FAILED,
     * naming 35 of the 36 catalogued tables as offenders — every one of them wrongly. Both
     * halves are therefore asserted here: the false positive it produced, and the true
     * positive it must produce.
     */
    public function test_the_detector_reports_a_second_unique_index_and_hides_a_composite_primary(): void
    {
        $capsule = ShippedStoreSchema::capsuleFor(
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php',
        );
        $builder = $capsule->getConnection()->getSchemaBuilder();

        $builder->create('tf_gate_probe_with_second_unique', function ($t): void {
            $t->unsignedBigInteger('account_id');
            $t->unsignedBigInteger('id');
            $t->string('label', 64);
            $t->unsignedBigInteger('peer_id')->nullable();
            $t->primary(['account_id', 'id']);
            $t->unique(['account_id', 'peer_id'], 'tf_gate_probe_second_unique');
        });

        $builder->create('tf_gate_probe_primary_only', function ($t): void {
            $t->unsignedBigInteger('account_id');
            $t->unsignedBigInteger('id');
            $t->primary(['account_id', 'id']);
        });

        $withSecond = self::nonPrimaryUniqueIndexes($builder, 'tf_gate_probe_with_second_unique');
        $primaryOnly = self::nonPrimaryUniqueIndexes($builder, 'tf_gate_probe_primary_only');

        self::assertCount(
            1,
            $withSecond,
            'the detector must report a genuine second unique index — if it reports nothing '
            .'the gate above is vacuous and would stay green through any schema change',
        );
        self::assertSame(
            'tf_gate_probe_second_unique',
            $withSecond[0]['name'],
            'the detector must name the index it found, so a red gate says WHICH index to remove',
        );
        self::assertSame(
            ['account_id', 'peer_id'],
            $withSecond[0]['columns'],
            'the detector must report the index COLUMNS — a name alone does not tell the '
            .'reader what identity the writer would now be blind to',
        );
        self::assertSame(
            [],
            $primaryOnly,
            'a composite primary must NOT be counted as a second unique index: on sqlite it '
            .'arrives as sqlite_autoindex_*, and matching it by name turns 35 of 36 real '
            .'catalogued tables into false offenders',
        );
    }
}
