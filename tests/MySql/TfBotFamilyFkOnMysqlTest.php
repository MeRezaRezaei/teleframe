<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * The `tf_bot_*` / `tf_attach_menu_bots` family constraints are ACCEPTED and ENFORCED
 * by real MySQL/InnoDB — for BOTH join shapes the family contains.
 *
 * ## WHY THIS LANE IS NOT OPTIONAL
 *
 * `2026_10_04_000850_add_tf_bot_family_foreign_keys.php` adds nineteen
 * `ALTER TABLE … ADD CONSTRAINT` statements. sqlite can express NONE of the five
 * ways MySQL rejects one, so a sqlite-only suite reports this migration green in
 * five distinct situations where a real install would have refused to migrate:
 *
 *   - 1452, a child row naming a parent that does not exist — ENFORCEMENT, which
 *     sqlite only raises with `PRAGMA foreign_keys` turned on by hand;
 *   - 3780, a referencing and a referenced column of incompatible type or
 *     SIGNEDNESS — sqlite has no signedness, so it cannot raise this at all;
 *   - 6125, a parent key that is not UNIQUE — sqlite accepts the reference. This
 *     is the error that decided this family's `bot_id` verdict, and this engine is
 *     the only thing that can prove the verdict right rather than merely argued;
 *   - 1059, a constraint name over 64 characters — sqlite has no identifier limit
 *     at all, which is exactly how the tenant migration's first spelling shipped
 *     green and was refused on the first real ALTER;
 *   - 1170, TEXT in a PRIMARY KEY.
 *
 * So the SQLite gate in `tests/Schema/Mirror/TfBotFamilyForeignKeyTest.php` proves
 * the constraints BITE, and this file proves MySQL ACCEPTS the same DDL and
 * enforces it.
 *
 * ## THE `bot_id` VERDICT IS DECIDED HERE, NOT ARGUED
 *
 * The family's hazard is that two relations join on `(account_id, bot_id)` while
 * the other seventeen join on `(account_id, id)`. A `(account_id, bot_id)` leg
 * set whose parent has no UNIQUE index over those columns is refused by this
 * engine with **6125**, and the prescribed remedy is to leave the relation out
 * and record the mechanical reason — never to add the missing index, because
 * widening or narrowing a key is forbidden.
 *
 * MEASURED on the executed dial (SQLite, `getIndexes()`): `tf_attach_menu_bots`
 * carries `PRIMARY KEY (account_id, bot_id)`, so `bot_id` is a column of the
 * parent's own PRIMARY KEY rather than merely a column that shares the name, and
 * the leg set references a UNIQUE index. That is the argument. This file is the
 * proof: `test_every_family_child_carries_a_constraint_to_its_parent_on_mysql()`
 * counts nineteen constraints on this engine, and the two `bot_id` relations are
 * among them. A parent key that was not unique could not produce a count of
 * nineteen — the DDL would have been refused at migrate time, before any
 * assertion could run.
 *
 * ## THE CONTROL HALF IS THE POINT OF THE FIRST TEST
 *
 * "The orphan insert was refused" is green for the wrong reason whenever the
 * session does not enforce. So the SAME orphan row is inserted twice: once with
 * `SET SESSION foreign_key_checks = 0`, where it MUST LAND, and once with it at 1,
 * where it MUST RAISE with MySQL's own error 1452. A harness that is
 * unconditionally permissive raises in both passes and fails the control half,
 * which is what makes the refusal half evidence.
 *
 * ## AND BOTH SHAPES ARE EXERCISED, NOT JUST THE FIRST ONE
 *
 * A suite that proved one shape would still be green if the `bot_id` half were
 * missing entirely — which is the specific failure this family is dispatched to
 * catch. So the behavioural proofs run once per shape, with the witness for each
 * shape DISCOVERED from the engine's own key listing rather than named.
 *
 * ## AND THE ROW SHAPES ARE NOT TYPED AGAINST A MIGRATION
 *
 * Every insert is checked against the EXECUTED table —
 * `information_schema.COLUMNS`, read off the engine after the dial ran — and the
 * test refuses to proceed unless the payload covers every NOT NULL column with no
 * default and names no column the engine does not have. A fixture that happens to
 * agree today would drift the day a column changes and the suite would keep
 * reporting green.
 */
final class TfBotFamilyFkOnMysqlTest extends MySqlTestCase
{
    /** A `telegram_accounts.user_id` this file plants, so the tenant FK cannot refuse. */
    private const TENANT = 1724372757;

    /** The planted parent's `id` / `bot_id` coordinate. */
    private const PARENT_ID = 424242;

    /** Coordinates for a parent that is NEVER planted — the orphan. */
    private const ABSENT_ID = 987654321;

    /**
     * The family's NAME-SPACES, matching the migration's.
     *
     * Two, not one: `tf_attach_menu_bots` extends no `tf_bot_*` table, so a
     * one-root rule would miss half the family and still report a plausible
     * count. Namespaces, not tables — every table inside them is read off this
     * engine.
     *
     * @var list<string>
     */
    private const FAMILY_NAMESPACES = ['tf_bot_', 'tf_attach_menu_bots'];

    /**
     * MEASURED on 2026-10-05: this family holds 19 single-parent relations and
     * constrains all 19. Asserted as a COUNT read back from this engine, because
     * "the migration ran" and "the migration did its work" are different claims
     * and only the count separates them.
     *
     * MOVE, measured 2026-10-11: 19 -> 18, and the engine is the authority for
     * the new figure — re-read from `information_schema` + `MirrorRelationPlan`
     * on a freshly migrated database, not reasoned from the migration.
     *
     * ONE table left the family and TWO relations changed shape, which is why
     * the net is minus one and not minus three:
     *   - `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
     *     dropped `tf_bot_infos_description` (a `flags.1?string` scalar on
     *     `botInfo`, so a fabricated table), removing the relation
     *     `tf_bot_infos_description -> tf_bot_infos`.        (-1)
     *   - `tf_bot_infos_description_photo` and `tf_bot_infos_description_document`
     *     were RE-PARENTED from that fabricated table onto `tf_bot_infos`, which
     *     is where `mirror-catalog.json` declared them all along. They stay in
     *     the family, so the count does not move for them.               (+0)
     *
     * The two unions are still constrained — the count proves the family shrank,
     * not that it lost coverage, and `EveryFkableRelationHasAConstraintTest`
     * independently refuses any single-parent relation with no constraint.
     */
    private const MEASURED_CONSTRAINED = 18;

    // ------------------------------------------------------------------
    // the two proofs
    // ------------------------------------------------------------------

    /**
     * For BOTH leg shapes: a valid child row lands, an orphan is refused with
     * error 1452, and the control half — the same orphan with
     * `foreign_key_checks = 0` — lands.
     */
    public function test_both_join_shapes_land_refuse_with_1452_and_land_again_with_checks_off(): void
    {
        $db = $this->migratedMysqlConnection();

        $relations = $this->discoveredFamilyRelations($db);
        $witnesses = $this->witnessesByShape($relations);

        self::assertCount(
            2,
            $witnesses,
            'the family no longer holds two join shapes on this engine, so the per-shape proofs below would run '
            .'against one shape and pass while the other was unhandled. Shapes found: '
            .implode(', ', array_map(static fn (array $w): string => implode(',', $w['columns']), $witnesses)).'. '
            .'If that is real, re-measure and say so in the migration docblock; if it is a discovery failure, this '
            .'is where it shows.',
        );

        // Planted ONCE, outside the loop: both witnesses live under the SAME
        // tenant, and `telegram_accounts.user_id` is that table's primary key — so
        // re-planting it per witness makes the second insert a duplicate-key error
        // and the shape being proved is never reached.
        $this->seedTenant($db);

        foreach ($witnesses as $witness) {
            $shape = implode(',', $witness['columns']);

            $this->seedParent($db, $witness, self::PARENT_ID);

            // ---- THE POSITIVE HALF: a well-parented row MUST land. ----
            $row = $this->childRow($db, $witness, self::PARENT_ID);
            $db->table($witness['child'])->insert($row);

            $landed = $db->table($witness['child'])->where('account_id', self::TENANT)
                ->where(end($witness['columns']), $this->legValue($db, $witness, self::PARENT_ID))
                ->first();

            self::assertNotNull(
                $landed,
                "the well-parented {$witness['child']} row ({$shape}) did not LAND. A constraint that refuses real "
                .'engine rows is worse than no constraint: it is an outage, not a safety net.',
            );

            foreach ($witness['columns'] as $leg) {
                self::assertSame(
                    $row[$leg],
                    $landed->{$leg},
                    "the constrained column {$leg} of {$witness['child']} came back as a different value, so the "
                    .'constraint is over another column set than the one this fixture built.',
                );
            }

            // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
            $orphan = $this->childRow($db, $witness, self::ABSENT_ID);

            $controlLanded = false;
            $db->statement('SET SESSION foreign_key_checks = 0');
            try {
                $db->table($witness['child'])->insert($orphan);
                $controlLanded = $db->table($witness['child'])->where('account_id', self::TENANT)
                    ->where(end($witness['columns']), $this->legValue($db, $witness, self::ABSENT_ID))
                    ->exists();
            } finally {
                $db->statement('SET SESSION foreign_key_checks = 1');
            }

            self::assertTrue(
                $controlLanded,
                "with foreign_key_checks = 0 the {$witness['child']} ({$shape}) orphan MUST land. If it did not, this "
                .'control half is not measuring what it claims and the refusal half below proves nothing.',
            );

            // The control row is removed so the enforced half measures the SECOND
            // insert alone and the table is left holding exactly what it should.
            $db->table($witness['child'])->where('account_id', self::TENANT)
                ->where(end($witness['columns']), $this->legValue($db, $witness, self::ABSENT_ID))
                ->delete();

            // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
            $thrown = null;
            try {
                $db->table($witness['child'])->insert($orphan);
            } catch (QueryException $e) {
                $thrown = $e;
            }

            self::assertNotNull(
                $thrown,
                sprintf(
                    'the orphan %s row naming %s (%s) at %d, which does not exist, was ACCEPTED with '
                    .'foreign_key_checks = 1. Its tenant IS real, so the tenant FK cannot be what should have '
                    .'refused it — either 2026_10_04_000850 did not create the constraint, or this engine is not '
                    .'enforcing.',
                    $witness['child'],
                    $witness['parent'],
                    $shape,
                    self::ABSENT_ID,
                ),
            );

            // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the
            // SQLSTATE slot (which is the generic HY000/23000). Asserting the
            // driver code is what makes this MySQL evidence rather than "some
            // error happened".
            self::assertSame(
                1452,
                (int) ($thrown->errorInfo[1] ?? 0),
                "expected MySQL error 1452 (cannot add or update a child row) for {$witness['child']} ({$shape}); "
                .'got: '.$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
            );

            self::assertSame(
                0,
                $db->table($witness['child'])->where('account_id', self::TENANT)
                    ->where(end($witness['columns']), $this->legValue($db, $witness, self::ABSENT_ID))
                    ->count(),
                'the refused orphan insert left a row behind, so the constraint fired late rather than not at all.',
            );
        }
    }

    /**
     * ON DELETE CASCADE reaches the child on the engine that honours it.
     *
     * The rule is READ BACK from `information_schema` rather than assumed from the
     * migration text, so a later migration that retargets or drops the leg fails
     * here with the actual delete rule in the message.
     */
    public function test_deleting_the_parent_cascades_on_mysql_for_both_shapes(): void
    {
        $db = $this->migratedMysqlConnection();

        $witnesses = $this->witnessesByShape($this->discoveredFamilyRelations($db));

        // Planted ONCE, outside the loop — `telegram_accounts.user_id` is its
        // primary key, so re-planting per witness would fail the second shape on a
        // duplicate key before the cascade is ever exercised.
        $this->seedTenant($db);

        foreach ($witnesses as $witness) {
            $this->seedParent($db, $witness, self::PARENT_ID);

            $db->table($witness['child'])->insert($this->childRow($db, $witness, self::PARENT_ID));

            self::assertSame(
                1,
                $db->table($witness['child'])->where('account_id', self::TENANT)
                    ->where(end($witness['columns']), $this->legValue($db, $witness, self::PARENT_ID))
                    ->count(),
                "the seeded {$witness['child']} row is not there, so the cascade below would prove nothing",
            );

            self::assertSame(
                'CASCADE',
                strtoupper((string) ($this->deleteRule($db, $witness['child'], $witness['parent']) ?? '')),
                "{$witness['child']} -> {$witness['parent']} is not ON DELETE CASCADE on this engine, so deleting "
                .'the parent cannot reach the child and this test would be asserting nothing.',
            );

            $deleted = $db->table($witness['parent'])->where('account_id', self::TENANT)
                ->where(end($witness['columns']), $this->legValue($db, $witness, self::PARENT_ID))
                ->delete();

            self::assertSame(1, $deleted, "the parent {$witness['parent']} row itself was not deleted");

            self::assertSame(
                0,
                $db->table($witness['child'])->count(),
                "deleting the parent must cascade to {$witness['child']} on MySQL. A cascade proven on sqlite "
                .'proves the pragma, not the engine.',
            );
        }
    }

    /**
     * Every family child carries a constraint on this engine, at the legs this
     * engine says its parent is keyed by.
     *
     * `migratedMysqlConnection()` already proves the DDL was ACCEPTED; this proves
     * it did its WORK, at the derived count. A dial that silently skipped this
     * family would still migrate cleanly.
     */
    public function test_every_family_child_carries_a_constraint_to_its_parent_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $relations = $this->discoveredFamilyRelations($db);

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            $relations,
            'the number of single-parent relations discovered inside this family on THIS engine moved from the '
            .'measured figure. Re-measure on the target engine and bump both this constant and the migration '
            .'docblock — never edit the count to reach a green run.',
        );

        $unconstrained = [];
        $notCascading = [];

        foreach ($relations as $relation) {
            $found = null;
            foreach ($this->foreignKeys($db, $relation['child']) as $foreign) {
                if ($foreign['parent'] !== $relation['parent']) {
                    continue;
                }
                if ($foreign['legs'] === $relation['columns']) {
                    $found = $foreign;
                    break;
                }
            }

            if ($found === null) {
                $unconstrained[] = $relation['child'].' -> '.$relation['parent'].' ['
                    .implode(',', $relation['columns']).']';

                continue;
            }

            if ($found['delete_rule'] !== 'CASCADE') {
                $notCascading[] = $relation['child'].': '.$found['delete_rule'];
            }
        }

        self::assertSame(
            [],
            $unconstrained,
            'these family children carry NO foreign key to their parent on this engine: '.implode(', ', $unconstrained)
            .'. Either the family migration did not run, or a new family member landed without a lane.',
        );
        self::assertSame(
            [],
            $notCascading,
            'these family constraints exist but do not CASCADE: '.implode(', ', $notCascading),
        );
    }

    // ------------------------------------------------------------------
    // discovery — read off THIS engine, never transcribed
    // ------------------------------------------------------------------

    /**
     * The family's single-parent relations, discovered by PRIMARY-KEY containment
     * on this engine.
     *
     * Deliberately NOT `MirrorRelationPlan`: that is the class the migration uses,
     * so asking it here would make this file a restatement rather than a second
     * opinion. The rule is re-implemented against `information_schema`: a family
     * table is a child when some OTHER family table's PRIMARY KEY is a strict
     * subset of its own, and the legs are that parent's key — which is what a
     * FOREIGN KEY must reference.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function discoveredFamilyRelations(Connection $db): array
    {
        $family = $this->familyTables($db);
        $tableSet = array_flip($family);

        $primaryKeys = [];
        foreach ($family as $table) {
            $primaryKeys[$table] = $this->primaryKeyOf($db, $table);
        }

        $relations = [];
        foreach ($family as $table) {
            $key = $primaryKeys[$table];
            if ($key === []) {
                continue;
            }

            // THE PARENT IS FOUND BY WALKING NAME SEGMENTS FROM THE RIGHT, and
            // the parent's key must be a SUBSET of the child's — not a STRICT
            // subset.
            //
            // Both halves were wrong here, and both were visible only on MySQL:
            //
            //  - **strict** subset. MEASURED on this engine, 22 of the family's 23
            //    tables share the IDENTICAL primary key `(account_id, id)`. A rule
            //    that demands `count(parent) < count(child)` therefore finds NO
            //    parent for any of them, and discovery returned 3 relations where
            //    the family holds 19. That surfaced as
            //
            //        Failed asserting that actual size 3 matches expected size 19.
            //
            //  - an ARBITRARY tie-break. With every 2-column key equal to every
            //    other, "the family table with the smallest key" was whichever
            //    sorted first, so `tf_bot_infos_commands` was assigned the parent
            //    `tf_bot_apps` while its real FK constraint targets `tf_bot_infos`.
            //    The fixture then planted a parent the constraint does not name,
            //    and the WELL-PARENTED half raised 1452:
            //
            //        Integrity constraint violation: 1452 … `tf_bot_infos_commands`,
            //        CONSTRAINT `tf_bot_infos_commands_bot_parent_fk`
            //        FOREIGN KEY (`account_id`, `id`) REFERENCES `tf_bot_infos`
            //
            //    Two 1452s in this file were this discovery bug, not a schema
            //    defect and not a bad fixture in the sense of a planted orphan.
            //
            // Walking the name segments from the right is what makes the parent
            // DETERMINISTIC: `tf_bot_infos_commands` splits to
            // [tf, bot, infos, commands], and the walk takes the longest family
            // prefix that is a real table — `tf_bot_infos` — before reaching
            // `tf_bot`. It is also the rule `tests/Schema/Mirror/
            // TfBotFamilyForeignKeyTest::familyRelations()` uses, so this file and
            // its SQLite sibling cannot disagree about what the family is.
            $parts = explode('_', $table);
            $parent = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate])) {
                    continue;
                }
                if (array_diff($primaryKeys[$candidate], $key) !== []) {
                    continue;
                }
                $parent = $candidate;
                break;
            }

            if ($parent === null) {
                continue;
            }

            // The legs are the parent's key ORDER, which is what a FOREIGN KEY
            // must reference — intersected with the columns the child really has.
            $legs = [];
            foreach ($primaryKeys[$parent] as $leg) {
                $legs[] = $leg;
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        usort($relations, static fn (array $a, array $b): int => $a['child'] <=> $b['child']);

        return $relations;
    }

    /**
     * One witness per leg SHAPE, keyed by the LAST leg — `id` and `bot_id` are the
     * two shapes this family holds, and which tables carry them is read off the
     * engine, not named here.
     *
     * @param  list<array{child:string, parent:string, columns:list<string>}>  $relations
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function witnessesByShape(array $relations): array
    {
        $byShape = [];
        foreach ($relations as $relation) {
            $byShape[implode(',', $relation['columns'])][] = $relation;
        }

        $wanted = ['account_id,id', 'account_id,bot_id'];
        $witnesses = [];
        foreach ($wanted as $shape) {
            if (! isset($byShape[$shape])) {
                continue;
            }
            usort($byShape[$shape], static fn (array $a, array $b): int => $a['child'] <=> $b['child']);
            $witnesses[] = $byShape[$shape][0];
        }

        return $witnesses;
    }

    /**
     * @return list<string>
     */
    private function familyTables(Connection $db): array
    {
        $rows = $db->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()',
        );

        $members = [];
        foreach ($rows as $row) {
            $name = (string) $row->name;
            foreach (self::FAMILY_NAMESPACES as $namespace) {
                if (str_starts_with($name, $namespace)) {
                    $members[] = $name;

                    break;
                }
            }
        }

        sort($members);

        return $members;
    }

    /**
     * @return list<string>
     */
    private function primaryKeyOf(Connection $db, string $table): array
    {
        $rows = $db->select(
            'SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = \'PRIMARY\'
              ORDER BY ORDINAL_POSITION',
            [$table],
        );

        return array_map(static fn (object $r): string => (string) $r->name, $rows);
    }

    /**
     * Every foreign key `$child` declares on this engine, with its legs in
     * declaration order and its delete rule read from `information_schema`.
     *
     * @return list<array{parent:string, legs:list<string>, delete_rule:string, name:string}>
     */
    private function foreignKeys(Connection $db, string $child): array
    {
        $legRows = $db->select(
            'SELECT kcu.REFERENCED_TABLE_NAME AS parent, kcu.COLUMN_NAME AS name,
                    kcu.CONSTRAINT_NAME AS constraint_name, kcu.ORDINAL_POSITION AS ordinal
               FROM information_schema.KEY_COLUMN_USAGE kcu
              WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                AND kcu.TABLE_NAME = ?
                AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION',
            [$child],
        );

        $grouped = [];
        foreach ($legRows as $row) {
            $grouped[$row->constraint_name][] = $row;
        }

        $rules = [];
        foreach ($db->select(
            'SELECT CONSTRAINT_NAME AS name, DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$child],
        ) as $row) {
            $rules[(string) $row->name] = (string) $row->rule_name;
        }

        $keys = [];
        foreach ($grouped as $name => $legs) {
            $keys[] = [
                'name' => (string) $name,
                'parent' => (string) $legs[0]->parent,
                'legs' => array_map(static fn (object $r): string => (string) $r->name, $legs),
                'delete_rule' => $rules[(string) $name] ?? '',
            ];
        }

        return $keys;
    }

    /** The delete rule MySQL reports for `$child` → `$parent`, or null. */
    private function deleteRule(Connection $db, string $child, string $parent): ?string
    {
        foreach ($this->foreignKeys($db, $child) as $foreign) {
            if ($foreign['parent'] === $parent) {
                return $foreign['delete_rule'];
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // fixtures — every payload checked against the EXECUTED table
    // ------------------------------------------------------------------

    /**
     * The tenant row, exactly as the executed `telegram_accounts` requires it.
     */
    private function seedTenant(Connection $db): void
    {
        $row = [
            'user_id' => self::TENANT,
            'label' => 'mysql-bot-family-probe',
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * The parent row, built from the engine's own column list so every NOT NULL
     * column with no default is covered.
     *
     * @param  array{child:string, parent:string, columns:list<string>}  $witness
     */
    private function seedParent(Connection $db, array $witness, int $coordinate): void
    {
        $coordinates = ['account_id' => self::TENANT];
        foreach ($witness['columns'] as $leg) {
            if ($leg === 'account_id') {
                continue;
            }
            $coordinates[$leg] = $this->legValue($db, $witness, $coordinate);
        }

        $row = $this->rowFor($db, $witness['parent'], $coordinates);
        $this->assertRowCoversRequiredColumns($db, $witness['parent'], $row);
        $db->table($witness['parent'])->insert($row);
    }

    /**
     * The child row. The parent coordinate is the parameter, so the same builder
     * produces the well-parented row and the orphan — which is what makes the two
     * halves of the first test the SAME insert.
     *
     * @param  array{child:string, parent:string, columns:list<string>}  $witness
     */
    private function childRow(Connection $db, array $witness, int $coordinate): array
    {
        $coordinates = ['account_id' => self::TENANT];
        foreach ($witness['columns'] as $leg) {
            if ($leg === 'account_id') {
                continue;
            }
            $coordinates[$leg] = $this->legValue($db, $witness, $coordinate);
        }

        $row = $this->rowFor($db, $witness['child'], $coordinates);
        $this->assertRowCoversRequiredColumns($db, $witness['child'], $row);

        return $row;
    }

    /**
     * A value for one constrained leg, typed by the PARENT's declared column type.
     *
     * `tf_bot_inline_results` is keyed on a TL `string` and its `id` is `varchar`,
     * while `tf_bot_apps`/`tf_bot_infos` are keyed on an integer — so a leg's PHP
     * type is a property of the parent, not of the leg name, and typing it here by
     * hand would be MySQL 3780 waiting to happen.
     *
     * @param  array{child:string, parent:string, columns:list<string>}  $witness
     */
    private function legValue(Connection $db, array $witness, int $coordinate): mixed
    {
        $leg = end($witness['columns']);
        if ($leg === 'account_id') {
            return self::TENANT;
        }

        $type = $this->columnType($db, $witness['parent'], (string) $leg);

        if (str_contains($type, 'char') || str_contains($type, 'text') || str_contains($type, 'blob')) {
            return (string) $coordinate;
        }

        return $coordinate;
    }

    /**
     * A row for `$table` covering every column this engine declares, with the
     * forced coordinates applied and the rest filled from their declared types.
     *
     * @param  array<string, mixed>  $forced
     */
    private function rowFor(Connection $db, string $table, array $forced): array
    {
        $row = [];
        foreach ($this->columnTypes($db, $table) as $name => $type) {
            $row[$name] = array_key_exists($name, $forced) ? $forced[$name] : $this->placeholderFor($type);
        }

        return $row;
    }

    /** A legal value for a declared MySQL column type, chosen by type not by table. */
    private function placeholderFor(string $type): mixed
    {
        $type = strtolower($type);

        if (str_contains($type, 'blob')) {
            return 'x';
        }

        if (str_contains($type, 'char') || str_contains($type, 'text') || str_contains($type, 'enum')
            || str_contains($type, 'json')) {
            return 'x';
        }

        if (str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal')) {
            return 0;
        }

        return 0;
    }

    /** @return array<string, string> */
    private function columnTypes(Connection $db, string $table): array
    {
        $rows = $db->select(
            'SELECT COLUMN_NAME AS name, COLUMN_TYPE AS type FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );

        $types = [];
        foreach ($rows as $row) {
            $types[(string) $row->name] = (string) $row->type;
        }

        return $types;
    }

    private function columnType(Connection $db, string $table, string $column): string
    {
        return $this->columnTypes($db, $table)[$column] ?? '';
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it names
     * exists, and it supplies every NOT NULL column the engine has no default for.
     *
     * Read off the engine rather than typed from the migration, so this is a
     * statement about the schema that will actually receive the insert.
     */
    private function assertRowCoversRequiredColumns(Connection $db, string $table, array $row): void
    {
        $columns = $db->select(
            'SELECT COLUMN_NAME AS name, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS def, EXTRA AS extra
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );

        self::assertNotEmpty($columns, "no columns found for {$table} — the dial did not create it");

        $present = [];
        $required = [];

        foreach ($columns as $column) {
            $name = (string) $column->name;
            $present[] = $name;

            $autoIncrement = stripos((string) $column->extra, 'auto_increment') !== false;
            $hasDefault = $column->def !== null;

            if (strtoupper((string) $column->nullable) === 'NO' && ! $hasDefault && ! $autoIncrement) {
                $required[] = $name;
            }
        }

        foreach (array_keys($row) as $name) {
            self::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — the fixture is written against a schema the "
                .'shipped dial does not produce.',
            );
        }

        $missing = array_values(array_diff($required, array_keys($row)));

        self::assertSame(
            [],
            $missing,
            "the {$table} insert omits NOT NULL columns with no default: ".implode(', ', $missing),
        );
    }
}
