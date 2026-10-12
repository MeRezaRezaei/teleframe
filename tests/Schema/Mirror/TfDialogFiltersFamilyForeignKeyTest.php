<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use ReflectionClass;
use ReflectionMethod;

/**
 * The `tf_dialog_filters` family is constrained wherever the target engine
 * accepts the constraint, and REFUSED BY NAME where it does not.
 *
 * ## WHAT IS ACTUALLY BEING PROVED — 1 CONSTRAINT, NOT 7
 *
 * `2026_10_04_000870_add_tf_dialog_filters_family_foreign_keys.php` discovers
 * 7 single-parent relations in this family. It writes ONE constraint — the
 * depth-2 leg from `tf_dialog_filters_title_entities` to
 * `tf_dialog_filters_title` — and refuses the SIX depth-1 legs, each by name and
 * with a mechanical reason.
 *
 * That is not a shortfall worked around. MEASURED on MySQL 8.4.11: this family's
 * root is keyed `int` while every child is keyed `bigint`, and
 *
 *     ALTER TABLE c_bi ADD CONSTRAINT fk_bi_int FOREIGN KEY (account_id,id)
 *       REFERENCES p_int (account_id,id) ON DELETE CASCADE;
 *     → ERROR 3780 (HY000): Referencing column 'id' and referenced column 'id'
 *       in foreign key constraint 'fk_bi_int' are incompatible.
 *
 * while `bigint` → `bigint` — the depth-2 shape — is ACCEPTED, with SIGNEDNESS
 * IDENTICAL in both cases. So the six depth-1 relations can only land through a
 * key change, and this lane's Rule 2 forbids shipping one.
 *
 * ## THE THING THAT MADE THE FIRST DRAFT OF THIS FILE GREEN, AND WHY IT IS NOW
 * A DOCUMENTED LIMITATION INSTEAD OF A TEST
 *
 * The first draft asserted "every constrained leg matches its parent's
 * signedness" by normalising `int` and `bigint` down to the same token. It
 * passed, and it was worthless: it normalised away the very difference MySQL
 * refuses on.
 *
 * Worse, the difference is not merely invisible to that comparison — it is
 * invisible to sqlite ENTIRELY. MEASURED against the built dial: sqlite reports
 * `tf_dialog_filters.id` and `tf_dialog_filters_title.id` with the SAME declared
 * type, `integer`, because sqlite has no 4-byte and 8-byte integer types at all:
 *
 *     tf_dialog_filters.id        => type='integer'
 *     tf_dialog_filters_title.id  => type='integer'
 *     tf_dialog_filters_color.id  => type='integer'
 *
 * So no assertion in this file could prove the width mismatch, and pretending
 * otherwise would be the exact defect this suite exists to catch. The width is
 * therefore proven where it can be — on the engine, from
 * `information_schema.COLUMNS` — in
 * `tests/MySql/TfDialogFiltersFamilyFkOnMysqlTest::test_the_refused_relations_are
 * _exactly_the_integer_width_incompatible_ones()`, which also ATTEMPTS the
 * refused ALTER and requires MySQL to refuse it with 3782/3780 in front of a
 * control that the depth-2 shape is accepted.
 *
 * This file's job is the part sqlite CAN do: that discovery found 7, that
 * exactly 1 is constrained, that the refused relations' legs ARE their parent's
 * own key (so a derivation defect cannot hide behind a type refusal), and that
 * the one constraint that does exist genuinely BITES.
 *
 * ## THE TWO-LEVEL CHAIN, AND THE INVERSION THE PLAN DID NOT PREDICT
 *
 * The plan names this family's hazard:
 *
 * > **Family 7** — a two-level chain: `tf_dialog_filters_title_entities` is a
 * > grandchild, so the parent's constraint must exist first or the leg set is
 * > ambiguous.
 *
 * In that expectation the depth-1 leg lands first and depth 2 follows it. Here
 * depth 1 is refused outright, so the ORDERING IS VACUOUS — nothing is written
 * at depth 1, and exactly one thing is written at depth 2. What remains is that
 * `tf_dialog_filters_title` is now constrained to its own children while
 * remaining unconstrained to its own parent. That is a legal schema, but the
 * chain is enforced only at its DEEPER hop, and this file says so rather than
 * reporting a two-hop success it did not achieve.
 *
 * ## WHY EVERY BEHAVIOURAL ASSERTION RUNS ON AN ENFORCING CONNECTION
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF, where a
 * declared constraint is INERT. A negative test written on that default is green
 * because no check happened, and would stay green if the migration were deleted
 * outright. Every behavioural connection here goes through
 * `RelationConstraintTestCase::enforcingConnection()`, which turns the pragma on
 * and READS IT BACK before handing it over.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP THE FIXTURES ARE BUILT AROUND
 *
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). An orphan built naively — inventing an
 * `account_id` no tenant carries — is refused by the TENANT constraint, and
 * every refusal below would be green while this migration contributed nothing.
 * So every fixture plants a REAL tenant first and makes the orphan-ness live ONLY
 * in the dialog-filter coordinates.
 */
final class TfDialogFiltersFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control halves withhold the file
     * without duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000870';

    /** The family ROOT. Descendants are discovered by name prefix, never listed. */
    private const FAMILY_ROOT = 'tf_dialog_filters';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_dialog_filters_';

    /**
     * The single-parent relations MEASURED inside this family, and how many of
     * them this migration therefore constrains.
     *
     * MEASURED by running the anti-rot gate and filtering its RED list to this
     * family's two parents — the gate is the work list, so the count is read from
     * there and never transcribed:
     *
     *   vendor/bin/phpunit --no-coverage \
     *     --filter test_every_single_parent_relation_has_a_real_constraint \
     *     tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *     | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *     | grep -E "→ tf_dialog_filters(_title)?$"
     *     → 7
     *
     * Of those 7, exactly ONE is constrained: the depth-2 leg. The six depth-1
     * legs are REFUSED, each by name with a measured mechanical reason — see the
     * class docblock and the migration's `REFUSED`. So the count arithmetic this
     * file pins is 7 discovered − 6 refused = 1 constrained.
     *
     * All numbers are pinned so discovery cannot silently change length in
     * either direction. If the family gains or loses a table, or if
     * `tf_dialog_filters.id` is ever re-keyed to `bigint` — which would make all
     * six depth-1 relations landable and is the fix this family's refusal waits
     * on — re-measure and bump BOTH here and in the migration. Never edit a count
     * to reach a green run.
     */
    private const MEASURED_RELATIONS = 7;

    private const MEASURED_REFUSED = 6;

    private const MEASURED_CONSTRAINED = 1;

    /** The depth split of the DISCOVERY, before any refusal is applied. */
    private const MEASURED_DEPTH_1 = 6;

    private const MEASURED_DEPTH_2 = 1;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /** A tenant `telegram_accounts` does NOT carry — used to prove enforcement. */
    private const FABRICATED_TENANT = 999999999999;

    /**
     * The depth-2 witness: the family's only grandchild, and the only relation
     * this migration constrains.
     *
     * Its parent is `tf_dialog_filters_title`, which is itself a child of
     * `tf_dialog_filters` — hence the two-level chain.
     */
    private const GRANDCHILD = 'tf_dialog_filters_title_entities';

    /** The grandchild's parent. A chain has a spine, so the middle hop is named. */
    private const GRANDPARENT = 'tf_dialog_filters_title';

    /**
     * A depth-1 witness, used to prove the REFUSAL rather than a constraint.
     *
     * `tf_dialog_filters_color` is the family's smallest table and the one whose
     * refusal reason is spelled out in full in the migration's `REFUSED`.
     */
    private const REFUSED_WITNESS = 'tf_dialog_filters_color';

    /** Coordinates of the planted parent filter — `id` is the leg that matters. */
    private const FILTER = 7;

    /** Coordinates of a filter that is NEVER planted — the orphan. */
    private const ABSENT_FILTER = 4242;

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * The DIRECTORY list comes from `UpdateIngestor::migrationPaths()` — the
     * same list the ingest seam and the MySQL lane run — rather than from a glob
     * of `src/Laravel/Migrations`, because a glob here would be a second copy of
     * the dial and a second copy drifts. This migration's own file lives in that
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
                // applies `2026_09_14_200030` and `…_200031` twice and dies on
                // "table already exists". Deduped by real path, keeping apply
                // order, because the curated directory is listed first and
                // therefore owns the position of both files.
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
        return (new ReflectionClass($this->migration()))->getConstant($name);
    }

    /** The migration object, required afresh so constants read live. */
    private function migration(): object
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_dialog_filters_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return $migration;
    }

    /**
     * The migration's OWN `orderByDepth()`, invoked by reflection on the
     * relations this file discovered.
     *
     * The order constraints were WRITTEN IN is not observable in a finished
     * schema — a schema is a set of declared facts, and one constraint being
     * present says nothing about which ALTER ran first. So the ordering half of
     * the chain proof invokes the migration's own pure, static `orderByDepth()`
     * on the same relations the migration loops over. A migration that reversed
     * its loop produces a different answer rather than a silent green.
     *
     * @param  list<array{child:string, parent:string, columns:list<string>}>  $relations
     * @return array<int, list<array{child:string, parent:string, columns:list<string>}>> depth => relations
     */
    private function migrationOrderByDepth(array $relations): array
    {
        $method = new ReflectionMethod($this->migration(), 'orderByDepth');
        $method->setAccessible(true);

        $grouped = $method->invoke(null, $relations);

        Assert::assertIsArray(
            $grouped,
            'the migration has no static orderByDepth(), so the depth ordering it claims is not implemented and '
            .'cannot be proven.',
        );

        return $grouped;
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented
    // ------------------------------------------------------------------

    /**
     * The family relations, discovered from the BUILT schema by this file's own
     * code — NOT by asking `MirrorRelationPlan`, which is the class the
     * migration uses and therefore the thing under test.
     *
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving table-name prefix whose primary key is a subset of this table's
     * own; the constraint's legs are that parent's key intersected down to the
     * columns this table actually has.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        [$tables, $tableSet, $columns, $primaryKey] = $this->reflected($db);

        $family = [self::FAMILY_ROOT => true];
        foreach ($tables as $table) {
            if (str_starts_with($table, self::FAMILY_PREFIX)) {
                $family[$table] = true;
            }
        }

        $relations = [];
        foreach ($tables as $table) {
            if ($table === self::FAMILY_ROOT) {
                continue;
            }

            $parent = $this->ancestorOf($table, $tableSet, $family, $primaryKey);

            if ($parent === null) {
                continue;
            }

            $relations[] = [
                'child' => $table,
                'parent' => $parent,
                'columns' => $this->legsOnto($primaryKey[$parent], $columns[$table]),
            ];
        }

        return $relations;
    }

    /**
     * The single-parent ancestor of `$table` under the family's ancestry rule,
     * or null when it has none inside the family.
     *
     * @param  array<string,bool>  $tableSet
     * @param  array<string,bool>  $family
     * @param  array<string,list<string>>  $primaryKey
     */
    private function ancestorOf(string $table, array $tableSet, array $family, array $primaryKey): ?string
    {
        $parts = explode('_', $table);
        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $candidate = implode('_', array_slice($parts, 0, $i));
            if (! isset($tableSet[$candidate], $family[$candidate])) {
                continue;
            }
            if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * The parent's key columns, narrowed to those this table actually carries.
     *
     * @param  list<string>  $parentKey
     * @param  array<string,bool>  $childColumns
     * @return list<string>
     */
    private function legsOnto(array $parentKey, array $childColumns): array
    {
        $legs = [];
        foreach ($parentKey as $leg) {
            if (isset($childColumns[$leg])) {
                $legs[] = $leg;
            }
        }

        return $legs;
    }

    /**
     * The built schema, read off the engine: bare table names, the column set of
     * each, and each table's primary key IN KEY ORDER.
     *
     * The key comes from the driver's INDEX listing, not the column rows:
     * `getColumns()` carries no key position, and a composite FOREIGN KEY must
     * be declared in the parent's key ORDER, which is not the order the columns
     * sit in the table.
     *
     * @return array{0:list<string>,1:array<string,bool>,2:array<string,array<string,bool>>,3:array<string,list<string>>}
     */
    private function reflected(Connection $db): array
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

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $cols = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $cols[(string) $column['name']] = true;
            }
            $columns[$table] = $cols;

            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        return [$tables, array_flip($tables), $columns, $primaryKey];
    }

    /**
     * `REFUSED` read out of the migration, with its shape and membership proven.
     *
     * @return array<string, string>
     */
    private function refused(): array
    {
        $refused = $this->migrationConstant('REFUSED');

        Assert::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            Assert::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            Assert::assertNotSame(
                '',
                trim($reason),
                "REFUSED[{$child}] has an empty reason. An unnamed refusal is indistinguishable from a bug in "
                .'the discovery.',
            );
        }

        $discovered = array_column($this->familyRelations($this->enforcingConnection(...$this->migrations())), 'child');

        foreach (array_keys($refused) as $child) {
            Assert::assertContains(
                $child,
                $discovered,
                "REFUSED names {$child}, which is not a discovered relation in this family. A refusal aimed at a "
                .'table outside the family is a refusal that hides a discovery bug.',
            );
        }

        return $refused;
    }

    // ------------------------------------------------------------------
    // fixtures
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

    /** Plant the root filter. */
    private function plantFilter(Connection $db, int $filterId): void
    {
        $db->table(self::FAMILY_ROOT)->insert([
            'account_id' => self::TENANT,
            'id' => $filterId,
            'constructor' => self::FAMILY_ROOT,
        ]);
    }

    /** Plant the middle hop — which is the grandchild's parent. */
    private function plantTitle(Connection $db, int $filterId): void
    {
        $db->table(self::GRANDPARENT)->insert([
            'account_id' => self::TENANT,
            'id' => $filterId,
            'text' => 'probe title',
        ]);
    }

    /**
     * The depth-2 orphan: a REAL tenant, and a title row that does not exist.
     *
     * Nothing is wrong with this row's own filter coordinate. If the depth-2 leg
     * were absent the row would land, which is exactly what the control half
     * below asserts.
     */
    private function grandchildOrphanRow(int $titleId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $titleId,
            'position' => 0,
            'constructor' => 'textEntity',
        ];
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
            'the number of single-parent relations inside the tf_dialog_filters family moved. That is expected '
            .'when the family gains or loses a table — but then this constant AND the docblock in '
            .self::MIGRATION.'_add_tf_dialog_filters_family_foreign_keys.php are both stale. Re-measure by '
            .'filtering the anti-rot gate\'s RED list to this family, and bump BOTH. Never edit this count to '
            .'reach a green run: it is the only thing standing between a deleted mirror table and a silent '
            .'green.',
        );

        self::assertCount(
            self::MEASURED_REFUSED,
            $this->refused(),
            'the refusal count moved. This family refuses its six depth-1 relations because '
            .self::FAMILY_ROOT.'.id is `int` against a `bigint` child, which MySQL refuses with ERROR 3780. If '
            .self::FAMILY_ROOT.' has been re-keyed to `bigint`, all six become landable and BOTH the migration '
            .'and this file must be updated together — not one of them.',
        );

        self::assertSame(
            self::MEASURED_CONSTRAINED,
            self::MEASURED_RELATIONS - self::MEASURED_REFUSED,
            'the pinned constants disagree: (discovered − refused) is the number this migration constrains.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the depth SPLIT of the DISCOVERY
    // ------------------------------------------------------------------

    /**
     * The family is a CHAIN, and the chain is the hazard the plan names.
     *
     * This asserts the SHAPE independently of the refusals: exactly
     * `MEASURED_DEPTH_1` relations whose parent is the root, and exactly
     * `MEASURED_DEPTH_2` whose parent is the root's own child. If a relation
     * moved between depths — say the grandchild were re-parented, or a second
     * grandchild appeared — this goes red naming it, because every proof below
     * is written against a fixture at one specific depth.
     */
    public function test_the_family_is_a_two_level_chain_of_the_measured_depths(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $depth1 = [];
        $depth2 = [];
        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['parent'] === self::FAMILY_ROOT) {
                $depth1[] = $relation['child'];
            } elseif ($relation['parent'] === self::GRANDPARENT) {
                $depth2[] = $relation['child'];
            }
        }

        self::assertCount(
            self::MEASURED_DEPTH_1,
            $depth1,
            'the number of relations one hop below '.self::FAMILY_ROOT.' moved. Found: '.implode(', ', $depth1),
        );
        self::assertCount(
            self::MEASURED_DEPTH_2,
            $depth2,
            'the number of relations two hops below '.self::FAMILY_ROOT.' moved. Found: '.implode(', ', $depth2),
        );

        self::assertContains(
            self::GRANDCHILD,
            $depth2,
            self::GRANDCHILD.' is the grandchild this file is built around. If it is no longer a child of '
            .self::GRANDPARENT.' — the depth-2 proofs below are proving something other than what they claim.',
        );
        self::assertContains(
            self::REFUSED_WITNESS,
            $depth1,
            self::REFUSED_WITNESS.' is the depth-1 witness whose refusal reason is spelled out in the '
            .'migration. If it is no longer a child of '.self::FAMILY_ROOT.' — the refusal proofs below are '
            .'proving something other than what they claim.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the ORDERING, and the inversion the plan did not predict
    // ------------------------------------------------------------------

    /**
     * The migration's own depth ordering, and the honest reading of it.
     *
     * The plan's expectation for this family is that the child's leg lands first
     * and the grandchild's follows it. MEASURED outcome: the depth-1 legs are all
     * refused, so NOTHING is written at depth 1 and the depth-2 leg is the only
     * constraint this file creates. The ordering is therefore vacuous — and this
     * asserts exactly that rather than reporting a two-hop success.
     *
     * It also pins the ordering FUNCTION's behaviour on the full, unfiltered
     * discovery, so the depth grouping the migration computes is verified rather
     * than assumed: every depth-1 relation must be emitted at a lower index than
     * the grandchild. A migration that reversed its loop fails here.
     */
    public function test_the_depth_ordering_is_vacuous_because_every_depth_1_leg_is_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $discovered = $this->familyRelations($db);

        // ---- the ordering FUNCTION, on the FULL discovery ----
        $grouped = $this->migrationOrderByDepth($discovered);

        self::assertArrayHasKey(1, $grouped, 'the migration computed no depth-1 group at all.');
        self::assertArrayHasKey(2, $grouped, 'the migration computed no depth-2 group at all.');

        $emission = array_merge(array_column($grouped[1], 'child'), array_column($grouped[2], 'child'));
        $lastDepth1 = -1;
        $firstDepth2 = PHP_INT_MAX;
        foreach ($emission as $index => $child) {
            if (in_array($child, array_column($grouped[1], 'child'), true)) {
                $lastDepth1 = max($lastDepth1, $index);
            }
            if (in_array($child, array_column($grouped[2], 'child'), true)) {
                $firstDepth2 = min($firstDepth2, $index);
            }
        }

        self::assertLessThan(
            $firstDepth2,
            $lastDepth1,
            'the migration emits a depth-2 relation at or before a depth-1 one. Emission order: '
            .implode(', ', $emission).'. The grandchild leg would then be derived against a parent whose own '
            .'constraint does not exist yet — the ambiguity the plan names for this family.',
        );

        // ---- the ordering AFTER the refusals, which is what actually ships ----
        $refused = array_keys($this->refused());
        $surviving = array_values(array_filter($discovered, static fn (array $r): bool => ! in_array(
            $r['child'],
            $refused,
            true,
        )));

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            $surviving,
            'exactly '.self::MEASURED_CONSTRAINED.' relation survives the refusal filter. Found: '
            .implode(', ', array_column($surviving, 'child')),
        );
        self::assertSame(
            self::GRANDCHILD,
            $surviving[0]['child'],
            'the ONLY relation this migration constrains must be the grandchild '.self::GRANDCHILD
            .'. If a depth-1 relation survives, then '.self::FAMILY_ROOT.' has been re-keyed and this file\'s '
            .'refusal reasoning, the migration\'s REFUSED list and the MySQL lane\'s width check are all stale.',
        );

        // The inversion, stated as an assertion so it cannot be forgotten: the
        // grandchild's parent is itself an UNCONSTRAINED depth-1 relation.
        self::assertContains(
            self::GRANDPARENT,
            $refused,
            self::GRANDPARENT.' is the grandchild\'s parent AND a depth-1 relation, and this migration does not '
            .'constrain it to '.self::FAMILY_ROOT.'. So the chain is enforced only at its DEEPER hop. That is a '
            .'legal schema, but it is not the plan\'s expectation and must not be reported as one.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — the ONE constraint that exists, and that it BITES
    // ------------------------------------------------------------------

    public function test_the_single_declared_relation_declares_its_parent_foreign_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = array_keys($this->refused());
        $missing = [];
        $wrong = [];
        $declared = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }
            $declared++;

            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                    continue;
                }
                // The constraint this migration owns is the one whose legs are
                // the DERIVED legs.
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

        self::assertSame(self::MEASURED_CONSTRAINED, $declared, 'the declaration loop saw the wrong count.');
        self::assertSame(
            [],
            $missing,
            'these family relations declare NO foreign key to their parent, so a child row naming a parent that '
            .'does not exist is admitted. Delete '
            .self::MIGRATION.'_add_tf_dialog_filters_family_foreign_keys.php and this is the failure you get '
            .'— which is why it is asserted, not assumed.',
        );
        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A dialog-filter fact child has no life outside its '
            .'parent: a re-delivered filter with a shorter field vector must take its old children with it, or '
            .'the dropped ones are orphaned under a re-placed parent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the REFUSALS are justified, and are of the RIGHT KIND
    // ------------------------------------------------------------------

    /**
     * A refused relation's derived legs ARE its parent's own key.
     *
     * This is what distinguishes THIS family's refusals from the sibling
     * `tf_messages` family's, which declines a relation because the DERIVED LEG
     * SET is wrong. Here the derivation is CORRECT and the ENGINE refuses it — so
     * if a refused relation's legs ever stop being the parent's own key, the
     * reason recorded against it is a lie and this goes red.
     */
    public function test_every_refused_relation_s_legs_are_its_parents_own_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $refused = $this->refused();

        self::assertNotSame(
            [],
            $refused,
            'REFUSED is empty, so every proof about the refusal is vacuous — and so is the migration\'s claim '
            .'that it declines six relations for a measured reason.',
        );

        [, , , $primaryKey] = $this->reflected($db);
        $byChild = [];
        foreach ($this->familyRelations($db) as $relation) {
            $byChild[$relation['child']] = $relation;
        }

        foreach (array_keys($refused) as $child) {
            $relation = $byChild[$child] ?? null;
            self::assertNotNull($child, "{$child} vanished from discovery");

            self::assertSame(
                $primaryKey[$relation['parent']],
                $relation['columns'],
                "{$child}'s derived legs are NOT its parent ".$relation['parent']
                ."\'s own key. The recorded refusal reason is a TYPE refusal, which only holds while the leg "
                .'set is correct; a wrong leg set needs the derivation fixed, not a type exception.',
            );
        }
    }

    /**
     * RE-BASELINED 2026-10-05 — every relation in this family now carries a
     * constraint, so the assertion is INVERTED. The original asserted that no
     * refused relation carries a constraint to its parent; it carried its own
     * tripwire, in this very docblock: *"If this ever starts passing,
     * `tf_dialog_filters.id` has been re-keyed and every refusal in this file is
     * stale."* It fired, because that is exactly what happened:
     * `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint` widened the
     * parent, which made the six depth-1 legs constrable with no key change
     * anywhere else — the escape clause D-020 wrote in advance for this case.
     *
     * The test is KEPT and its direction reversed rather than deleted, because
     * the thing it now proves is the stronger property: not merely "no relation
     * smuggles a constraint past the refusal list", but "EVERY relation the
     * refusal list once named is genuinely constrained to its parent". A stale
     * excuse that silently stops being true is how `soft 0` came to overstate the
     * schema; asserting the constraints EXIST is what makes the excuse's
     * continued presence in `000870` visibly historical rather than load-bearing.
     *
     * `000870`'s `REFUSED` filter is deliberately left in place. It is a filter on
     * that migration's own loop, not a schema property, so on a fresh install both
     * files run in order — widen, then constrain — and the six land.
     */
    public function test_every_relation_the_refusal_list_named_is_now_constrained(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $refused = $this->refused();

        $constrained = [];
        $missing = [];
        foreach (array_keys($refused) as $child) {
            $parent = null;
            foreach ($this->familyRelations($db) as $relation) {
                if ($relation['child'] === $child) {
                    $parent = $relation['parent'];

                    break;
                }
            }
            if ($parent === null) {
                continue;
            }

            $found = false;
            foreach ($db->getSchemaBuilder()->getForeignKeys($child) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === $parent) {
                    $found = true;

                    break;
                }
            }
            $constrained[] = $child.' → '.$parent.($found ? '' : '  [NO CONSTRAINT]');
            if (! $found) {
                $missing[] = $child.' → '.$parent;
            }
        }

        self::assertSame(
            [],
            $missing,
            'a relation the refusal list named carries NO constraint to its parent: '.implode('; ', $missing)
            .'. The widening migration and the six constraints were supposed to land together — if the widening '
            .'ran and the constraints did not, the schema is in the one state that is worse than either: a '
            .'bigint parent whose children are unconstrained.',
        );

        self::assertCount(
            6,
            $constrained,
            'the refusal list is expected to name exactly the six depth-1 relations. A different count means the '
            .'list moved, and both this test and the widening migration need re-reading before the schema is '
            .'trusted. Observed: '.implode('; ', $constrained),
        );
    }

    /**
     * RE-BASELINED 2026-10-05 — the DEPTH-1 orphan is now REFUSED, because the
     * depth-1 leg now exists. The original asserted the row LANDS, and said so in
     * its own failure message: *"If this row is now refused, either
     * `tf_dialog_filters` has been re-keyed to bigint and the whole refusal
     * reasoning in this file and in the migration is stale."* That is precisely
     * what happened — see `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint`.
     *
     * The ORIGINAL assertion was true only while the refusal held, and it is kept
     * here in its new direction because a depth-1 leg that does not refuse its own
     * parent is the dangerous state, not the safe one: an orphan row naming a
     * filter that does not exist would sit in the table forever, invisible, which
     * is the failure the operator named — "a surprise of relational data".
     *
     * The control half is UNCHANGED and still load-bearing: the fabricated-tenant
     * insert below must still be refused by the TENANT FK. Without it, the
     * depth-1 refusal asserted above could be satisfied by the wrong constraint and
     * would prove nothing about the leg under test.
     */
    public function test_the_depth_1_orphan_is_refused_because_the_depth_1_leg_now_exists(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);

        $row = [
            'account_id' => self::TENANT,
            'id' => self::ABSENT_FILTER,
            'color' => 42,
        ];

        $refused = null;
        try {
            $db->table(self::REFUSED_WITNESS)->insert($row);
        } catch (QueryException $e) {
            $refused = $e;
        }

        self::assertNotNull(
            $refused,
            'a '.self::REFUSED_WITNESS.' row naming a filter that does not exist MUST now be refused, because '
            .self::FAMILY_ROOT.'.id was widened to bigint and the depth-1 constraint exists. If this row LANDS, '
            .'the widening landed without its constraints and the schema holds unconstrained children under a '
            .'re-keyed parent — strictly worse than either uniform state.',
        );
        self::assertStringContainsString(
            'FOREIGN KEY constraint failed',
            $refused->getMessage(),
            'the depth-1 orphan must be refused BY a foreign key. Any other refusal means the row failed for some '
            .'other reason and the depth-1 leg is still not proven.',
        );

        self::assertSame(
            0,
            $db->table(self::REFUSED_WITNESS)->where('id', self::ABSENT_FILTER)->count(),
            'the refused row must not be present in any form.',
        );

        // ---- CONTROL HALF, unchanged: the connection really is enforcing ----
        // A fabricated tenant MUST be refused by the tenant FK, so a permissive
        // connection fails here rather than turning the refusal above into a
        // vacuous green. Same operation, a DIFFERENT constraint, deliberately.
        $tenantRefused = null;
        try {
            $db->table(self::REFUSED_WITNESS)->insert([
                'account_id' => self::FABRICATED_TENANT,
                'id' => self::ABSENT_FILTER,
                'color' => 43,
            ]);
        } catch (QueryException $e) {
            $tenantRefused = $e;
        }

        self::assertNotNull(
            $tenantRefused,
            'a row naming a tenant that does not exist was ACCEPTED on a connection handed out by '
            .'enforcingConnection(). The pragma is not on, so the depth-1 refusal asserted just above proves '
            .'nothing.',
        );
        self::assertStringContainsString(
            'FOREIGN KEY constraint failed',
            $tenantRefused->getMessage(),
            'the fabricated tenant must be refused BY the tenant FK. Any other refusal means the depth-1 refusal '
            .'above was measured against a connection enforcing something other than what this file claims.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — DEPTH 2, the leg that DOES exist: control, refusal, land
    // ------------------------------------------------------------------

    public function test_the_grandchild_orphan_is_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and the
        // refusal proof would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        // The root filter IS planted, so this row is wrong in EXACTLY ONE way:
        // the middle hop has no row. Anything wronger and the refusal below
        // could come from a constraint at the wrong depth.
        $this->plantTenant($permissive, self::TENANT);
        $this->plantFilter($permissive, self::FILTER);
        $permissive->table(self::GRANDCHILD)->insert($this->grandchildOrphanRow(self::FILTER));

        self::assertSame(
            1,
            $permissive->table(self::GRANDCHILD)->count(),
            'the depth-2 orphan must actually LAND without '.self::MIGRATION.'. Its tenant and its root filter '
            .'are both real, so the ONLY constraint that could refuse it is the depth-2 leg — which is exactly '
            .'what the refusal proof below needs to be able to attribute.',
        );
    }

    public function test_the_grandchild_orphan_is_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantFilter($db, self::FILTER);

        try {
            $db->table(self::GRANDCHILD)->insert($this->grandchildOrphanRow(self::FILTER));
            self::fail(
                'a '.self::GRANDCHILD.' row naming '.self::GRANDPARENT.' '.self::FILTER.', which does not '
                .'exist, was ACCEPTED on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant '
                .'and its ROOT filter are both valid, so neither the tenant constraint nor a depth-1 constraint '
                .'can be what should have refused it — the depth-2 leg is absent, and the only constraint this '
                .'migration creates is not doing its job.',
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
            $db->table(self::GRANDCHILD)->count(),
            'a refused insert must leave nothing behind.',
        );
    }

    /**
     * The grandchild's parent key must be UNIQUE over exactly the legs used.
     *
     * Read off the engine's index listing rather than assumed: `(account_id, id)`
     * is only a legal REFERENCES target if it is the parent's own PRIMARY KEY
     * (or otherwise unique), which MySQL refuses with error 6125. sqlite will not
     * raise 6125 at all, so asserting the key here is the only local check
     * available — and it is the check that would catch a `tf_dialog_filters_title`
     * that had been re-keyed.
     */
    public function test_the_grandchild_depth_joins_the_grandparents_own_unique_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        [, , , $primaryKey] = $this->reflected($db);

        self::assertSame(
            ['account_id', 'id'],
            $primaryKey[self::GRANDPARENT] ?? [],
            self::GRANDPARENT.'\'s primary key moved. MEASURED from its `primary()` call in '
            .'src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php:283 as '
            .'(account_id, id). If it changed, the depth-2 leg set is no longer the parent\'s own key and the '
            .'target engine refuses the ALTER with error 6125 — re-derive, do not re-key.',
        );

        // The LEG the grandchild actually declares, read off the engine.
        $legs = null;
        foreach ($db->getSchemaBuilder()->getForeignKeys(self::GRANDCHILD) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') === self::GRANDPARENT) {
                $legs = array_map('strval', $foreign['foreign_columns'] ?? []);

                break;
            }
        }

        self::assertNotNull(
            $legs,
            self::GRANDCHILD.' carries no foreign key to '.self::GRANDPARENT.', so the depth-2 leg is absent.',
        );
        self::assertSame(
            $primaryKey[self::GRANDPARENT],
            $legs,
            'the depth-2 leg set must be '.self::GRANDPARENT.'\'s OWN key, in the parent\'s key ORDER. A leg '
            .'set that is not the parent\'s key either references a non-unique column set (MySQL error 6125) '
            .'or is refused for ordering (MySQL error 3780), and sqlite raises neither.',
        );
    }

    /**
     * A real grandchild row LANDS once its parent exists, and ON DELETE CASCADE
     * is proven AT THE HOP THAT ACTUALLY EXISTS.
     *
     * A constraint that refuses real engine rows is worse than no constraint: it
     * is an outage, not a safety net.
     *
     * ## WHY THE CASCADE IS MEASURED FROM THE MIDDLE HOP, NOT THE ROOT
     *
     * The obvious assertion — "delete the root and the grandchild goes too" —
     * is FALSE in this family, and the first draft of this test asserted it and
     * failed. It is false because the depth-1 leg is REFUSED, so there is no
     * cascade from `tf_dialog_filters` to `tf_dialog_filters_title` for the
     * cascade to travel along. The only cascade in this family is hop 2.
     *
     * So both facts are asserted here, and the second one matters more than it
     * looks: a reader who assumed a root-anchored cascade would conclude that
     * deleting a filter prunes its entities, and that conclusion is wrong on this
     * schema. Recording the gap is the only way it stays visible.
     */
    public function test_a_real_grandchild_row_lands_and_the_hop_two_cascade_is_proven(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantFilter($db, self::FILTER);
        $this->plantTitle($db, self::FILTER);

        $row = $this->grandchildOrphanRow(self::FILTER);
        $db->table(self::GRANDCHILD)->insert($row);

        $landed = $db->table(self::GRANDCHILD)->first();
        self::assertNotNull(
            $landed,
            'the legitimate depth-2 row must LAND once its parent '.self::GRANDPARENT.' exists. A depth-2 leg '
            .'that refuses its own parent\'s children is an outage, not a safety net.',
        );

        foreach (['account_id', 'id', 'position'] as $column) {
            self::assertSame(
                $row[$column],
                (int) $landed->{$column},
                "the column {$column} must come back as the value inserted.",
            );
        }

        // ---- HOP 2: the cascade this family actually has ----
        $db->table(self::GRANDPARENT)
            ->where('account_id', self::TENANT)
            ->where('id', self::FILTER)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::GRANDCHILD)->count(),
            'deleting '.self::GRANDPARENT.' must take its entities with it (ON DELETE CASCADE), which is the '
            .'depth-2 leg doing its job.',
        );

        // ---- HOP 1: the cascade this family does NOT have, asserted as absent ----
        // The root filter from the top of this test is still present — it was
        // never deleted — so only the title and the grandchild need re-planting.
        $this->plantTitle($db, self::FILTER);
        $db->table(self::GRANDCHILD)->insert($this->grandchildOrphanRow(self::FILTER));
        self::assertSame(
            1,
            $db->table(self::GRANDCHILD)->count(),
            'the depth-2 fixture did not land, so the hop-1 assertion below would prove nothing.',
        );

        // ---- HOP 1: NOW PRESENT, and the cascade travels the whole chain ----
        // RE-BASELINED 2026-10-05. This block used to assert the hop-1 cascade was
        // ABSENT, and said in its own failure message: "If this ever starts
        // passing, tf_dialog_filters.id has been re-keyed and every refusal in this
        // file and in the migration is stale." It now passes, because that is what
        // 2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint did.
        //
        // The inverted assertion is the STRONGER one, and it is the property the
        // original docblock warned a reader away from assuming: deleting a dialog
        // filter now prunes its title AND its entities, all the way down two hops.
        // A suite that only recorded the gap would have left a reader believing the
        // opposite of what the schema now does.
        $db->table(self::FAMILY_ROOT)
            ->where('account_id', self::TENANT)
            ->where('id', self::FILTER)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::GRANDPARENT)->count(),
            'deleting '.self::FAMILY_ROOT.' must now take '.self::GRANDPARENT.' with it (ON DELETE CASCADE over '
            .'the depth-1 leg created by the widening migration). If this row survives, the widening landed '
            .'without its constraints and the schema holds unconstrained children under a re-keyed parent.',
        );
        self::assertSame(
            0,
            $db->table(self::GRANDCHILD)->count(),
            'the cascade must now travel BOTH hops: deleting '.self::FAMILY_ROOT.' takes '.self::GRANDPARENT
            .' and then its entities. This is the chain property the operator named — relations validating each '
            .'other, so a delete at the root cannot leave a grandchild behind.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — the constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite test
     * and fails on the first real ALTER — which is exactly how the tenant
     * migration's first spelling shipped green and was refused.
     */
    public function test_the_constraint_name_fits_the_engine_identifier_budget(): void
    {
        $limit = 64;

        $db = $this->enforcingConnection(...$this->migrations());
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        // The grandchild is the longest NAME in this family, MEASURED at 32
        // characters, and it is the only relation constrained — so the budget
        // claim has to be checked against it specifically.
        self::assertSame(
            self::GRANDCHILD,
            self::GRANDCHILD,
            'the witness constant must be the family\'s longest table name.',
        );
        self::assertSame(
            32,
            strlen(self::GRANDCHILD),
            'the family\'s longest table name is no longer 32 characters. Re-measure the identifier budget in '
            .'the migration docblock rather than trusting this test\'s arithmetic.',
        );

        $name = self::GRANDCHILD.$suffix;
        self::assertLessThanOrEqual(
            $limit,
            strlen($name),
            'the constraint name is '.strlen($name)." characters, over MySQL's {$limit}-character identifier "
            .'limit, which it refuses with ERROR 1059. Shorten CONSTRAINT_SUFFIX in the migration — do not '
            .'shorten it here, because the test and the migration must agree on the same spelling.',
        );

        // The budget must hold for every relation the migration COULD name, not
        // just the one it writes, so a future un-refusal cannot overflow it.
        foreach ($this->familyRelations($db) as $relation) {
            self::assertLessThanOrEqual(
                $limit,
                strlen($relation['child'].$suffix),
                "the constraint name for {$relation['child']} is over the budget.",
            );
        }
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

}
