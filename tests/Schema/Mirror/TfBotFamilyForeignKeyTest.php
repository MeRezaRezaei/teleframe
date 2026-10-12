<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use ReflectionClass;

/**
 * The `tf_bot_*` / `tf_attach_menu_bots` family is constrained to its parent, and the
 * database says so — for BOTH join shapes the family contains.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000850_add_tf_bot_family_foreign_keys.php` adds a FOREIGN KEY from
 * every single-parent relation inside this family to that parent's key. This file
 * proves the constraints BITE — a valid child row lands, an orphan is refused BY
 * the constraint and not by anything else, and the whole thing disappears when the
 * migration does.
 *
 * ## THE FAMILY IS NOT ONE NAME-SPACE, AND THAT IS THE POINT
 *
 * The plan sizes this family at "the 19 relations spread across FIVE parents",
 * and flags the hazard: the `tf_bot_*` parents are keyed `(account_id, id)` while
 * the two `tf_attach_menu_bots_*` children join on `(account_id, bot_id)`. A
 * family treated as uniform is the failure mode, so this file does three things
 * the uniform-family template does not:
 *
 *  1. it selects the family by TWO name-spaces, because a single prefix rule
 *     cannot reach both — `tf_attach_menu_bots` does not extend any `tf_bot_*`
 *     table;
 *  2. it ASSERTS that both leg shapes are actually present and constrained, so
 *     "I handled both" is a measurement rather than a claim (PROOF 3);
 *  3. it asserts the family selection is EXHAUSTIVE — every discovered relation
 *     whose child matches a namespace has a parent inside the family
 *     (PROOF 2). A family that quietly skipped a member would otherwise be
 *     indistinguishable from a complete one.
 *
 * ## WHY THE `bot_id` SHAPE LANDS, AND IT IS NOT ASSUMED
 *
 * The hazard was that a `(account_id, bot_id)` leg set might reference a
 * non-UNIQUE parent index, which MySQL 8.4 refuses with error 6125 — and the
 * prescribed remedy is to leave the relation out rather than widen a key.
 *
 * MEASURED on the executed dial (SQLite, `getIndexes()` on the built schema),
 * with the command that produced it recorded in the migration's docblock:
 *
 *   tf_attach_menu_bots   PK(account_id,bot_id)  unique=true
 *   tf_attach_menu_bots_icons       PK(account_id,bot_id,position)  idx(account_id,bot_id)
 *   tf_attach_menu_bots_peer_types  PK(account_id,bot_id,position)  idx(account_id,bot_id)
 *
 * So `bot_id` is a column of the parent's PRIMARY KEY, not merely a column of
 * the same name — the exact distinction family 4's hazard warns about. The leg
 * set references the parent's own UNIQUE key and is accepted. PROOF 4 asserts
 * this at runtime from the built schema rather than trusting this paragraph, so
 * a future migration that drops that uniqueness fails HERE with the actual index
 * in the message instead of failing as a mystery 6125 on a real ALTER.
 *
 * ## WHY THE DISCOVERY IS RE-IMPLEMENTED HERE, ON PURPOSE
 *
 * Two implementations of one discovery is not duplication to be removed; it is
 * the only way the migration's discovery is CHECKED rather than trusted. This
 * file re-derives the family relations from the BUILT schema by its own code —
 * longest-prefix ancestry whose parent's primary key is a subset of the child's,
 * intersected down to the parent's key — and asserts the result against the
 * executed DDL. If this and the migration's `familyRelations()` ever disagree,
 * one of the assertions below goes red naming the difference. No table list and
 * no join-column list is written into this file: a transcribed list is a second
 * copy of the schema and it drifts.
 *
 * ## WHY EVERY BEHAVIOURAL ASSERTION RUNS ON AN ENFORCING CONNECTION
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF, where a
 * declared constraint is INERT. A negative test written on that default is green
 * because no check happened. Every behavioural connection here goes through
 * `RelationConstraintTestCase::enforcingConnection()`, which turns the pragma on
 * and READS IT BACK before handing it over.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP THIS FIXTURE IS BUILT AROUND
 *
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). An "orphan" built naively — an
 * `account_id` no tenant carries — is refused by the TENANT constraint, and every
 * assertion below would be green while this migration contributed nothing. So
 * every fixture plants a REAL tenant and makes the orphan-ness live ONLY in the
 * parent's key coordinates.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphans_are_admitted_without_this_migration()` inserts the SAME
 * orphan rows into the SAME tables on the SAME engine with exactly one file
 * withheld from the dial, and requires them to LAND. That is what makes the
 * refusals attributable to this migration rather than to sqlite, to the pragma,
 * or to the tenant constraint.
 *
 * ## AND WHY THE WITNESSES ARE CHOSEN BY SHAPE, NEVER BY NAME
 *
 * A witness transcribed by table name is a second copy of the schema. Both
 * witnesses are DERIVED here — one relation whose legs end in `id`, one whose
 * legs end in `bot_id` — and their payload columns are filled from the EXECUTED
 * table's own column types rather than typed by hand, so a column added later
 * fails this fixture loudly instead of silently accepting a short insert.
 */
final class TfBotFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000850';

    /**
     * The family's name-spaces.
     *
     * TWO, not one, and that is a measurement of the shipped dialect rather than
     * a convenience: the family's 23 tables split across `tf_bot_*` and
     * `tf_attach_menu_bots*`, and no single prefix reaches both because
     * `tf_attach_menu_bots` extends no `tf_bot_*` table. A one-root rule — which
     * is what the `tf_messages` and `tf_stars_transactions` families get to use —
     * would silently miss one half of this family and still report a count.
     *
     * These are NAMESPACE strings, the only hand-named values in the family
     * selection, and PROOF 2 exists because of them: it asserts that every
     * relation whose child matches one of them has its parent inside the
     * selection, so the selection cannot be quietly incomplete.
     *
     * @var list<string>
     */
    private const FAMILY_NAMESPACES = ['tf_bot_', 'tf_attach_menu_bots'];

    /**
     * The number of single-parent relations MEASURED inside this family on
     * 2026-10-05, and the number this migration therefore constrains.
     *
     *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
     *       --filter test_every_single_parent_relation_has_a_real_constraint \
     *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *       | grep -E "→ tf_bot_infos$|→ tf_bot_inline_results$|→ tf_bot_apps$|→ tf_attach_menu_bots$" \
     *       | wc -l
     *     → 18
     *
     * and independently, from the built schema rather than the gate's RED list:
     * 22 family tables, of which 5 are parents, so 22 − 5 + 1 = 18 children.
     *
     * Both numbers are pinned so that discovery cannot silently change length in
     * either direction. If a family gains or loses a table, re-measure and bump
     * BOTH here and in the migration — never edit a count to reach a green run.
     *
     * MOVE, measured 2026-10-11: 19 -> 18, from
     * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`.
     * `tf_bot_infos_description` was a fabricated table for `botInfo`'s
     * `description:flags.1?string` scalar; dropping it takes the family from 23
     * tables to 22 and the relation count from 19 to 18. The RED-list grep above
     * lost its `tf_bot_infos(_description)?` alternative on purpose: after the
     * fold nothing ends in `→ tf_bot_infos_description`, because
     * `tf_bot_infos_description_{photo,document}` were re-parented onto
     * `tf_bot_infos` — which is where the catalog declared them all along.
     */
    private const MEASURED_RELATIONS = 18;

    /**
     * `MEASURED_RELATIONS − REFUSED`.
     *
     * Equal to `MEASURED_RELATIONS` today, and that equality is asserted rather
     * than assumed: the hazard that would make `bot_id` a refusal (a non-unique
     * parent index, MySQL error 6125) was MEASURED not to hold — see the class
     * docblock — so nothing is declined. See PROOF 4, which is where that
     * measurement is proved from the built schema instead.
     */
    private const MEASURED_CONSTRAINED = 18;

    /** A `telegram_accounts.user_id` this file plants, so the tenant FK cannot refuse. */
    private const TENANT = 1724372757;

    /** The planted parent's `id` / `bot_id` coordinate — distinct per shape. */
    private const PARENT_ID = 424242;

    /** Coordinates for a parent that is NEVER planted — the orphan. */
    private const ABSENT_ID = 987654321;

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * The DIRECTORY list comes from `UpdateIngestor::migrationPaths()` — the same
     * list the ingest seam and the MySQL lane run — rather than from a glob of
     * `src/Laravel/Migrations`, because a glob here would be a second copy of the
     * dial and a second copy drifts. This migration's own file lives in that
     * directory, so if it is ever moved out of the curated dial this suite fails
     * rather than quietly testing a dial without it.
     *
     * @return list<string>
     */
    private function migrations(bool $withholdThisMigration = false): array
    {
        $files = [];
        $seen = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            sort($inDir);

            foreach ($inDir as $file) {
                // `migrationPaths()` yields the curated DIRECTORY and then two
                // entity FILES that already live inside it, so a naive expansion
                // applies those two twice and dies on "table already exists".
                // Deduped by real path, keeping apply order.
                $key = realpath($file) ?: $file;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if ($withholdThisMigration && str_contains(basename($file), self::MIGRATION)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix and the refusal
     * list are read out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_bot_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new ReflectionClass($migration))->getConstant($name);
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented
    // ------------------------------------------------------------------

    /** @return list<string> */
    private function familyTables(Connection $db): array
    {
        $members = [];
        foreach ($this->tableNames($db) as $table) {
            foreach (self::FAMILY_NAMESPACES as $namespace) {
                if (str_starts_with($table, $namespace)) {
                    $members[] = $table;

                    break;
                }
            }
        }

        return $members;
    }

    /**
     * @return list<string>
     */
    private function tableNames(Connection $db): array
    {
        $prefix = $db->getTablePrefix();
        $names = [];

        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;

            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }

            $names[] = $bare;
        }

        sort($names);

        return $names;
    }

    /**
     * Every family's table name is a namespace, and a namespace's own children
     * are in it — but a table can also EXTEND a family member. Both are folded in
     * by the ancestry rule below, which is why membership is not just the prefix
     * test.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        $tables = $this->tableNames($db);
        $tableSet = array_flip($tables);

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $cols = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $cols[(string) $column['name']] = true;
            }
            $columns[$table] = $cols;

            // The key comes from the driver's INDEX listing, not the column rows:
            // `getColumns()` carries no key position, and a composite FOREIGN KEY
            // must be declared in the parent's key ORDER.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        $family = array_flip($this->familyTables($db));

        $relations = [];
        foreach ($family as $table => $_) {
            if (($primaryKey[$table] ?? []) === []) {
                continue;
            }

            $parts = explode('_', $table);
            $parent = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate], $family[$candidate])) {
                    continue;
                }
                if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }
                $parent = $candidate;
                break;
            }

            if ($parent === null) {
                continue;
            }

            $legs = [];
            foreach ($primaryKey[$parent] as $leg) {
                if (isset($columns[$table][$leg])) {
                    $legs[] = $leg;
                }
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        usort($relations, static fn (array $a, array $b): int => $a['child'] <=> $b['child']);

        return $relations;
    }

    /**
     * The single-parent relations this file re-derives, for the EXHAUSTIVENESS
     * check: every table whose name matches a family namespace and which descends
     * from a table inside the family selection. Written independently of
     * `familyRelations()`'s own loop so the two are not the same computation.
     *
     * @return list<array{child:string, parent:string}>
     */
    private function descendantsInsideFamily(Connection $db): array
    {
        $tables = $this->tableNames($db);
        $tableSet = array_flip($tables);
        $family = array_flip($this->familyTables($db));

        $primaryKey = [];
        foreach ($tables as $table) {
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $primaryKey[$table] = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] ??= [];
        }

        $found = [];
        foreach ($tables as $table) {
            $inNamespace = false;
            foreach (self::FAMILY_NAMESPACES as $namespace) {
                if (str_starts_with($table, $namespace)) {
                    $inNamespace = true;
                    break;
                }
            }
            if (! $inNamespace) {
                continue;
            }

            $parts = explode('_', $table);
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate], $family[$candidate])) {
                    continue;
                }
                if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }
                $found[] = ['child' => $table, 'parent' => $candidate];
                break;
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    private function refused(): array
    {
        $refused = $this->migrationConstant('REFUSED');

        Assert::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            Assert::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            Assert::assertNotSame('', trim($reason), "REFUSED[{$child}] has an empty reason");
        }

        return array_keys($refused);
    }

    // ------------------------------------------------------------------
    // fixtures — built from the EXECUTED table, never typed by hand
    // ------------------------------------------------------------------

    /** Plant the tenant every fixture below needs, so the tenant FK cannot be what refuses. */
    private function plantTenant(Connection $db, int $tenant): void
    {
        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => $tenant,
        ]);
    }

    /**
     * A row for `$table` covering EVERY column the engine declares, with the
     * constrained coordinates forced to `$coordinates` and everything else filled
     * from that column's own declared type.
     *
     * Nothing here is typed against a migration: `getColumns()` is the executed
     * table, so a column added tomorrow fails this builder loudly instead of
     * producing a short insert that NOT NULL would refuse for an unrelated
     * reason.
     *
     * @param  array<string, mixed>  $coordinates
     */
    private function rowFor(Connection $db, string $table, array $coordinates): array
    {
        $row = [];
        foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
            $name = (string) $column['name'];
            $type = strtolower((string) ($column['type'] ?? ''));

            if (array_key_exists($name, $coordinates)) {
                $row[$name] = $coordinates[$name];

                continue;
            }

            $row[$name] = $this->placeholderFor($type);
        }

        return $row;
    }

    /** A legal value for a declared column type, chosen by type not by table. */
    private function placeholderFor(string $type): mixed
    {
        if (str_contains($type, 'char') || str_contains($type, 'text') || str_contains($type, 'clob')) {
            return 'x';
        }

        if (str_contains($type, 'bool')) {
            return 0;
        }

        if (str_contains($type, 'blob')) {
            return 'x';
        }

        if (str_contains($type, 'real') || str_contains($type, 'floa') || str_contains($type, 'doub')) {
            return 0.0;
        }

        return 0;
    }

    /**
     * The coordinates that identify the parent row for a relation whose legs end
     * in `$leg`. `account_id` is ALWAYS the planted tenant, so the orphan-ness of
     * an absent parent lives only in the identifying column.
     *
     * @param  list<string>  $legs
     * @return array<string, mixed>
     */
    private function coordinatesFor(array $legs, int $identifying): array
    {
        $coordinates = ['account_id' => self::TENANT];

        foreach ($legs as $leg) {
            if ($leg === 'account_id') {
                continue;
            }
            $coordinates[$leg] = $identifying;
        }

        return $coordinates;
    }

    /**
     * One witness per leg SHAPE, chosen from the discovered relations rather than
     * named. Sorted, so the choice is stable across runs.
     *
     * @param  list<array{child:string, parent:string, columns:list<string>}>  $relations
     * @return array{0:array{child:string, parent:string, columns:list<string>}, 1:array{child:string, parent:string, columns:list<string>}}
     */
    private function witnesses(array $relations): array
    {
        $byId = [];
        $byBotId = [];

        foreach ($relations as $relation) {
            $last = end($relation['columns']);
            if ($last === 'id') {
                $byId[] = $relation;
            }
            if ($last === 'bot_id') {
                $byBotId[] = $relation;
            }
        }

        usort($byId, static fn (array $a, array $b): int => $a['child'] <=> $b['child']);
        usort($byBotId, static fn (array $a, array $b): int => $a['child'] <=> $b['child']);

        return [$byId[0], $byBotId[0]];
    }

    /** The unique indexes the engine declares on `$table`, as `columns => unique`. */
    private function uniqueIndexes(Connection $db, string $table): array
    {
        $indexes = [];
        foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
            if (($index['unique'] ?? false) === true) {
                $indexes[] = array_map('strval', $index['columns']);
            }
        }

        return $indexes;
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the discovered family relations are the MEASURED ones
    // ------------------------------------------------------------------

    public function test_the_discovered_family_relations_are_the_measured_ones(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = $this->familyRelations($db);

        self::assertCount(
            self::MEASURED_RELATIONS,
            $discovered,
            'the number of single-parent relations inside this family moved. That is expected when the family '
            .'gains or loses a table — but then this constant AND the docblock in '.self::MIGRATION
            .'_add_tf_bot_family_foreign_keys.php are both stale. Re-measure on the target engine and bump BOTH. '
            .'Never edit this count to reach a green run: it is the only thing standing between a deleted mirror '
            .'table and a silent green.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused). If only one of '
            .'those two numbers moved, the constant and the REFUSED list have drifted apart.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the family SELECTION is exhaustive, not merely a count
    // ------------------------------------------------------------------

    /**
     * Every table in a family namespace that descends from inside the family is
     * IN the family. A one-root rule would miss the whole `tf_attach_menu_bots`
     * half and still report a plausible count; this is what catches it.
     */
    public function test_every_family_namespace_table_that_has_a_family_parent_is_constrained(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $descendants = $this->descendantsInsideFamily($db);
        self::assertNotSame(
            [],
            $descendants,
            'the ancestry rule found no family descendant at all, so this proof is vacuous — re-read '
            .'bin/generate-relation-graph.php before trusting it.',
        );

        $selected = array_column($this->familyRelations($db), 'child');

        $missed = [];
        foreach ($descendants as $relation) {
            if (in_array($relation['child'], $selected, true)) {
                continue;
            }
            $missed[] = $relation['child'].' -> '.$relation['parent'];
        }

        sort($missed);

        self::assertSame(
            [],
            $missed,
            'these tables live in a '.self::MIGRATION.' namespace and descend from inside the family, yet no '
            .'constraint was derived for them: '.implode(', ', $missed).'. Either FAMILY_NAMESPACES is missing a '
            .'namespace, or the ancestry rule moved.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — BOTH JOIN SHAPES EXIST AND ARE CONSTRAINED
    // ------------------------------------------------------------------

    /**
     * The hazard this family is sized for is that it holds two leg shapes and a
     * uniform treatment would only ever handle one. This asserts both are here.
     */
    public function test_both_join_shapes_are_present_and_both_are_constrained(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $relations = $this->familyRelations($db);
        $refused = $this->refused();

        $shapes = [];
        foreach ($relations as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }
            $shapes[implode(',', $relation['columns'])][] = $relation['child'];
        }

        ksort($shapes);

        self::assertArrayHasKey(
            'account_id,bot_id',
            $shapes,
            'no relation in this family joins on (account_id,bot_id), so the family no longer holds two shapes. '
            .'If that is real, re-measure and say so in the migration docblock; if it is a discovery failure, this '
            .'is where it shows.',
        );
        self::assertArrayHasKey(
            'account_id,id',
            $shapes,
            'no relation in this family joins on (account_id,id), so the family no longer holds two shapes.',
        );

        // And each shape is genuinely CONSTRAINED, on the legs it derives.
        foreach ($shapes as $legs => $children) {
            foreach ($children as $child) {
                $relation = null;
                foreach ($relations as $candidate) {
                    if ($candidate['child'] === $child) {
                        $relation = $candidate;
                        break;
                    }
                }
                Assert::assertNotNull($relation);

                $found = false;
                foreach ($db->getSchemaBuilder()->getForeignKeys($child) as $foreign) {
                    if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                        continue;
                    }
                    if (array_map('strval', $foreign['foreign_columns'] ?? []) === $relation['columns']) {
                        $found = true;
                        break;
                    }
                }

                self::assertTrue(
                    $found,
                    "{$child} joins {$relation['parent']} on [".implode(',', $relation['columns']).'] and carries no '
                    .'such constraint. A shape handled by name rather than by derivation is the failure this '
                    .'assertion exists for.',
                );
            }
        }
    }

    // ------------------------------------------------------------------
    // PROOF 4 — THE `bot_id` VERDICT, measured from the built schema
    // ------------------------------------------------------------------

    /**
     * Why the two `bot_id` relations are CONSTRAINED rather than refused.
     *
     * The prescribed remedy for a `(account_id, bot_id)` leg set whose parent
     * lacks a UNIQUE index is to leave the relation out (MySQL error 6125) and
     * record the mechanical reason. That remedy is only correct if the fact is
     * true — so this asserts it at runtime, from the engine's own index listing,
     * rather than trusting a paragraph.
     *
     * It also asserts the leg set is the parent's PRIMARY KEY and not merely a
     * column that happens to share the name: a same-named column is not the same
     * thing, and that distinction is exactly what family 4's hazard warns about.
     */
    public function test_every_bot_id_relation_references_its_parents_own_primary_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (end($relation['columns']) !== 'bot_id') {
                continue;
            }

            $child = $relation['child'];
            $parent = $relation['parent'];

            $unique = $this->uniqueIndexes($db, $parent);

            self::assertContains(
                $relation['columns'],
                $unique,
                "{$parent} declares NO UNIQUE index over (".implode(',', $relation['columns']).'), which is what '
                ."{$child} would reference. MySQL 8.4 refuses such a constraint with error 6125, so the correct "
                .'action is to move '.self::MIGRATION.'\'s entry for '.$child.' into its REFUSED list WITH this '
                .'reason and report it — NOT to add a unique index, because widening or narrowing a key is the one '
                .'thing this family is forbidden to do. UNIQUE indexes on '.$parent.': '
                .(json_encode($unique) ?: 'none').'.',
            );

            self::assertNotContains(
                $child,
                $refused,
                "{$child} joins {$parent} on (".implode(',', $relation['columns']).'), which the engine DOES hold '
                .'as a UNIQUE index — so refusing it would be declining a constraint that MySQL accepts. Either '
                .'REFUSED is stale or the index above is gone; this assertion fails rather than letting the two '
                .'disagree quietly.',
            );

            $checked++;
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no relation joins on bot_id, so this proof is vacuous — the family no longer holds the second shape '
            .'this whole verdict is about, and the migration docblock is stale.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_parent_foreign_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $missing = [];
        $wrong = [];

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                    continue;
                }
                if (array_map('strval', $foreign['foreign_columns'] ?? []) === $relation['columns']) {
                    $found = $foreign;
                    break;
                }
            }

            if ($found === null) {
                $missing[] = "{$relation['child']} -> {$relation['parent']} [".implode(',', $relation['columns']).']';

                continue;
            }

            $rule = strtoupper((string) ($found['on_delete'] ?? ''));
            if ($rule !== 'CASCADE') {
                $wrong[] = "{$relation['child']}: on_delete={$rule}";
            }
        }

        self::assertSame(
            [],
            $missing,
            'these family relations declare NO foreign key to their parent, so a child row naming a parent that '
            .'does not exist is admitted. Delete '.self::MIGRATION.'_add_tf_bot_family_foreign_keys.php and this '
            .'is the failure you get — which is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A bot fact\'s decomposed children have no life outside '
            .'it, so a re-delivered botInfo with a shorter field vector must take its old children with it, or '
            .'the dropped ones are orphaned under a re-placed parent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — CONTROL: BOTH orphans are ADMITTED without this migration
    // ------------------------------------------------------------------

    public function test_the_orphans_are_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and PROOF
        // 7 would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $this->plantTenant($permissive, self::TENANT);

        [$byId, $byBotId] = $this->witnesses($this->familyRelations($permissive));

        foreach ([$byId, $byBotId] as $witness) {
            $coordinates = $this->coordinatesFor($witness['columns'], self::ABSENT_ID);
            $permissive->table($witness['child'])->insert($this->rowFor($permissive, $witness['child'], $coordinates));

            self::assertSame(
                1,
                $permissive->table($witness['child'])->count(),
                'the orphan must actually LAND in '.$witness['child'].' without '.self::MIGRATION.'. Its tenant is '
                .'real, so if this row were refused the refusal would have come from the tenant constraint, and '
                .'PROOF 7 would be proving that instead of this migration.',
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 7 — the SAME orphans are REFUSED once the migration ships
    // ------------------------------------------------------------------

    public function test_the_orphans_are_refused_for_both_join_shapes(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);

        [$byId, $byBotId] = $this->witnesses($this->familyRelations($db));

        foreach ([[$byId, 'account_id,id'], [$byBotId, 'account_id,bot_id']] as [$witness, $shape]) {
            $coordinates = $this->coordinatesFor($witness['columns'], self::ABSENT_ID);

            try {
                $db->table($witness['child'])->insert($this->rowFor($db, $witness['child'], $coordinates));
                self::fail(
                    'a '.$witness['child'].' row naming '.$witness['parent'].' ('.$shape.') at '
                    .self::ABSENT_ID.', which does not exist, was ACCEPTED on a connection whose PRAGMA '
                    .'foreign_keys was read back as 1. Its tenant IS valid, so the tenant constraint cannot be '
                    .'what should have refused it — either this migration\'s constraint is absent, or enforcement '
                    .'is not on, and both mean this suite is not a gate.',
                );
            } catch (QueryException $e) {
                self::assertStringContainsString(
                    'FOREIGN KEY constraint failed',
                    $e->getMessage(),
                    'the insert must be refused BY THE FOREIGN KEY. Any other QueryException means the fixture is '
                    .'wrong — it is not evidence the constraint bit.',
                );
                self::assertStringContainsString(
                    '23000',
                    $e->getMessage(),
                    'sqlite reports a foreign-key refusal as SQLSTATE 23000; assert it so a different refusal class '
                    .'cannot satisfy this test.',
                );
            }

            self::assertSame(
                0,
                $db->table($witness['child'])->count(),
                'a refused insert must leave nothing behind.',
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 8 — a real child row LANDS for BOTH shapes, and CASCADE works
    // ------------------------------------------------------------------

    public function test_real_child_rows_land_for_both_shapes_and_deleting_the_parent_cascades(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);

        [$byId, $byBotId] = $this->witnesses($this->familyRelations($db));

        foreach ([$byId, $byBotId] as $witness) {
            $coordinates = $this->coordinatesFor($witness['columns'], self::PARENT_ID);

            $db->table($witness['parent'])->insert($this->rowFor($db, $witness['parent'], $coordinates));
            $childRow = $this->rowFor($db, $witness['child'], $coordinates);
            $db->table($witness['child'])->insert($childRow);

            $landed = $db->table($witness['child'])->first();
            self::assertNotNull(
                $landed,
                'the legitimate '.$witness['child'].' row must LAND. A constraint that refuses real engine rows is '
                .'worse than no constraint: it is an outage, not a safety net.',
            );

            foreach ($witness['columns'] as $leg) {
                self::assertSame(
                    $childRow[$leg],
                    $landed->{$leg},
                    "the constrained column {$leg} of {$witness['child']} must come back as the value inserted; a "
                    .'mismatch would mean the constraint is over a different column set than this fixture built.',
                );
            }

            $db->table($witness['parent'])
                ->where('account_id', self::TENANT)
                ->where(end($witness['columns']), self::PARENT_ID)
                ->delete();

            self::assertSame(
                0,
                $db->table($witness['child'])->count(),
                'deleting the parent must take its children with it (ON DELETE CASCADE). A child row outliving its '
                .'parent fact is the state this whole family constraint exists to prevent.',
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 9 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite
     * test and fails on the first real ALTER.
     */
    public function test_every_family_constraint_name_fits_the_engine_identifier_budget(): void
    {
        $limit = 64;

        $db = $this->enforcingConnection(...$this->migrations());
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        $longest = '';
        foreach ($this->familyRelations($db) as $relation) {
            $name = $relation['child'].$suffix;
            self::assertLessThanOrEqual(
                $limit,
                strlen($name),
                "the constraint name for {$relation['child']} is ".strlen($name)." characters, over MySQL's "
                ."{$limit}-character identifier limit, which it refuses with ERROR 1059. Shorten "
                .'CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the test and the migration '
                .'must agree on the same spelling.',
            );
            if (strlen($relation['child']) > strlen($longest)) {
                $longest = $relation['child'];
            }
        }

        self::assertNotSame('', $longest, 'discovery found no family relations, so nothing above is proven');
    }

    // ------------------------------------------------------------------
    // PROOF 10 — signedness, which sqlite cannot express
    // ------------------------------------------------------------------

    /**
     * Every constrained leg must match its parent's column SIGNEDNESS and type.
     *
     * This is the MySQL 3780 failure (`Referencing column 'x' and referenced
     * column 'y' in foreign key constraint 'f' are incompatible`), and it is the
     * one class of defect sqlite can report as green no matter what, because
     * sqlite has no signedness to disagree about.
     */
    public function test_every_constrained_leg_matches_its_parents_signedness(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $mismatch = [];
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            $childColumns = $this->columnTypes($db, $relation['child']);
            $parentColumns = $this->columnTypes($db, $relation['parent']);

            foreach ($relation['columns'] as $leg) {
                $child = $childColumns[$leg] ?? null;
                $parent = $parentColumns[$leg] ?? null;

                if ($child === null || $parent === null) {
                    $mismatch[] = "{$relation['child']}.{$leg} or {$relation['parent']}.{$leg} does not exist";

                    continue;
                }
                $checked++;

                if ($child !== $parent) {
                    $mismatch[] = sprintf(
                        '%s.%s is [%s] but %s.%s is [%s]',
                        $relation['child'], $leg, $child,
                        $relation['parent'], $leg, $parent,
                    );
                }
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no leg was compared, so this proof is vacuous — discovery found no constrained relation.',
        );
        self::assertSame(
            [],
            $mismatch,
            'a constrained leg disagrees with its parent\'s column type or signedness. MySQL refuses the ALTER '
            .'with error 3780 and sqlite cannot raise it at all, so this is the one defect class a sqlite-only suite '
            .'reports as green forever.',
        );
    }

    /**
     * `column_name => "TYPE SIGNEDNESS"`, normalised so the comparison is about the
     * thing MySQL actually refuses on.
     *
     * @return array<string, string>
     */
    private function columnTypes(Connection $db, string $table): array
    {
        $types = [];
        foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
            $name = (string) $column['name'];
            $type = strtolower((string) ($column['type'] ?? ''));

            $unsigned = str_contains($type, 'unsigned');
            $base = trim(str_replace(
                ['unsigned', 'signed', 'bigint', 'mediumint', 'smallint', 'tinyint', 'integer', 'int', ''],
                ' ',
                $type,
            ));
            $types[$name] = trim($base).($unsigned ? '|UNSIGNED' : '|SIGNED');
        }

        return $types;
    }
}
