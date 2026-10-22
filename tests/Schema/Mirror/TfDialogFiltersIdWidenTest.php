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
 * `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php` widens
 * `tf_dialog_filters.id` from `int` to `bigint` and lands the SIX depth-1
 * relations its predecessor refused.
 *
 * ## WHAT THIS FILE IS: THE RE-CHECK D-020 ASKED FOR
 *
 * D-020 refused those six and said, in its "what would make this wrong" clause:
 *
 * > If `tf_dialog_filters.id` is ever widened to `bigint` by a future migration,
 * > the 6 refusals become constrable with no key change and **must** be landed
 * > then. The mechanical reason in `REFUSED` is the thing to re-check, not the
 * > count.
 *
 * So the count is not what this file asserts. The MECHANICAL REASON is: that
 * the parent really was the narrow one, that every child really was already the
 * wider one, and that therefore widening moves the parent TOWARD its children
 * and strands nothing.
 *
 * ## THE WIDENING IS A DECLARED DIVERGENCE — RECORDED HERE, NOT REVERTED
 *
 * `bin/audit-fidelity.php --engine=mysql` reports this column as a C3 width
 * divergence:
 *
 *     C3  tf_dialog_filters  column:id
 *         TL `int` — a signed 32-bit value
 *         ours: bigint (64)
 *
 * The authority is `schema/sources/TL_telegram_v227.tl:1367`:
 *
 *     dialogFilter#aa472651 flags:# ... id:int title:TextWithEntities ...
 *
 * `id:int` is 32 bits and the column is 64, so the store can admit values the
 * wire cannot produce. The tool's C3 rule fires on a width mismatch in EITHER
 * direction and its own remedy text ("a 32-bit column is a truncation, not a
 * translation") describes the NARROW direction only, which is why this section
 * exists rather than leaving the next reader to assume the widening was
 * verified faithful.
 *
 * **THE DIVERGENCE IS NOW DECLARED, and the declaration is the authority — not
 * this docblock.** `docs/schema/fidelity-declarations.json` carries a
 * `DELIBERATE` declaration for exactly one subject, `column:id` on exactly one
 * table, and it names THIS FILE as its `cites`. With it in place the MySQL audit
 * reads `COPYING-GAP=0 DELIBERATE=1` (MEASURED 2026-10-10). Two properties of
 * the mechanism matter and are the reason a declaration is not simply a quieter
 * way to hide a finding:
 *
 *  - a declaration may ONLY claim `STORAGE-TRANSLATION` or `DELIBERATE` — the
 *    loader exits `EXIT_USAGE` on any other verdict, so a divergence cannot
 *    declare itself away; and
 *  - every declaration must carry a named `fact` and a `cites` file, or it is
 *    refused.
 *
 * THE FACTS THE DECLARATION RESTS ON, re-measured here independently on MySQL
 * 8.4.11 against the shipped dial:
 *
 *     ALTER TABLE tf_dialog_filters MODIFY id INT NOT NULL;
 *       -> errno 3780, SQLSTATE HY000
 *          Referencing column 'id' and referenced column 'id' in foreign key
 *          constraint 'tf_dialog_filters_color_df_widen_fk' are incompatible.
 *
 * `id` is the SECOND column of this table's composite PRIMARY KEY
 * `(account_id, id)` and is the PARENT leg of the SIX constraints this very file
 * proves are declared. MySQL requires the size and sign of fixed-precision types
 * to match across a foreign key, so the root cannot narrow without all six
 * children narrowing with it — and `SET SESSION foreign_key_checks = 0` does NOT
 * bypass that (measured: the same 3780). Every table in the eight-table family is
 * simultaneously a child and, except the leaf, a parent, so no ordering avoids
 * dropping the seven constraints first. D-020 recorded the same shape as
 * REJECTED in its alternative A: *"Narrow a CHILD's `id` from `bigint` to `int`.
 * Rejected: a primary-key change… Explicitly forbidden by the plan's rule 2."*
 *
 * WHAT THIS FILE THEREFORE DOES, and what it deliberately does NOT do:
 *
 *  - it keeps every assertion it had. The widening happened, the six
 *    constraints are declared, and those are facts about this tree that must
 *    keep being proved;
 *  - it does NOT soften, skip or range any count to accommodate the divergence.
 *    Loosening this file would silence the evidence that the six constraints
 *    exist, which is the opposite of what a declared divergence calls for;
 *  - it does not restate the declaration. If the declaration is edited or
 *    withdrawn, this docblock's evidence is still the measurement, and the
 *    audit's verdict follows the declaration, not this prose.
 *
 * The sibling that CAN see the width, `tests/MySql/TfDialogFiltersFamilyFkOnMysqlTest`,
 * is where an engine-level verdict on this column belongs; this file's own
 * position — that sqlite collapses both widths to `integer` and therefore cannot
 * judge the column either way — is unchanged and is still asserted below.
 *
 * ## sqlite CANNOT PROVE THE WIDTH, AND THIS FILE DOES NOT PRETEND OTHERWISE
 *
 * MEASURED on this tree, from the vendor grammar that decides the spelling:
 *
 *     Schema/Grammars/SQLiteGrammar.php:774  typeInteger()   => 'integer'
 *     Schema/Grammars/SQLiteGrammar.php:785  typeBigInteger() => 'integer'
 *     Schema/Grammars/MySqlGrammar.php:835    typeInteger()   => 'int'
 *     Schema/Grammars/MySqlGrammar.php:824    typeBigInteger() => 'bigint'
 *
 * and confirmed against a booted dial — `PRAGMA table_info` reports `id` as
 * `'INTEGER'` on the parent AND on all seven children, and `getColumns()` reports
 * `type_name => 'integer'` for every one of them. A booted dial is built below
 * in `test_sqlite_reports_one_integer_type_for_the_whole_family`, and that
 * assertion is the POINT: it proves no SQLite test could ever have detected the
 * width mismatch, so the proof lives where the width is decided.
 *
 * The consequence, stated rather than hidden: this file cannot assert that
 * `tf_dialog_filters.id` is `bigint` on the target engine. `tests/MySql/**`
 * owns that, it is not this lane's to touch, and asserting it here would be a
 * green lie. What this file DOES prove is everything sqlite can prove, and each
 * of those is behavioural rather than textual.
 *
 * ## THE CLOSED REFERENCE INVENTORY — WHY NOTHING CAN BE ORPHANED
 *
 * A widening orphans a row only if some column SOMEWHERE references
 * `tf_dialog_filters.id` and is narrower than the new parent. Measured against a
 * booted dial, three independent ways, all in
 * `test_the_reference_inventory_is_closed_at_six_and_all_children_are_wide()`:
 *
 *  - exactly 6 derived relations carry the root as their parent;
 *  - exactly 7 relations exist in the whole `tf_dialog_filters*` namespace, the
 *    7th being the depth-2 leg `000870` already constrains;
 *  - NO column in ANY shipped table has `dialog_filter` in its name, so there is
 *    no denormalised pointer column and no second route by which anything could
 *    reference this parent;
 *  - and `getForeignKeys()` reports 0 existing constraints into the root, so
 *    nothing already depends on the column's current type.
 *
 * ## WHY THE THREE REFUSALS IN 000870 ARE LEFT STANDING, AND WHY THAT IS NOT A
 * ## CONTRADICTION
 *
 * `000870`'s `REFUSED` records the mechanical reason the six were declined AT
 * THAT TIME. `git cat-file -e HEAD:src/Laravel/Migrations/2026_10_04_000870_…`
 * succeeds, so that file is SHIPPED and not editable in place — an install that
 * already ran it has it recorded as applied. `REFUSED` is a filter on `000870`'s
 * OWN loop, not a schema property, and this migration discovers independently,
 * so on a fresh install both run and the six DO end up constrained.
 * `test_000870_is_shipped_so_its_refusal_is_history_not_a_schema_property()`
 * asserts exactly that, which is what keeps the two files from reading as a
 * contradiction.
 */
final class TfDialogFiltersIdWidenTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control halves withhold the file
     * without duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_001100';

    /** Its full filename, for the shipped-file receipt. */
    private const MIGRATION_FILE = '2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php';

    /** The predecessor whose refusal this migration discharges. */
    private const PREDECESSOR = '2026_10_04_000870';

    /** The family ROOT, and the table whose `id` is widened. */
    private const FAMILY_ROOT = 'tf_dialog_filters';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_dialog_filters_';

    /**
     * The relations MEASURED to carry the root as their parent.
     *
     * MEASURED by booting the shipped dial and asking the engine, which is what
     * `test_the_reference_inventory_is_closed_at_six_and_all_children_are_wide()`
     * does — the count is read from the schema, never transcribed from a plan.
     *
     *   $ php -r '…MirrorRelationPlan::fromConnection($db)->singleParent()…'
     *     filtered to parent === tf_dialog_filters
     *     → 6, all with legs (account_id, id)
     *
     * That is the same 6 the anti-rot gate reports as unconstrained before this
     * migration and as 0 after it.
     */
    private const MEASURED_DEPTH_1 = 6;

    /**
     * Every relation in the family's namespace, the 7th being the depth-2 leg.
     *
     * MEASURED by the same query as above, unfiltered to the root's immediate
     * children: 6 at depth 1 plus `tf_dialog_filters_title_entities -> tf_
     * dialog_filters_title` at depth 2. There is no third depth.
     */
    private const MEASURED_FAMILY_RELATIONS = 7;

    /** A tenant every fixture plants first, so the tenant FK cannot be what refuses. */
    private const TENANT = 1724372757;

    /** A tenant `telegram_accounts` does NOT carry — proves enforcement. */
    private const FABRICATED_TENANT = 999999999999;

    /** Coordinates of a planted parent filter. */
    private const FILTER = 7;

    /** Coordinates of a filter that is NEVER planted — the orphan. */
    private const ABSENT_FILTER = 4242;

    /**
     * The longest child this migration constrains, and the suffix length.
     *
     * MEASURED, not chosen, for the same reason `000870` measures its own: MySQL
     * caps an identifier at exactly 64 characters (65 refused with ERROR 1059)
     * and sqlite caps it at none, so a name that is too long passes every sqlite
     * test and is refused on the first real ALTER. `000870` budgets against the
     * 32-character GRANDCHILD; this file constrains only depth-1 tables, whose
     * longest name is `tf_dialog_filters_exclude_peers` at 31 — so the longest
     * name produced here is 31 + 12 = 43, with 21 characters of headroom.
     */
    private const LONGEST_CHILD = 'tf_dialog_filters_exclude_peers';

    /**
     * Every suffix already in the dial that `down()` must be able to tell apart.
     *
     * A shared suffix is what made the tenant migration's `down()` remove 271
     * constraints instead of 265, so this is asserted rather than trusted.
     */
    private const OTHER_SUFFIXES = ['_df_parent_fk', '_msg_parent_fk', '_st_parent_fk', '_acc_tenant_fk'];

    // ------------------------------------------------------------------
    // the dial
    // ------------------------------------------------------------------

    /**
     * Every shipped migration, in apply order.
     *
     * From `UpdateIngestor::migrationPaths()` — the same list the ingest seam and
     * the MySQL lane run — rather than a glob of `src/Laravel/Migrations`,
     * because a glob here would be a second copy of the dial and a second copy
     * drifts. This migration's own file lives in that directory, so moving it out
     * of the curated dial fails this suite rather than quietly testing a dial
     * without it.
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
                // entity FILES already inside it, so a naive expansion applies
                // those twice and dies on "table already exists". Deduped by real
                // path, keeping apply order.
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

    /** The migration object, required afresh so constants read live. */
    private function migration(): object
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION_FILE;

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return $migration;
    }

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix is read out of the
     * file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        return (new ReflectionClass($this->migration()))->getConstant($name);
    }

    /**
     * The migration's OWN `depthOneRelations()`, invoked by reflection.
     *
     * A finished schema is a SET of declared facts: it cannot show which
     * relations were DISCOVERED or in what order they were written, so the
     * discovery half of the proof has to be exercised against the migration's own
     * code rather than read back out of the result.
     *
     * @return list<array{child:string, parent:string, columns:list<string>, reason:string}>
     */
    private function migrationDepthOneRelations(Connection $db): array
    {
        // ONE instance for both the reflection handle and the invocation: the
        // migration is an ANONYMOUS class (`return new class extends Migration`),
        // so each `require` yields a distinct class and a method handle taken
        // from one instance refuses an argument from another with
        // "Given object is not an instance of the class this method was declared
        // in" — measured, and the reason this looks the way it does.
        $migration = $this->migration();

        $method = new ReflectionMethod($migration, 'depthOneRelations');
        $method->setAccessible(true);

        $relations = $method->invoke($migration);

        Assert::assertIsArray($relations, 'depthOneRelations() did not return an array');

        return $relations;
    }

    // ------------------------------------------------------------------
    // discovery, re-implemented independently of MirrorRelationPlan
    // ------------------------------------------------------------------

    /**
     * The family relations, discovered from the BUILT schema by this file's own
     * code — NOT by asking `MirrorRelationPlan`, which is the class the
     * migration uses and therefore the thing under test.
     *
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving table-name prefix whose primary key is a subset of this table's
     * own; the legs are that parent's key narrowed to the columns present.
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
     * be declared in the parent's key ORDER.
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
     * The declared sqlite type of `$table`.`$column`, read off the engine.
     */
    private function declaredType(Connection $db, string $table, string $column): ?string
    {
        foreach ($db->select('SELECT * FROM pragma_table_info(?) WHERE name = ?', [$table, $column]) as $row) {
            $row = (array) $row;

            return isset($row['type']) ? (string) $row['type'] : null;
        }

        return null;
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /** Plant the tenant every fixture needs, so the tenant FK cannot be what refuses. */
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

    /** A depth-1 child row naming filter `$filterId`. */
    private function childRow(string $child, int $filterId): array
    {
        return ['account_id' => self::TENANT, 'id' => $filterId];
    }

    // ------------------------------------------------------------------
    // PROOF 1 — sqlite genuinely cannot see the width (why the proof is
    // BEHAVIOURAL, and why the width lives in the MySQL lane)
    // ------------------------------------------------------------------

    public function test_sqlite_reports_one_integer_type_for_the_whole_family(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $types = [];
        foreach ($this->familyRelations($db) as $relation) {
            $types[$relation['child']] = $this->declaredType($db, $relation['child'], 'id');
        }
        $types[self::FAMILY_ROOT] = $this->declaredType($db, self::FAMILY_ROOT, 'id');

        self::assertNotSame([], $types, 'no family relation was discovered, so nothing below is measured.');

        $distinct = array_unique(array_values($types));
        self::assertCount(
            1,
            $distinct,
            'sqlite now reports MORE THAN ONE declared type for `id` across this family: '
            .json_encode($types).'. That means either the grammar changed or a future driver separates the widths, '
            .'in which case THIS test is the place that must be rewritten to assert the real mismatch — and the '
            .'migration docblock\'s "sqlite cannot show the width" claim is stale.',
        );

        self::assertSame(
            'integer',
            strtolower((string) $types[self::FAMILY_ROOT]),
            'the root\'s declared sqlite type changed from `integer`. If sqlite now spells bigint differently, the '
            .'width becomes locally observable and the MySQL-only deferral recorded in '
            .self::MIGRATION.' and in '.self::PREDECESSOR.' must be revisited.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the CLOSED reference inventory, and the reason nothing orphans
    // ------------------------------------------------------------------

    public function test_the_reference_inventory_is_closed_at_six_and_all_children_are_wide(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $relations = $this->familyRelations($db);

        self::assertCount(
            self::MEASURED_FAMILY_RELATIONS,
            $relations,
            'the number of relations in the tf_dialog_filters family moved. If it grew, a NEW table may reference '
            .'the root with a NARROW column and the widening would strand it — re-measure and check the new '
            .'table\'s declared width before trusting this file.',
        );

        $depth1 = array_values(array_filter($relations, static fn (array $r): bool => $r['parent'] === self::FAMILY_ROOT));
        self::assertCount(
            self::MEASURED_DEPTH_1,
            $depth1,
            'the number of relations one hop below '.self::FAMILY_ROOT.' moved. Found: '
            .implode(', ', array_column($depth1, 'child')).'. This count IS the number of constraints '
            .self::MIGRATION.' writes, so a silent change here would mean a silent change in what ships.',
        );

        // No OTHER route by which anything could reference the root. This is the
        // check that makes "nothing can be orphaned" a measurement rather than an
        // argument: if a denormalised `dialog_filter*_id` column ever appears, the
        // closed inventory is false and the widening must be re-examined.
        $pointerColumns = [];
        foreach ($this->reflected($db)[0] as $table) {
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                if (str_contains((string) $column['name'], 'dialog_filter')) {
                    $pointerColumns[] = $table.'.'.$column['name'];
                }
            }
        }

        self::assertSame(
            [],
            $pointerColumns,
            'a column referencing a dialog filter now exists: '.implode(', ', $pointerColumns)
            .'. The closed-reference argument for widening rests on NO column outside these '
            .self::MEASURED_DEPTH_1.' tables referencing '.self::FAMILY_ROOT.'.id. A new pointer column could be '
            .'narrower than the widened parent, which is exactly the orphaning the widening must not cause.',
        );

        // Nothing already depends on the column's current type.
        $inbound = [];
        foreach ($this->reflected($db)[0] as $table) {
            foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === self::FAMILY_ROOT) {
                    $inbound[] = $table;
                }
            }
        }

        self::assertSame(
            self::MEASURED_DEPTH_1,
            count($inbound),
            'the number of tables carrying a constraint into '.self::FAMILY_ROOT.' is not '
            .self::MEASURED_DEPTH_1.'. Found '.count($inbound).': '.implode(', ', $inbound).'. Either a constraint '
            .'from another lane has landed here or one of this migration\'s six is missing.',
        );
    }

    public function test_every_depth_1_leg_is_the_roots_own_primary_key(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        [, , , $primaryKey] = $this->reflected($db);

        self::assertSame(
            ['account_id', 'id'],
            $primaryKey[self::FAMILY_ROOT] ?? [],
            self::FAMILY_ROOT.'\'s primary key moved. It is read from the engine\'s index listing in the engine\'s '
            .'own key ORDER, because a composite FOREIGN KEY declared in the wrong order is refused by MySQL '
            .'(3780) and by nothing in sqlite. If this moved, re-derive the legs rather than re-key.',
        );

        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                continue;
            }

            self::assertSame(
                $primaryKey[self::FAMILY_ROOT],
                $relation['columns'],
                "{$relation['child']}'s derived legs are NOT ".self::FAMILY_ROOT."'s own key. The migration "
                .'derives them, so this is a DERIVATION defect rather than a type exception — the fix is in '
                .'MirrorRelationPlan, not in a key.',
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the six constraints are DECLARED, over the right target
    // ------------------------------------------------------------------

    public function test_all_six_depth_1_relations_carry_a_cascading_constraint(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        $missing = [];
        $wrongRule = [];
        $declared = 0;

        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                continue;
            }
            $declared++;

            $found = null;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== self::FAMILY_ROOT) {
                    continue;
                }
                if (array_map('strval', $foreign['foreign_columns'] ?? []) === $relation['columns']) {
                    $found = $foreign;
                    break;
                }
            }

            if ($found === null) {
                $missing[] = "{$relation['child']} -> ".self::FAMILY_ROOT.' ['.implode(',', $relation['columns']).']';

                continue;
            }

            $rule = strtoupper((string) ($found['on_delete'] ?? ''));
            if ($rule !== 'CASCADE') {
                $wrongRule[] = "{$relation['child']}: on_delete={$rule}";
            }
        }

        self::assertSame(self::MEASURED_DEPTH_1, $declared, 'the declaration loop saw the wrong count.');
        self::assertSame(
            [],
            $missing,
            'these depth-1 relations declare NO foreign key to '.self::FAMILY_ROOT.', so a child naming a filter '
            .'that does not exist is admitted. Withhold '.self::MIGRATION.' and this is the failure you get.',
        );
        self::assertSame(
            [],
            $wrongRule,
            'the constraint exists but does not CASCADE. A dialog-filter child has no life outside its filter.',
        );
    }

    public function test_the_constraint_names_fit_the_budget_and_cannot_be_confused_with_another_migration(): void
    {
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');
        Assert::assertIsString($suffix);

        self::assertNotContains(
            $suffix,
            self::OTHER_SUFFIXES,
            'this migration\'s suffix collides with one already in the dial. A shared suffix makes down() unable to '
            .'tell whose constraints it holds, which is what made the tenant migration\'s down() remove 271 instead '
            .'of 265.',
        );

        $db = $this->enforcingConnection(...$this->migrations());

        self::assertSame(
            31,
            strlen(self::LONGEST_CHILD),
            'the longest depth-1 child name is no longer '.self::LONGEST_CHILD.' at 31 characters. Re-measure the '
            .'identifier budget in the migration docblock rather than trusting this arithmetic.',
        );

        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                continue;
            }

            self::assertLessThanOrEqual(
                64,
                strlen($relation['child'].$suffix),
                'the constraint name for '.$relation['child'].' is over MySQL\'s 64-character identifier limit, '
                .'which it refuses with ERROR 1059. sqlite has no limit at all, so this passes every local test and '
                .'fails on the first real ALTER.',
            );
        }
    }

    // ------------------------------------------------------------------
    // PROOF 4 — the CONTROL: withhold this file and the constraints are GONE
    // ------------------------------------------------------------------

    public function test_withholding_this_migration_leaves_the_six_unconstrained(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every assertion attributing a constraint to this migration would be '
            .'unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $depth1 = array_values(array_filter(
            $this->familyRelations($permissive),
            static fn (array $r): bool => $r['parent'] === self::FAMILY_ROOT,
        ));

        $carrying = 0;
        foreach ($depth1 as $relation) {
            foreach ($permissive->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === self::FAMILY_ROOT) {
                    $carrying++;
                    break;
                }
            }
        }

        self::assertSame(
            0,
            $carrying,
            'with '.self::MIGRATION.' withheld, '.$carrying.' of '.count($depth1).' depth-1 relations still carry a '
            .'constraint into '.self::FAMILY_ROOT.'. Something else in the dial created them, and the refusals this '
            .'migration discharges would be attributed to the wrong file.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 5 — the constraints genuinely BITE, and they BITE for the right reason
    // ------------------------------------------------------------------

    public function test_a_depth_1_orphan_is_refused_and_the_refusal_is_the_parent_fk(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());
        $this->plantTenant($db, self::TENANT);

        // A real tenant, and a REAL sibling child of the same absent filter, so
        // the row is wrong in EXACTLY ONE way: its parent filter does not exist.
        try {
            $db->table('tf_dialog_filters_color')->insert([
                'account_id' => self::TENANT,
                'id' => self::ABSENT_FILTER,
                'color' => 42,
            ]);
            self::fail(
                'a tf_dialog_filters_color row naming filter '.self::ABSENT_FILTER.', which does not exist, was '
                .'ACCEPTED on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant is real, so the '
                .'tenant constraint cannot be what should have refused it — the depth-1 leg is absent.',
            );
        } catch (QueryException $e) {
            self::assertStringContainsString(
                'FOREIGN KEY constraint failed',
                $e->getMessage(),
                'the insert must be refused BY THE FOREIGN KEY. Any other QueryException means the fixture is wrong '
                .'— it is not evidence the constraint bit.',
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
            $db->table('tf_dialog_filters_color')->count(),
            'a refused insert must leave nothing behind.',
        );
    }

    public function test_the_landing_above_is_only_evidence_on_an_enforcing_connection(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        // The refusal in the proof above only means something if this connection
        // refuses SOMETHING. A fabricated tenant must be refused by the tenant
        // FK, so a permissive connection fails here rather than turning the
        // refusal into a vacuous green.
        $this->plantTenant($db, self::TENANT);

        $refused = null;
        try {
            $db->table('tf_dialog_filters_color')->insert([
                'account_id' => self::FABRICATED_TENANT,
                'id' => self::FILTER,
                'color' => 43,
            ]);
        } catch (QueryException $e) {
            $refused = $e;
        }

        self::assertNotNull(
            $refused,
            'a row naming a tenant that does not exist was ACCEPTED on a connection handed out by '
            .'enforcingConnection(). The pragma is not on, so every refusal asserted in this file proves nothing.',
        );
        self::assertStringContainsString(
            'FOREIGN KEY constraint failed',
            $refused->getMessage(),
            'the fabricated tenant must be refused BY the tenant FK. Any other refusal means the depth-1 refusal '
            .'was measured against a connection enforcing something other than what this file claims.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 6 — a REAL row lands, and the hop-1 cascade now EXISTS
    // ------------------------------------------------------------------

    /**
     * The constraint must not refuse real engine rows, and `ON DELETE CASCADE`
     * must now travel the hop `000870` deliberately left uncascaded.
     *
     * `TfDialogFiltersFamilyForeignKeyTest::test_a_real_grandchild_row_lands_and_
     * _the_hop_two_cascade_is_proven` asserts the OPPOSITE of the last half of
     * this — that deleting the root does NOT take `tf_dialog_filters_title` with
     * it, because the depth-1 leg is absent. That assertion was a documented
     * limitation of the refusal, not a property worth keeping: this test is the
     * one that holds once the leg exists, and it is stated here so the pair cannot
     * be reconciled by weakening either.
     */
    public function test_a_real_depth_1_row_lands_and_the_hop_1_cascade_now_travels(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $this->plantTenant($db, self::TENANT);
        $this->plantFilter($db, self::FILTER);

        $db->table('tf_dialog_filters_color')->insert($this->childRow('tf_dialog_filters_color', self::FILTER) + ['color' => 42]);
        $db->table('tf_dialog_filters_title')->insert($this->childRow('tf_dialog_filters_title', self::FILTER) + ['text' => 'probe']);

        self::assertSame(
            1,
            $db->table('tf_dialog_filters_color')->count(),
            'a legitimate depth-1 row must LAND once its parent filter exists. A constraint that refuses its own '
            .'parent\'s children is an outage, not a safety net.',
        );

        $db->table(self::FAMILY_ROOT)
            ->where('account_id', self::TENANT)
            ->where('id', self::FILTER)
            ->delete();

        self::assertSame(
            0,
            $db->table('tf_dialog_filters_color')->count(),
            'deleting '.self::FAMILY_ROOT.' must take its color child with it (ON DELETE CASCADE), which is the '
            .'hop '.self::PREDECESSOR.' deliberately left uncascaded doing its job now that the leg exists.',
        );
        self::assertSame(
            0,
            $db->table('tf_dialog_filters_title')->count(),
            'the same must hold for the title child, which is the MIDDLE HOP of the two-level chain — so the chain '
            .'is now enforced at BOTH depths rather than only at the deeper one.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — 000870's REFUSED is history, not a live schema property
    // ------------------------------------------------------------------

    public function test_000870_is_shipped_so_its_refusal_is_history_not_a_schema_property(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        // The predecessor still exists and still names all six — it is history,
        // and editing a shipped file would leave every install that already ran
        // it unconstrained forever.
        self::assertFileExists(
            dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::PREDECESSOR
            .'_add_tf_dialog_filters_family_foreign_keys.php',
            'the predecessor migration is gone. Its REFUSED list is the record of why these six were declined; '
            .'deleting the file would delete the reasoning D-020 rests on.',
        );

        // ...and its REFUSED filter does NOT stop this migration from landing them,
        // which is what makes the two files consistent rather than contradictory.
        $constrained = 0;
        foreach ($this->familyRelations($db) as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                continue;
            }
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === self::FAMILY_ROOT) {
                    $constrained++;
                    break;
                }
            }
        }

        self::assertSame(
            self::MEASURED_DEPTH_1,
            $constrained,
            'the six are not constrained even though '.self::MIGRATION.' runs AFTER '.self::PREDECESSOR
            .' on a fresh install. If this fails, the discovery in the new migration stopped finding them — most '
            .'likely by filtering on a name prefix and sweeping in the depth-2 grandchild, or by excluding '
            .'relations the predecessor refused.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 8 — the migration's own discovery, invoked rather than trusted
    // ------------------------------------------------------------------

    public function test_the_migration_discovers_exactly_the_six_and_never_the_grandchild(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = $this->migrationDepthOneRelations($db);

        self::assertCount(
            self::MEASURED_DEPTH_1,
            $discovered,
            'the migration\'s own depthOneRelations() discovered '.count($discovered).' relations, not '
            .self::MEASURED_DEPTH_1.'. A finished schema cannot show what was DISCOVERED, so this is the only way '
            .'the discovery half of the proof is exercised.',
        );

        foreach ($discovered as $relation) {
            self::assertSame(
                self::FAMILY_ROOT,
                $relation['parent'],
                $relation['child'].' was selected with parent '.$relation['parent'].'. Selecting by name prefix '
                .'rather than by derived parent is the specific defect this forbids: it would sweep in the depth-2 '
                .'grandchild tf_dialog_filters_title_entities, whose constraint '.self::PREDECESSOR.' already '
                .'created, producing a duplicate-constraint error on MySQL.',
            );
        }

        self::assertNotContains(
            'tf_dialog_filters_title_entities',
            array_column($discovered, 'child'),
            'the depth-2 grandchild was selected. Its constraint belongs to '.self::PREDECESSOR.' and a second '
            .'constraint name here would be a duplicate on MySQL and a rollback this file could not own.',
        );
    }

    public function test_the_widen_is_executable_and_idempotent(): void
    {
        // The widen is step 1 of `up()` and is load-bearing: a FOREIGN KEY is
        // checked against the parent's type as it stands when the constraint is
        // CREATED, so widening after the six ALTERs would leave the target
        // engine's 3780 unaddressed. Idempotence matters because an install that
        // already ran this file must not fail on a re-run.
        $db = $this->enforcingConnection(...$this->migrations());

        $run = function () use ($db): string {
            $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION_FILE;
            $migration->up();

            return (string) $db->scalar('SELECT sql FROM sqlite_master WHERE name = ?', [self::FAMILY_ROOT]);
        };

        $first = $run();
        self::assertNotSame('', $first, 'no CREATE statement is recorded for '.self::FAMILY_ROOT.' after a re-run.');

        $second = $run();

        self::assertSame(
            $first,
            $second,
            'running '.self::MIGRATION.' twice produced a different CREATE statement for '.self::FAMILY_ROOT
            .'. The migration must be idempotent: an install that already ran it must not fail on a re-run, and the '
            .'second run must not silently drop the tenant foreign key that was there before it.',
        );

        // The tenant FK is the one a table rebuild on sqlite can lose, so assert
        // it survived rather than assuming the rebuild was faithful.
        $tenant = null;
        foreach ($db->getSchemaBuilder()->getForeignKeys(self::FAMILY_ROOT) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') === 'telegram_accounts') {
                $tenant = $foreign;
                break;
            }
        }

        self::assertNotNull(
            $tenant,
            self::FAMILY_ROOT.' no longer carries its tenant foreign key after the widen. Rebuilding the table to '
            .'change a column type can drop constraints that were not restated, and a widened table with no tenant '
            .'constraint accepts rows for accounts that do not exist.',
        );
    }
}
