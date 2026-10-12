<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\MySql\UpdateDifferencesMiscFamilyFkOnMysqlTest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

/**
 * The miscellaneous update-differences family is constrained to its parent, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000900_add_update_differences_misc_family_foreign_keys.php` adds a
 * FOREIGN KEY from every single-parent relation under the five roots below to
 * that parent's own key. This file proves the constraints BITE — that a valid
 * child row lands, that an orphan is refused by the constraint and not by
 * anything else, that deleting the parent cascades, and that the whole thing
 * disappears when the migration does.
 *
 * ## WHY THIS FAMILY IS THE HETEROGENEOUS ONE, AND WHAT "FIVE SHAPES" ACTUALLY MEANS
 *
 * The plan calls family 10 "the most heterogeneous (five different join shapes:
 * `(account_id, position)`, `(account_id, seq, position)`, `(account_id, link)`,
 * `(account_id, saved_reaction_tag_id)`, `(account_id, id)`) … split it further
 * if the helpers do not unify."
 *
 * MEASURED 2026-10-05 on this tree, `MirrorRelationPlan` derives exactly those
 * five shapes and 9 relations. The heterogeneity is real, and
 * `test_every_derived_leg_is_the_parent_s_own_key()` pins it so it cannot
 * quietly collapse.
 *
 * It is heterogeneity in the PARENTS' KEYS, not in the link MECHANISM — and that
 * distinction is what decides the split. The helper never chooses the legs; it
 * applies the legs the plan derived. So one helper covers all five shapes
 * WITHOUT transcribing any of them, and five helpers would be five byte-identical
 * functions. The honest reading of "split if the helpers do not unify" is
 * therefore "one helper", and the evidence that it is not a forced coercion is
 * `test_every_derived_leg_is_the_parent_s_own_key()`: each of the five shapes is
 * asserted to equal its own parent's PRIMARY key exactly, so no shape is being
 * squeezed into another one's leg set.
 *
 * What genuinely needed more than one constant is the FAMILY DEFINITION. The
 * sibling families are a single root plus a name prefix; this one is FIVE roots
 * with no common prefix, so membership is a set of roots (below) rather than
 * one root — and every relation is still discovered from the plan, never listed.
 *
 * ## WHY THIS FAMILY'S FOUR `position` LEGS WERE PREVIOUSLY THOUGHT TO BE CONSTRAINED
 *
 * `2026_09_14_200031_create_tf_channel_participants_tables.php` ships
 * `differenceLink()`, which declares exactly these four constraints inline and
 * with an explicit `->name()`. MEASURED 2026-10-05, walking the dial one file at
 * a time, the constraint is ABSENT the instant that file finishes — the DDL
 * recorded in `sqlite_master` has no FOREIGN KEY clause at all. It is not
 * dropped later; it is never created.
 *
 * The mechanism, measured, not inferred — a three-case reproduction on this exact
 * vendor tree:
 *
 * ```
 * inline ->foreign()->...->name('x')  inside Schema::create()  →  NO foreign key clause
 * inline ->foreign()->...             inside Schema::create()  →  foreign key clause PRESENT
 * ```
 *
 * `Blueprint::foreign()` registers its command under the name `foreign`
 * (`indexCommand('foreign', …)`), and `SQLiteGrammar::compileCreate()` collects
 * them with `getCommandsByName($blueprint, 'foreign')`, which matches on
 * `$command->name`. Chaining `->name('fk_diff_link_…')` OVERWRITES that attribute
 * with the constraint name, so the filter matches nothing and the clause is
 * silently omitted. `MySqlGrammar::compileCreateTable()` never emits foreign
 * commands at all, so the same inline declaration is dropped on MySQL as well —
 * which is why this family's four constraints were missing on BOTH engines and
 * why adding them here is an addition rather than a repair.
 *
 * `Schema::table()` is unaffected (it compiles `foreign` by command type rather
 * than by attribute name), which is why the constraints in
 * `2026_09_14_299999_create_tf_foreign_keys.php` do exist.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP EVERY FIXTURE HERE IS BUILT AROUND
 *
 * Every `tf_*` table in this family carries its own tenant FK on `account_id` to
 * `telegram_accounts.user_id`. An "orphan" built naively — inventing an
 * `account_id` no tenant carries — is refused by the TENANT constraint, and every
 * behavioural assertion below would be green while this migration contributed
 * nothing. So every fixture plants a REAL tenant first and makes the orphan-ness
 * live ONLY in the parent's own key coordinates. The tenant is valid, the parent
 * does not exist, so the only constraint that can refuse these rows is this
 * migration's.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * into the SAME table with exactly one file withheld from the dial, on a
 * connection whose `PRAGMA foreign_keys` was read back as **1** — enforcement
 * genuinely ON, the constraint genuinely absent — and requires the row to LAND.
 * That is what makes the refusal attributable to this migration rather than to
 * SQLite, to the pragma, or to the tenant constraint.
 *
 * @see UpdateDifferencesMiscFamilyFkOnMysqlTest
 *      for the same proofs on the target engine, which is where error 6125 and
 *      error 3780 are actually raised.
 */
final class UpdateDifferencesMiscFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000900';

    /**
     * The family's FIVE ROOTS, and the ONLY thing written here that looks like a
     * table list.
     *
     * A family is a set of roots plus a name-prefix closure, because this family
     * has five roots that share NO common prefix — unlike the sibling families,
     * which are one root and its descendants. Each entry is a PARENT name, and no
     * CHILD table, join column, or constraint is written anywhere in this file:
     * the relations themselves come from discovery, and `Discovery` below
     * re-derives them from the BUILT schema by its own code, so a root that
     * stops being a parent is caught by the count assertion rather than sitting
     * here as a silent no-op.
     *
     * MEASURED 2026-10-05 by reading the parents out of the gate's own RED list:
     *
     *   timeout -k 10 900 vendor/bin/phpunit --no-coverage \
     *     --filter test_every_single_parent_relation_has_a_real_constraint \
     *     tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *     | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *     | grep -E "→ tf_update_differences$|→ tf_updates_pts$|→ tf_business_chat_links$|→ tf_wallpapers$|→ tf_saved_reaction_tags$"
     *
     * @var list<string>
     */
    private const FAMILY_ROOTS = [
        'tf_update_differences',
        'tf_updates_pts',
        'tf_business_chat_links',
        'tf_wallpapers',
        'tf_saved_reaction_tags',
    ];

    /**
     * The number of single-parent relations MEASURED inside this family on
     * 2026-10-05, and the number this migration therefore constrains.
     *
     * Both are 9 and neither is 9 by arithmetic: `test_the_discovered_family_
     * relations_are_the_measured_ones` asserts the total AND the per-shape
     * breakdown, so a family that gains a table, loses one, or moves a relation
     * between shapes all fail here. Never edit this number to reach a green run —
     * re-measure with the command in the `FAMILY_ROOTS` docblock and bump BOTH
     * here and in the migration.
     */
    private const MEASURED_RELATIONS = 9;

    private const MEASURED_CONSTRAINED = 9;

    /**
     * The measured per-shape breakdown, `leg set => child count`.
     *
     * Pinned because "five shapes" is the plan's word and this file's whole
     * argument about NOT splitting rests on the heterogeneity being exactly this.
     * If a shape appears or vanishes the unification argument has to be re-made,
     * and that is what this assertion forces.
     *
     * @var array<string, int>
     */
    private const MEASURED_SHAPES = [
        'account_id,id' => 1,
        'account_id,link' => 2,
        'account_id,position' => 4,
        'account_id,saved_reaction_tag_id' => 1,
        'account_id,seq,position' => 1,
    ];

    /** A `telegram_accounts.user_id` every fixture below plants first. */
    private const TENANT = 1724372757;

    /**
     * Parent coordinates that are NEVER planted — the orphan, per shape.
     *
     * Distinct from the planted coordinates below so the control half inserts a
     * row whose parent is genuinely absent, and so a fixture that accidentally
     * reuses the planted coordinates fails its own assertion.
     */
    private const ABSENT = [
        'position' => 4242,
        'seq' => 4242,
        'link' => 'absent-link',
        'saved_reaction_tag_id' => 4242,
        'id' => 4242,
    ];

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * From `UpdateIngestor::migrationPaths()` — the same list the ingest seam and
     * the MySQL lane run — rather than a glob of `src/Laravel/Migrations`,
     * because a glob here is a second copy of the dial and a second copy drifts.
     * This migration's own file lives in that directory, so moving it out of the
     * curated dial fails this suite rather than quietly testing a dial without it.
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
                // applies two migrations twice. Deduped by real path, keeping
                // apply order, because the directory is listed first and owns the
                // position of both.
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
     * spelling change that broke the rollback, so the suffix and the refusal list
     * are read out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_update_differences_misc_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new ReflectionClass($migration))->getConstant($name);
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented
    // ------------------------------------------------------------------

    /**
     * The family relations, discovered from the BUILT schema by this file's own
     * code — NOT by asking `MirrorRelationPlan`, which is the class the migration
     * uses and therefore the thing under test.
     *
     * The rule, independently implemented: a family member's parent is the LONGEST
     * surviving table-name prefix that is one of the five roots and whose primary
     * key covers this table's; the constraint's legs are that parent's key
     * intersected down to the columns this table actually has.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        $prefix = $db->getTablePrefix();

        $tables = [];
        foreach ($db->getSchemaBuilder()->getTableListing() as $listed) {
            $bare = str_contains($listed, '.')
                ? substr($listed, (int) strrpos($listed, '.') + 1)
                : $listed;
            if ($prefix !== '' && str_starts_with($bare, $prefix)) {
                $bare = substr($bare, strlen($prefix));
            }
            $tables[] = $bare;
        }
        sort($tables);
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
            // must be declared in the parent's key ORDER, which is not the order
            // the columns sit in the table.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        $family = [];
        foreach ($tables as $table) {
            foreach (self::FAMILY_ROOTS as $root) {
                if ($table === $root || str_starts_with($table, $root.'_')) {
                    $family[$table] = true;
                }
            }
        }

        $relations = [];
        foreach ($tables as $table) {
            // Only a table INSIDE the family closure can carry a family relation,
            // and a root is the parent of its descendants rather than a child.
            if (! isset($family[$table]) || in_array($table, self::FAMILY_ROOTS, true)) {
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

            // The legs are the parent's key intersected down to the columns this
            // child really has — the same rule `MirrorRelationPlan` documents, so
            // this independent implementation and the class under test derive the
            // same leg set from the same executed schema.
            $legs = [];
            foreach ($primaryKey[$parent] as $leg) {
                if (isset($columns[$table][$leg])) {
                    $legs[] = $leg;
                }
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        return $relations;
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

        // The filter that reads REFUSED is only exercised by a family that has
        // one. This file's is EMPTY — measured, not omitted: all 9 relations carry
        // their parent's own PRIMARY key with matching column types, so none needs
        // an exception. Asserted empty so that claim cannot rot into an unexamined
        // constant, and the sibling `tf_messages` family's non-empty list is what
        // proves the filter itself works.
        self::assertSame(
            [],
            $refused,
            'REFUSED is no longer empty. If a relation in this family now needs a refusal, it needs a '
            .'MECHANICAL reason and this assertion must be replaced by the shape checks the sibling '
            .'TfMessagesFamilyForeignKeyTest uses — a refusal that appeared without a reason, or aimed at '
            .'a table outside this family, must not pass silently.',
        );

        return array_keys($refused);
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /**
     * One witness per JOIN SHAPE, with the parent row, the child row, and any
     * ANCESTOR rows the parent itself needs.
     *
     * The ancestor list is not decoration: `tf_updates_pts` carries its OWN
     * foreign key to `tf_updates` (shipped by `linkChild()` in
     * `2026_09_14_200030_create_tf_updates_tables.php`), so planting it requires
     * the grandparent row first. Without it the fixture dies with a foreign-key
     * error before reaching the constraint under test.
     *
     * @return array{shape:string, child:string, parent:string, parentRow:array<string,mixed>, childRow:array<string,mixed>, ancestors:list<array{0:string, 1:array<string,mixed>}>, planted:array<string,mixed>, absent:array<string,mixed>}
     */
    private static function witness(string $child, string $parent, string $shape): array
    {
        return [
            'shape' => $shape,
            'child' => $child,
            'parent' => $parent,
            'parentRow' => self::PARENT_ROWS[$parent],
            'childRow' => self::CHILD_ROWS[$child],
            'ancestors' => self::ANCESTOR_ROWS[$parent] ?? [],
            'planted' => self::PLANTED[$parent],
            'absent' => self::ABSENT,
        ];
    }

    /**
     * Every parent row, keyed by parent, with the PLANTED coordinates inline.
     *
     * Column names here are fixture data, not a schema transcription: each row is
     * asserted against the EXECUTED table by `assertRowCoversExistingColumns()`
     * before it is used, so a fixture naming a column the dial does not produce
     * fails loudly instead of drifting.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PARENT_ROWS = [
        'tf_update_differences' => ['account_id' => self::TENANT, 'position' => 1, 'constructor' => 'differenceEmpty'],
        'tf_updates_pts' => ['account_id' => self::TENANT, 'seq' => 1, 'position' => 2, 'pts' => 99],
        'tf_business_chat_links' => ['account_id' => self::TENANT, 'link' => 'live-link', 'message' => 'm', 'views' => 3],
        'tf_wallpapers' => ['account_id' => self::TENANT, 'id' => 5, 'constructor' => 'wallPaper'],
        'tf_saved_reaction_tags' => ['account_id' => self::TENANT, 'saved_reaction_tag_id' => 7],
    ];

    /**
     * Every witness child row, keyed by child.
     *
     * A child row is the CHILD's own columns — never the parent's row reused,
     * which is a fixture bug this family hit and fixed: `tf_update_differences_date`
     * carries no `constructor`, so reusing the parent row produced
     * `table tf_update_differences_date has no column named constructor`.
     *
     * @var array<string, array<string, mixed>>
     */
    private const CHILD_ROWS = [
        'tf_update_differences_date' => ['account_id' => self::TENANT, 'position' => 1, 'date' => 1700000000],
        'tf_update_differences_seq' => ['account_id' => self::TENANT, 'position' => 1, 'seq' => 4],
        'tf_update_differences_pts' => ['account_id' => self::TENANT, 'position' => 1, 'pts' => 8],
        'tf_update_differences_state' => [
            'account_id' => self::TENANT, 'position' => 1, 'pts' => 8, 'qts' => 2,
            'date' => 1700000000, 'seq' => 4, 'unread_count' => 0,
        ],
        'tf_updates_pts_count' => ['account_id' => self::TENANT, 'seq' => 1, 'position' => 2, 'pts_count' => 12],
        'tf_business_chat_links_title' => ['account_id' => self::TENANT, 'link' => 'live-link', 'title' => 't'],
        'tf_business_chat_links_entities' => [
            'account_id' => self::TENANT, 'link' => 'live-link', 'position' => 0,
            'constructor' => 'text', 'offset' => 0, 'length' => 3,
        ],
        'tf_wallpapers_settings' => ['account_id' => self::TENANT, 'id' => 5],
        'tf_saved_reaction_tags_title' => ['account_id' => self::TENANT, 'saved_reaction_tag_id' => 7, 'title' => 'tag'],
    ];

    /**
     * Rows a PARENT needs planted before it can be planted itself, keyed by parent.
     *
     * `@var array<string, list<array{0:string, 1:array<string,mixed>}>>`
     */
    private const ANCESTOR_ROWS = [
        'tf_updates_pts' => [
            ['tf_updates', ['account_id' => self::TENANT, 'seq' => 1, 'position' => 2, 'constructor' => 'updates']],
        ],
    ];

    /**
     * The PLANTED parent coordinates, keyed by parent.
     *
     * These are the LEG columns of that parent's relation, and the orphan-ness of
     * a fixture is measured against exactly them.
     *
     * @var array<string, array<string, mixed>>
     */
    private const PLANTED = [
        'tf_update_differences' => ['position' => 1],
        'tf_updates_pts' => ['seq' => 1, 'position' => 2],
        'tf_business_chat_links' => ['link' => 'live-link'],
        'tf_wallpapers' => ['id' => 5],
        'tf_saved_reaction_tags' => ['saved_reaction_tag_id' => 7],
    ];

    /**
     * @return array<string, array{0:string, 1:string, 2:string}>
     */
    public static function witnesses(): array
    {
        return [
            'shape (account_id,position)' => ['tf_update_differences_date', 'tf_update_differences', 'account_id,position'],
            'shape (account_id,seq,position)' => ['tf_updates_pts_count', 'tf_updates_pts', 'account_id,seq,position'],
            'shape (account_id,link)' => ['tf_business_chat_links_title', 'tf_business_chat_links', 'account_id,link'],
            'shape (account_id,saved_reaction_tag_id)' => ['tf_saved_reaction_tags_title', 'tf_saved_reaction_tags', 'account_id,saved_reaction_tag_id'],
            'shape (account_id,id)' => ['tf_wallpapers_settings', 'tf_wallpapers', 'account_id,id'],
        ];
    }

    /** Plant the tenant every fixture needs, so the tenant FK cannot be what refuses. */
    private function plantTenant(Connection $db): void
    {
        $db->table('telegram_accounts')->insert([
            'label' => 'probe',
            'type' => 'user',
            'user_id' => self::TENANT,
        ]);
    }

    /**
     * Plant every row the witness's PARENT needs in order to be planted itself.
     *
     * `$absent` rewrites the ancestors' LEG columns onto the ORPHAN
     * coordinates, which is what makes the orphan-ness attributable.
     *
     * This is not a convenience — it is load-bearing for the
     * `(account_id, seq, position)` shape, and the reason is measured, not
     * suspected. `tf_updates_pts_count` ALREADY carries a shipped foreign key to
     * `tf_updates` over the very same three columns, added by `linkChild()` in
     * `2026_09_14_200030_create_tf_updates_tables.php` — and `linkChild()` chains
     * no `->name()`, so unlike `differenceLink()` it survives (see this
     * migration's docblock). So an orphan row at absent coordinates is refused
     * by that SHIPPED constraint whether or not this migration exists, and a
     * control half run against it proves nothing about this migration.
     *
     * Planting the grandparent at the orphan coordinates while leaving the parent
     * absent isolates the orphan-ness to exactly one missing link — the one this
     * migration adds. The same leg set then resolves against `tf_updates` and
     * fails against `tf_updates_pts`, which is the claim under test.
     *
     * @param  array<string, mixed>  $witness
     */
    private function plantAncestors(Connection $db, array $witness, bool $absent = false): void
    {
        foreach ($witness['ancestors'] as [$table, $row]) {
            $this->assertRowCoversExistingColumns($db, $table, $row);
            $db->table($table)->insert($absent ? $this->orphanise($witness, $row) : $row);
        }
    }

    /**
     * Rewrite a row's LEG columns onto coordinates no planted row carries.
     *
     * The orphan-ness lives ONLY in the parent's key coordinates; `account_id` is
     * never rewritten, so the tenant stays real and the tenant constraint cannot
     * be what refuses the row.
     *
     * Every rewritten column is asserted to differ from the planted value, so an
     * "orphan" that happens to be the row that exists fails here rather than
     * producing a green refusal that proves nothing.
     *
     * @param  array<string, mixed>  $witness
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function orphanise(array $witness, array $row): array
    {
        $rewritten = [];

        foreach ($witness['planted'] as $column => $plantedValue) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $absentValue = $witness['absent'][$column];

            // The check is on the value the row will END UP carrying, not the one
            // it starts with. The template rows hold the PLANTED coordinates —
            // that is what makes one template serve both halves — so comparing
            // before the rewrite would compare a value against itself and fire on
            // every shape.
            self::assertNotSame(
                $absentValue,
                $plantedValue,
                "the orphan's {$column} coordinate ({$absentValue}) equals the planted one ({$plantedValue}), "
                .'so the "orphan" IS the row that exists and its refusal would prove nothing. Fix the '
                .'fixture — ABSENT or PLANTED — not the assertion.',
            );

            $row[$column] = $absentValue;
            $rewritten[] = $column;
        }

        self::assertNotEmpty(
            $rewritten,
            'no leg column was rewritten for '.$witness['child'].' — the fixture has no way to orphan it.',
        );

        return $row;
    }

    /**
     * @param  array<string, mixed>  $witness
     * @return array<string, mixed>
     */
    private function parentRow(array $witness, bool $absent): array
    {
        return $absent
            ? $this->orphanise($witness, $witness['parentRow'])
            : $witness['parentRow'];
    }

    /**
     * @param  array<string, mixed>  $witness
     * @return array<string, mixed>
     */
    private function childRow(array $witness, bool $absent): array
    {
        return $absent
            ? $this->orphanise($witness, $witness['childRow'])
            : $witness['childRow'];
    }

    /**
     * The payload names columns the EXECUTED table actually has.
     *
     * @param  array<string, mixed>  $row
     */
    private function assertRowCoversExistingColumns(Connection $db, string $table, array $row): void
    {
        $present = [];
        foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
            $present[] = (string) $column['name'];
        }

        foreach (array_keys($row) as $name) {
            self::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — the fixture is written against a "
                .'schema the shipped dial does not produce.',
            );
        }
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
            'the number of single-parent relations inside this family moved. That is expected when the '
            .'family gains or loses a table — but then this constant AND the docblock in '
            .self::MIGRATION.'_add_update_differences_misc_family_foreign_keys.php are both stale. '
            .'Re-measure with the command in the FAMILY_ROOTS docblock and bump BOTH. Never edit this '
            .'count to reach a green run.',
        );

        $shapes = [];
        foreach ($discovered as $relation) {
            $shapes[implode(',', $relation['columns'])] = ($shapes[implode(',', $relation['columns'])] ?? 0) + 1;
        }
        ksort($shapes);

        self::assertSame(
            self::MEASURED_SHAPES,
            $shapes,
            'the per-shape breakdown moved. "Five different join shapes" is the plan\'s word and the whole '
            .'argument that this family needs ONE helper rather than five rests on the heterogeneity being '
            .'exactly this. If a shape appeared or vanished, re-make that argument and update both the '
            .'migration and this constant.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused).',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the DECLARATION, on every relation the migration claims
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
            'these family relations declare NO foreign key to their parent, so a child row naming a parent '
            .'that does not exist is admitted. Delete '.self::MIGRATION
            .'_add_update_differences_misc_family_foreign_keys.php and this is the failure you get — which '
            .'is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A fact re-delivered under a shorter field vector '
            .'must take its old children with it, or the dropped ones are orphaned under a re-placed '
            .'parent; a child that outlives its parent fact is the state this constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the parent-key proof: legs ARE the parent's own key
    // ------------------------------------------------------------------

    /**
     * Every derived leg set is EXACTLY its parent's PRIMARY key, read at runtime.
     *
     * This is the proof the plan's family-10 warning demands, and it is the
     * evidence that one helper is not coercing five shapes into one.
     *
     * A FOREIGN KEY must reference a UNIQUE index on the parent; MySQL 8.4
     * refuses anything else with error 6125. Taking the legs from the CHILD's
     * primary key instead — which the Task 4.1 RED step did — writes constraints
     * over a non-unique column set. So the leg set is checked against the
     * parent's real key rather than assumed to be right:
     *
     *  - `legEqualsParentKey` — the legs are the parent's key, not merely a
     *    subset of it, so each of the five shapes is genuinely that parent's own
     *    key and no shape has been stretched to fit another;
     *  - `parentKeyIsPrimary` — the key it references is the PRIMARY index
     *    itself, which is what makes it unique and therefore referenceable.
     *
     * A subset-but-not-equal result would be a shape silently losing a leg.
     */
    public function test_every_derived_leg_is_the_parent_s_own_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $legMismatch = [];
        $notPrimary = [];
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            // The parent's REAL key, from the driver's own INDEX listing.
            $parentKey = [];
            foreach ($db->getSchemaBuilder()->getIndexes($relation['parent']) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $parentKey = array_map('strval', $index['columns']);
                    break;
                }
            }

            if ($parentKey === []) {
                $notPrimary[] = "{$relation['parent']} has no PRIMARY index at all";

                continue;
            }

            $checked++;

            if ($relation['columns'] !== $parentKey) {
                $legMismatch[] = sprintf(
                    '%s(%s) → %s, whose PRIMARY key is (%s)',
                    $relation['child'],
                    implode(',', $relation['columns']),
                    $relation['parent'],
                    implode(',', $parentKey),
                );
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no parent key was read, so this proof is vacuous — discovery found no constrained relation.',
        );

        self::assertSame(
            [],
            $notPrimary,
            'a family parent has no PRIMARY index, so nothing on it can be referenced by a FOREIGN KEY at '
            .'all. MySQL refuses with error 6125 and sqlite never notices.',
        );

        self::assertSame(
            [],
            $legMismatch,
            'a derived leg set is not its parent\'s own PRIMARY key. A foreign key must reference a UNIQUE '
            .'index: legs taken from the child\'s key instead write a constraint over a non-unique column '
            .'set, which MySQL 8.4 refuses with error 6125 and sqlite reports as green forever. A leg set '
            .'that is a strict SUBSET of the parent key is also wrong here — it silently drops a leg, so '
            .'the constraint would not identify one parent row.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — every constraint NAME fits the engine's identifier budget
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
                "the constraint name for {$relation['child']} is ".strlen($name).' characters, over MySQL\'s '
                ."{$limit}-character identifier limit, which it refuses with ERROR 1059. Shorten "
                .'CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the test and the '
                .'migration must agree on the same spelling.',
            );
            if (strlen($relation['child']) > strlen($longest)) {
                $longest = $relation['child'];
            }
        }

        self::assertNotSame('', $longest, 'discovery found no family relations, so nothing above is proven');
    }

    // ------------------------------------------------------------------
    // PROOF 5 — signedness, which sqlite cannot express
    // ------------------------------------------------------------------

    /**
     * Every constrained leg must match its parent's column type and signedness.
     *
     * This is the MySQL 3780 failure (`Referencing column 'x' and referenced
     * column 'y' in foreign key constraint 'f' are incompatible`), the one defect
     * class sqlite reports as green no matter what, because sqlite has no
     * signedness to disagree about. This family is five different integer widths
     * across five parents — `position` is a smallint, `seq` an int, `id` a
     * bigint — so it is exactly where that disagreement would hide.
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
            'a constrained leg disagrees with its parent\'s column type or signedness. MySQL refuses the '
            .'ALTER with error 3780 and sqlite cannot raise it at all, so this is the one defect class a '
            .'sqlite-only suite reports as green forever.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — CONTROL: the orphan is ADMITTED without this migration
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $witness
     */
    #[DataProvider('witnesses')]
    public function test_the_orphan_is_admitted_without_this_migration(
        string $child,
        string $parent,
        string $shape,
    ): void {
        $witness = self::witness($child, $parent, $shape);
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and the
        // refusal below would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is '
            .'unattributable.',
        );

        // An ENFORCING connection, not the shipped permissive default: the point is
        // what the dial WITHOUT this migration permits while enforcement is on. A
        // permissive connection would admit the orphan for the wrong reason.
        $db = $this->enforcingConnection(...$withheld);
        self::assertSame(
            1,
            $this->foreignKeysPragma($db),
            'the control connection is not enforcing, so the orphan landing below proves nothing about '
            .'the constraint being absent — only that nothing was checking.',
        );

        $this->assertRowCoversExistingColumns($db, $child, $witness['childRow']);
        $this->plantTenant($db);

        // At the ORPHAN coordinates, so a constraint this migration does NOT own
        // — `tf_updates_pts_count` already carries one to `tf_updates` over the
        // same three columns — cannot be what refuses the row. Without this the
        // control would be refused by a shipped constraint and would prove nothing
        // about whether this migration's constraint is present.
        $this->plantAncestors($db, $witness, absent: true);
        $db->table($child)->insert($this->childRow($witness, absent: true));

        self::assertSame(
            1,
            $db->table($child)->count(),
            'the orphan must actually LAND without '.self::MIGRATION." on shape ({$shape}). Its tenant is "
            .'real, so if this row were refused the refusal would have come from the tenant constraint, and '
            .'the refusal test would be proving that instead of this migration.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — the SAME orphan is REFUSED once the migration ships
    // ------------------------------------------------------------------

    #[DataProvider('witnesses')]
    public function test_the_orphan_is_refused(string $child, string $parent, string $shape): void
    {
        $witness = self::witness($child, $parent, $shape);

        $db = $this->enforcingConnection(...$this->migrations());

        $this->assertRowCoversExistingColumns($db, $child, $witness['childRow']);
        $this->plantTenant($db);

        // The planted parent exists, so a refusal below is attributable to the
        // orphan row rather than to the parent table being missing entirely.
        $this->plantAncestors($db, $witness);
        $db->table($parent)->insert($this->parentRow($witness, absent: false));

        try {
            $db->table($child)->insert($this->childRow($witness, absent: true));
            self::fail(
                "a {$child} row naming a {$parent} that does not exist, on shape ({$shape}), was ACCEPTED "
                .'on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant IS valid, so the '
                .'tenant constraint cannot be what should have refused it — either this migration\'s '
                .'constraint is absent, or enforcement is not on, and both mean this suite is not a gate.',
            );
        } catch (QueryException $e) {
            self::assertStringContainsString(
                'FOREIGN KEY constraint failed',
                $e->getMessage(),
                'the insert must be refused BY THE FOREIGN KEY. Any other QueryException means the fixture '
                .'is wrong — it is not evidence the constraint bit.',
            );
            self::assertStringContainsString(
                '23000',
                $e->getMessage(),
                'sqlite reports a foreign-key refusal as SQLSTATE 23000; assert it so a different refusal '
                .'class cannot satisfy this test.',
            );
        }

        self::assertSame(0, $db->table($child)->count(), 'a refused insert must leave nothing behind.');
    }

    // ------------------------------------------------------------------
    // PROOF 8 — a real child row LANDS and deleting the parent CASCADES
    // ------------------------------------------------------------------

    #[DataProvider('witnesses')]
    public function test_a_real_child_row_lands_and_deleting_the_parent_cascades(
        string $child,
        string $parent,
        string $shape,
    ): void {
        $witness = self::witness($child, $parent, $shape);

        $db = $this->enforcingConnection(...$this->migrations());

        $this->assertRowCoversExistingColumns($db, $parent, $witness['parentRow']);
        $this->assertRowCoversExistingColumns($db, $child, $witness['childRow']);
        $this->plantTenant($db);
        $this->plantAncestors($db, $witness);
        $db->table($parent)->insert($this->parentRow($witness, absent: false));

        $row = $this->childRow($witness, absent: false);
        $db->table($child)->insert($row);

        $landed = $db->table($child)->first();
        self::assertNotNull(
            $landed,
            "the legitimate row must LAND on shape ({$shape}). A constraint that refuses real engine rows "
            .'is worse than no constraint: it is an outage, not a safety net.',
        );

        // The constrained columns, read back as the values inserted.
        foreach (array_keys($row) as $column) {
            self::assertEquals(
                $row[$column],
                $landed->{$column},
                "the constrained column {$column} must come back as the value inserted; a mismatch would "
                .'mean the constraint is over a different column set than the one this fixture built.',
            );
        }

        $db->table($parent)->where('account_id', self::TENANT)->delete();

        self::assertSame(
            0,
            $db->table($child)->count(),
            "deleting the {$parent} must take its {$child} with it (ON DELETE CASCADE). A child row "
            .'outliving its parent fact is the state this constraint exists to prevent.',
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
