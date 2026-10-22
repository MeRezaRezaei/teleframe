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
 * The `tf_saved_star_gifts` family is constrained to its parent, and the database
 * says so — with the legs proven to be the parent's OWN KEY, not a same-named
 * column.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000840_add_tf_saved_star_gifts_family_foreign_keys.php` adds a
 * FOREIGN KEY from every single-parent relation inside the `tf_saved_star_gifts`
 * family to that parent's key. This file proves the constraints BITE — that a
 * valid child row lands, that an orphan is refused by the constraint and not by
 * anything else, and that the whole thing disappears when the migration does.
 *
 * ## ⭐ WHY THIS FAMILY IS THE ONE WITH A HAZARD, AND WHAT THE HAZARD ACTUALLY IS
 *
 * The plan names it for this family specifically:
 *
 * > verify the `tf_saved_star_gifts_message` and `…_from_id` legs resolve to the
 * > gift's own primary key from the executed schema, not merely a column of the
 * > same name. A same-named column is not the same thing.
 *
 * This file treats that as a claim to MEASURE, not a formality, and the measurement
 * is sharper than the plan states. Introspecting the EXECUTED schema (booting the
 * whole dial, then reading `getColumns()` and `getIndexes()`) shows:
 *
 *   tf_saved_star_gifts_message   columns: account_id, id, text
 *   tf_saved_star_gifts_from_id   columns: account_id, id, from_id_type, from_id_id
 *
 * So for BOTH hazard-named children there is **no column of the same name at
 * all**. `tf_saved_star_gifts_message` has no `message` column, and
 * `tf_saved_star_gifts_from_id` has no `from_id` column — the `from_id` value is
 * decomposed into the pair `from_id_type` + `from_id_id`, which is what the
 * decomposer emits.
 *
 * That makes the hazard sharper, not vacuous: the ONLY column these two tables
 * can join their parent on is the parent's real key leg, `id`. A
 * "same-named column" reading has nowhere to land — and if the derivation had
 * produced one it would have named a column that does not exist, so the DDL would
 * have failed at `ALTER TABLE` rather than silently constraining the wrong thing.
 * `PROVIDES THE PARENT'S OWN KEY` proves the positive half of the same fact: the
 * legs ARE a subset of the parent's actual PRIMARY index, read at runtime.
 *
 * The adjacent shape worth naming, because it is the one that would bite: two
 * siblings in this family DO carry a same-named column that must NOT be the leg —
 * `tf_saved_star_gifts_msg_id.msg_id` and `tf_saved_star_gifts_saved_id.saved_id`
 * are payload values, while both tables join their gift on `id`. The same-named
 * column is present and is correctly NOT used.
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
 * declared constraint is INERT: the DDL is in `sqlite_master`,
 * `Schema::getForeignKeys()` still reports it, and nothing raises. A negative
 * test written on that default is green because no check happened, and would
 * stay green if the migration were deleted outright. Every behavioural
 * connection here goes through `RelationConstraintTestCase::enforcingConnection()`,
 * which turns the pragma on and READS IT BACK before handing it over.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP THIS FIXTURE IS BUILT AROUND
 *
 * Every `tf_*` table in this family already carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005, `2026_10_04_000700`). So an "orphan" child
 * built naively — inventing an `account_id` that no tenant carries — is refused
 * by the TENANT constraint, and every assertion below would be green while this
 * migration contributed nothing.
 *
 * Every fixture below therefore plants a REAL tenant first and makes the
 * orphan-ness live ONLY in the gift coordinates (`id`). The tenant is valid, the
 * parent gift does not exist, so the only constraint that can refuse these rows is
 * the one this migration adds.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * row into the SAME table on the SAME engine with exactly one file withheld from
 * the dial, and requires it to LAND. That is what makes the refusal in
 * `test_the_orphan_is_refused()` attributable to this migration rather than to
 * sqlite, to the pragma, or to the tenant constraint.
 *
 * ## WHY THIS FILE IS NOT THE MYSQL ACCEPTANCE LANE
 *
 * sqlite cannot raise 1452 (orphan refused), 3780 (FK column signedness), 6125
 * (parent key not unique), 1059 (constraint name over 64 chars) or 1170 (TEXT in
 * a PRIMARY KEY) — and 6125 is precisely the error a wrong parent leg triggers on
 * the target engine. `tests/MySql/TfSavedStarGiftsFamilyFkOnMysqlTest.php` is the
 * lane that proves acceptance there; this file proves the constraints bite, are
 * derived from the parent's real key, and vanish when the migration does.
 */
final class TfSavedStarGiftsFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000840';

    /** The family ROOT. Descendants are discovered by name prefix, never listed. */
    private const FAMILY_ROOT = 'tf_saved_star_gifts';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_saved_star_gifts_';

    /**
     * The number of single-parent relations MEASURED inside this family, and the
     * number this migration therefore constrains.
     *
     * MEASURED on 2026-10-05 by running the anti-rot gate's own derivation and
     * filtering to this family's two parents — not by copying a count written in
     * a plan, and not by re-measuring on the built schema:
     *
     *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
     *       --filter test_every_single_parent_relation_has_a_real_constraint \
     *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
     *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
     *       | grep -E "→ tf_saved_star_gifts(_gift)?$" | wc -l
     *     → 15
     *
     * All 15 hang under `tf_saved_star_gifts`. There is no longer a second-level
     * child: `tf_saved_star_gifts_gift_num` was a child TABLE for
     * `savedStarGift#41df43fc … gift_num:flags.19?int`, a single int, so the table
     * asserted an object the wire does not nest and its FK pointed at
     * `tf_saved_star_gifts_gift` while every other relation in this family —
     * and the resolver, and the hand-written model — all said the root. Its value
     * is a COLUMN on `tf_saved_star_gifts` now; see
     * `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`.
     *
     * Both numbers are pinned so discovery cannot silently change length in either
     * direction. If the family gains or loses a table, re-measure and bump BOTH
     * here and in the migration — never edit a count to reach a green run.
     */
    private const MEASURED_RELATIONS = 15;

    private const MEASURED_CONSTRAINED = 15;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /**
     * The witness child, and the ONLY reason it was chosen.
     *
     * `tf_saved_star_gifts_message` has exactly three columns, all NOT NULL, two
     * of which are the parent gift's key and one of which is the payload. So the
     * fixture cannot accidentally satisfy the constraint through some other
     * declared FK, there is nothing else to build, every column the constraint
     * names is visible in the insert, and — per this file's docblock — it is one of
     * the two hazard-named tables, so the gate exercises the dangerous pair.
     */
    private const CHILD = 'tf_saved_star_gifts_message';

    /** The gift the fixture plants, so the legitimate row has a parent. */
    private const GIFT = 4242;

    /** A gift id that is NEVER planted — the orphan. */
    private const ABSENT_GIFT = 918273645;

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
     * A test that hardcodes the spelling it is checking cannot notice the spelling
     * change that broke the rollback, so the suffix and the refusal list are read
     * out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_saved_star_gifts_family_foreign_keys.php';

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
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving table-name prefix whose primary key is a subset of this table's
     * own; the constraint's legs are that parent's key intersected down to the
     * columns this table actually has.
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
            $primaryKey[$table] = $this->primaryKeyColumns($db, $table);
        }

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

        return $relations;
    }

    /**
     * The PRIMARY key columns of `$table`, in key order, read at runtime.
     *
     * THIS is the resolution the family hazard demands: the parent's key is
     * asked of the engine's INDEX catalog rather than inferred from a column name.
     * An empty result means the table has no primary key, which is itself a
     * finding — a leg set could then never be a subset of anything.
     *
     * @return list<string>
     */
    private function primaryKeyColumns(Connection $db, string $table): array
    {
        foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
            if (($index['primary'] ?? false) === true) {
                return array_map('strval', $index['columns']);
            }
        }

        return [];
    }

    /**
     * The migration's REFUSED list, read out of the migration by reflection.
     *
     * A migration that DOES NOT EXIST YET reads as an empty refusal list, and that
     * is the correct reading rather than a lenient one: a migration which is not on
     * disk constrains nothing and refuses nothing, so there is nothing to exclude.
     * Without this guard the proofs below would abort on a `require` of a file that
     * does not exist and would never report WHICH constraints are absent — which is
     * the one thing a RED step has to say. With it, a missing migration produces the
     * honest red: every discovered family relation listed as declaring no foreign key.
     *
     * The guard cannot make a broken migration look green. `REFUSED` is empty in the
     * shipped migration, so both branches yield `[]` for it; and if a refusal were
     * later added while the migration went missing, the run goes REDER (the refused
     * relation is reported as unconstrained), never quieter.
     *
     * @return list<string>
     */
    private function refused(): array
    {
        $file = dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_saved_star_gifts_family_foreign_keys.php';

        if (! is_file($file)) {
            return [];
        }

        $refused = $this->migrationConstant('REFUSED');

        Assert::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            Assert::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            Assert::assertNotSame('', trim($reason), "REFUSED[{$child}] has an empty reason");
        }

        return array_keys($refused);
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

    /**
     * A gift row, exactly as the executed `tf_saved_star_gifts` requires it.
     *
     * MEASURED on the built schema: `account_id`, `id` and `date` are the NOT NULL
     * columns with no default; the seven flags all default to 0 and are left out.
     */
    private function plantGift(Connection $db, int $giftId): void
    {
        $db->table(self::FAMILY_ROOT)->insert([
            'account_id' => self::TENANT,
            'id' => $giftId,
            'date' => 1700000000,
        ]);
    }

    /**
     * A `tf_saved_star_gifts_message` row whose TENANT is real and whose parent
     * gift does not exist.
     *
     * Only `$giftId` varies between the orphan and the legitimate row, so the two
     * inserts are the SAME statement with one value changed — which is what makes
     * the control half a control.
     */
    private function childRow(int $giftId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $giftId,
            'text' => 'probe',
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
            'the number of single-parent relations inside the tf_saved_star_gifts family moved. That is '
            .'expected when the family gains or loses a table — but then this constant AND the docblock '
            .'in '.self::MIGRATION.'_add_tf_saved_star_gifts_family_foreign_keys.php are both stale. '
            .'Re-measure with the command in this class\'s docblock and bump BOTH. Never edit this count '
            .'to reach a green run: it is the only thing standing between a deleted mirror table and a '
            .'silent green.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused). If only '
            .'one of those two numbers moved, the constant and the REFUSED list have drifted apart.',
        );
    }

    // ------------------------------------------------------------------
    // ⭐ PROOF 2 — THE FAMILY HAZARD: the legs are the parent's OWN KEY
    // ------------------------------------------------------------------

    /**
     * Every leg set is a subset of its parent's ACTUAL PRIMARY index, read at
     * runtime — and the two hazard-named children can only join on that key.
     *
     * This is the proof the plan asks for, and it is the positive half of a two-part
     * claim. The negative half lives in
     * `test_the_hazard_named_children_have_no_same_named_column_to_join_on()`.
     *
     * Why a subset of the PRIMARY index specifically, rather than "a column with a
     * matching name": a FOREIGN KEY must reference a UNIQUE index on the parent.
     * Naming a column that merely exists produces either a DDL failure (MySQL 6125,
     * parent key not unique) or — worse — a constraint that binds the wrong pair of
     * values. Reading the key from the engine's index catalog and asserting the
     * legs sit inside it is what makes 6125 structurally impossible rather than
     * incidentally absent.
     */
    public function test_every_constrained_leg_is_a_subset_of_its_parents_actual_primary_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $refused = $this->refused();
        $problems = [];
        $checked = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if (in_array($relation['child'], $refused, true)) {
                continue;
            }

            // The parent's key, asked of the ENGINE for this run.
            $parentKey = $this->primaryKeyColumns($db, $relation['parent']);

            if ($parentKey === []) {
                $problems[] = "{$relation['parent']} has no PRIMARY key at runtime, so no leg set could be a "
                    .'subset of it — the constraint would fail on the target engine with MySQL 6125.';

                continue;
            }

            foreach ($relation['columns'] as $leg) {
                $checked++;

                if (! in_array($leg, $parentKey, true)) {
                    $problems[] = sprintf(
                        '%s.%s is a leg of %s → %s but is NOT a column of that parent\'s primary key [%s]. '
                        .'A constraint over it is not a reference to the parent\'s key; it is a reference to '
                        .'something else, or to nothing.',
                        $relation['child'],
                        $leg,
                        $relation['child'],
                        $relation['parent'],
                        implode(',', $parentKey),
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
            $problems,
            "these legs are not the parent's own key. This is the defect the plan warns about for this "
            .'family: a same-named column is not the same thing, and a constraint written over one binds '
            .'the wrong values or fails outright with MySQL 6125.',
        );
    }

    /**
     * The hazard named explicitly: `…_message` and `…_from_id` have NO column of
     * the same name, so `id` is not merely the better choice — it is the only one
     * the executed schema permits.
     *
     * MEASURED on the built schema by this file's own assertion, over the live
     * `getColumns()` of both tables:
     *
     *   tf_saved_star_gifts_message  ->  account_id, id, text
     *   tf_saved_star_gifts_from_id  ->  account_id, id, from_id_type, from_id_id
     *
     * The `from_id` value is stored DECOMPOSED into `from_id_type` + `from_id_id`
     * by the decomposer; there is no `from_id` column to join on. So had the
     * derivation produced a same-named leg, the `ALTER TABLE` would have named a
     * column that does not exist. The test asserts the absence rather than the
     * presence, because the absence is the interesting fact and it is what makes
     * this family's derivation safe by construction.
     */
    public function test_the_hazard_named_children_have_no_same_named_column_to_join_on(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $hazard = [
            'tf_saved_star_gifts_message' => 'message',
            'tf_saved_star_gifts_from_id' => 'from_id',
        ];

        foreach ($hazard as $child => $sameNamed) {
            $columns = [];
            foreach ($db->getSchemaBuilder()->getColumns($child) as $column) {
                $columns[] = (string) $column['name'];
            }

            self::assertNotEmpty($columns, "{$child} does not exist on the built schema, so the hazard "
                .'proof for it is vacuous — re-read the discovery before trusting this.');

            self::assertNotContains(
                $sameNamed,
                $columns,
                "{$child} now HAS a column named '{$sameNamed}'. That is a schema change, and it re-opens "
                .'exactly the hazard this family was dispatched for: a same-named column is not the '
                .'parent\'s key, and a constraint over it would bind the wrong values. Re-measure which '
                .'column is the parent\'s key leg before trusting any constraint on this table.',
            );
        }

        // And the leg these two tables actually use IS the parent's key leg, read
        // at runtime rather than transcribed.
        $parentKey = $this->primaryKeyColumns($db, self::FAMILY_ROOT);

        self::assertSame(
            ['account_id', 'id'],
            $parentKey,
            'tf_saved_star_gifts\'s primary key is not (account_id, id) as measured. Every leg in this '
            .'family is derived from it, so if it moved, this whole gate is asserting against a schema '
            .'that no longer exists.',
        );
    }

    /**
     * Two siblings in this family DO carry a same-named payload column, and the
     * derivation correctly does NOT use it as the leg.
     *
     * This is the adjacent hazard, and it is asserted rather than left implied:
     * `tf_saved_star_gifts_msg_id.msg_id` and `tf_saved_star_gifts_saved_id.saved_id`
     * exist as payload values on tables whose gift leg is `id`. A derivation that
     * joined on the name would refuse real engine data (a message id that is not a
     * gift id), so this asserts the leg is the parent's key and not the tempting
     * same-named neighbour.
     */
    public function test_a_same_named_payload_column_is_never_used_as_the_leg(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $pairs = [
            'tf_saved_star_gifts_msg_id' => 'msg_id',
            'tf_saved_star_gifts_saved_id' => 'saved_id',
        ];

        $relations = [];
        foreach ($this->familyRelations($db) as $relation) {
            $relations[$relation['child']] = $relation;
        }

        foreach ($pairs as $child => $sameNamed) {
            self::assertArrayHasKey(
                $child,
                $relations,
                "{$child} is no longer a discovered family relation, so this proof is vacuous.",
            );

            $columns = [];
            foreach ($db->getSchemaBuilder()->getColumns($child) as $column) {
                $columns[] = (string) $column['name'];
            }

            self::assertContains(
                $sameNamed,
                $columns,
                "{$child} no longer has the '{$sameNamed}' column this proof is about. If it was dropped, "
                .'this test is vacuous rather than passing — re-read the discovery.',
            );

            self::assertNotContains(
                $sameNamed,
                $relations[$child]['columns'],
                "{$child} joins its parent on '{$sameNamed}', which is that table's own payload value and "
                .'NOT the parent\'s key. A gift constrained on its own message id would refuse legitimate '
                .'Telegram data, where a gift\'s msg_id is a message coordinate and not a gift id.',
            );

            self::assertSame(
                ['account_id', 'id'],
                $relations[$child]['columns'],
                "{$child} must join its gift on the parent's own key (account_id, id).",
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_saved_star_gifts_parent_foreign_key(): void
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
                // The constraint this migration owns is the one whose legs are the
                // DERIVED legs, which are the parent's key.
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
            'these family relations declare NO foreign key to their gift parent, so a saved-gift child row '
            .'naming a gift that does not exist is admitted. Delete '
            .self::MIGRATION.'_add_tf_saved_star_gifts_family_foreign_keys.php and this is the failure you '
            .'get — which is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A gift fact re-delivered with a shorter field '
            .'vector must take its old children with it, or the dropped ones are orphaned under a re-placed '
            .'parent; a child that outlives its parent fact is the state this whole family constraint exists '
            .'to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — CONTROL: the orphan is ADMITTED without this migration
    // ------------------------------------------------------------------

    public function test_the_orphan_is_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and PROOF
        // 5 would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is '
            .'unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $this->plantTenant($permissive, self::TENANT);
        $permissive->table(self::CHILD)->insert($this->childRow(self::ABSENT_GIFT));

        self::assertSame(
            1,
            $permissive->table(self::CHILD)->count(),
            'the orphan must actually LAND without '.self::MIGRATION.'. Its tenant is real, so if this row '
            .'were refused the refusal would have come from the tenant constraint, and PROOF 5 would be '
            .'proving that instead of this migration.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the SAME orphan is REFUSED once the migration ships
    // ------------------------------------------------------------------

    public function test_the_orphan_is_refused(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);

        try {
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_GIFT));
            self::fail(
                'a '.self::CHILD.' row naming gift '.self::ABSENT_GIFT.', which does not exist, was ACCEPTED '
                .'on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant IS valid, so the '
                .'tenant constraint cannot be what should have refused it — either this migration\'s '
                .'constraint is absent, or enforcement is not on, and both mean this suite is not a gate.',
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
                'sqlite reports a foreign-key refusal as SQLSTATE 23000; assert it so a different refusal '
                .'class cannot satisfy this test.',
            );
        }

        self::assertSame(0, $db->table(self::CHILD)->count(), 'a refused insert must leave nothing behind.');
    }

    // ------------------------------------------------------------------
    // PROOF 6 — a real child row LANDS, its constrained columns as inserted,
    //           and deleting the parent CASCADES
    // ------------------------------------------------------------------

    public function test_a_real_child_row_lands_and_deleting_the_parent_cascades(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantGift($db, self::GIFT);

        $row = $this->childRow(self::GIFT);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->first();
        self::assertNotNull($landed, 'the legitimate row must LAND. A constraint that refuses real engine '
            .'rows is worse than no constraint: it is an outage, not a safety net.');

        // The constrained columns, read back as the values inserted.
        foreach (['account_id', 'id'] as $leg) {
            self::assertSame(
                $row[$leg],
                (int) $landed->{$leg},
                "the constrained column {$leg} must come back as the value inserted; a mismatch would mean "
                .'the constraint is over a different column set than the one this fixture built.',
            );
        }

        $db->table(self::FAMILY_ROOT)
            ->where('account_id', self::TENANT)
            ->where('id', self::GIFT)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD)->count(),
            'deleting the parent gift must take its children with it (ON DELETE CASCADE). A child row '
            .'outliving its parent fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite test
     * and fails on the first real ALTER.
     *
     * The longest table in THIS family is `tf_saved_star_gifts_drop_original_details_stars`,
     * MEASURED at 47 characters, so the budget for a suffix is 17.
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
                .'CONSTRAINT_SUFFIX in the migration — do not shorten it here, because the test and the '
                .'migration must agree on the same spelling.',
            );
            if (strlen($relation['child']) > strlen($longest)) {
                $longest = $relation['child'];
            }
        }

        self::assertNotSame('', $longest, 'discovery found no family relations, so nothing above is proven');
        self::assertLessThanOrEqual(
            $limit,
            strlen($longest.$suffix),
            "the longest family table ({$longest}) plus the suffix is over the budget.",
        );
    }

    // ------------------------------------------------------------------
    // PROOF 8 — signedness, which sqlite cannot express
    // ------------------------------------------------------------------

    /**
     * Every constrained leg must match its parent's column SIGNEDNESS and type.
     *
     * This is the MySQL 3780 failure (`Referencing column 'x' and referenced
     * column 'y' in foreign key constraint 'f' are incompatible`), and it is the
     * one class of defect sqlite can report as green no matter what, because
     * sqlite has no signedness to disagree about. Asserting it here — against the
     * executed DDL of BOTH tables — is the only local check available, and it is
     * why the MySQL lane exists.
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

    /**
     * `column_name => "TYPE SIGNEDNESS"`, normalised so the comparison is about
     * the thing MySQL actually refuses on.
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
