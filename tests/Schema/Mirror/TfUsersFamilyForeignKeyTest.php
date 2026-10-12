<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use PHPUnit\Framework\Assert;
use ReflectionClass;

/**
 * The `tf_users` family is constrained to its parent, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000820_add_tf_users_family_foreign_keys.php` adds a FOREIGN KEY
 * from every single-parent relation whose parent is `tf_users` to that parent's
 * key. This file proves the constraints BITE — that a valid child row lands, that
 * an orphan is refused BY the constraint and not by anything else, and that the
 * whole thing disappears when the migration does.
 *
 * ## THE RELATION LIST WAS DERIVED, NOT TRANSCRIBED
 *
 * MEASURED on 2026-10-05 by running the anti-rot gate and filtering its RED list
 * for this family:
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep "→ tf_users$"
 *
 * → 19 relations, every one of them carrying the legs `(account_id, id)`. The
 * same run measured 97 single-parent relations tree-wide, so this family is 19 of
 * 97 and nothing more. No table name from that list appears anywhere below: this
 * file re-derives the family from the BUILT schema by its own code, exactly as
 * `TfMessagesFamilyForeignKeyTest` does, and a transcribed list would be a second
 * copy of the schema that drifts.
 *
 * ## WHY THE DISCOVERY IS RE-IMPLEMENTED HERE, ON PURPOSE
 *
 * Two implementations of one discovery is not duplication to be removed; it is the
 * only way the migration's discovery is CHECKED rather than trusted. This file
 * re-derives the family relations from the BUILT schema by its own code —
 * longest-prefix ancestry whose parent's primary key is a subset of the child's,
 * intersected down to the parent's key — and asserts the result against the
 * executed DDL. If this and the migration's `familyRelations()` ever disagree, one
 * of the assertions below goes red naming the difference.
 *
 * ## WHY EVERY BEHAVIOURAL ASSERTION RUNS ON AN ENFORCING CONNECTION
 *
 * `ShippedStoreSchema` boots sqlite with `PRAGMA foreign_keys` OFF, where a
 * declared constraint is INERT: the DDL is in `sqlite_master`,
 * `Schema::getForeignKeys()` still reports it, and nothing raises. A negative
 * test written on that default is green because no check happened, and would stay
 * green if the migration were deleted outright. Every behavioural connection here
 * goes through `RelationConstraintTestCase::enforcingConnection()`, which turns
 * the pragma on and READS IT BACK before handing it over.
 *
 * ## THE TENANT CONSTRAINT IS THE TRAP THIS FIXTURE IS BUILT AROUND
 *
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). So an "orphan" child built naively —
 * inventing an `account_id` that no tenant carries — is refused by the TENANT
 * constraint, and every assertion below would be green while this migration
 * contributed nothing.
 *
 * Every fixture therefore plants a REAL tenant first, and makes the orphan-ness
 * live ONLY in the user id. The tenant is valid, the parent user row does not
 * exist, so the only constraint that can refuse these rows is the one this
 * migration adds.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * row into the SAME table on the SAME ENGINE — with `PRAGMA foreign_keys` read
 * back as 1 — with exactly one file withheld from the dial, and requires it to
 * LAND. Enforcement being ON in the control is what makes the two halves differ
 * by exactly one variable: the file.
 */
final class TfUsersFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000820';

    /** The family ROOT. Descendants are discovered by name prefix, never listed. */
    private const FAMILY_ROOT = 'tf_users';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_users_';

    /**
     * The number of single-parent relations MEASURED inside this family on
     * 2026-10-05 (19, every one carrying `(account_id, id)`), and the number this
     * migration therefore constrains.
     *
     * Both are pinned so that discovery cannot silently change length in either
     * direction. If the family gains or loses a table, re-measure and bump BOTH
     * here and in the migration — never edit a count to reach a green run.
     */
    private const MEASURED_RELATIONS = 19;

    private const MEASURED_CONSTRAINED = 19;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /**
     * The witness child, and the ONLY reason it was chosen.
     *
     * `tf_users_lang_code` has exactly three columns, all NOT NULL, two of which
     * are the parent user key and one of which is the payload. So the fixture
     * cannot accidentally satisfy the constraint through some other declared FK,
     * there is nothing else to build, and every column the constraint names is
     * visible in the insert.
     */
    private const CHILD = 'tf_users_lang_code';

    /** Id of the planted parent user. */
    private const USER = 424242;

    /** An id that is NEVER planted — the orphan. */
    private const ABSENT_USER = 999999999;

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
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix and the refusal
     * list are read out of the file itself by reflection.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_users_family_foreign_keys.php';

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
            // must be declared in the parent's key ORDER, which is not the order the
            // columns sit in the table.
            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);
                    break;
                }
            }
            $primaryKey[$table] = $key;
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

    private function plantUser(Connection $db, int $userId): void
    {
        $db->table('tf_users')->insert([
            'account_id' => self::TENANT,
            'id' => $userId,
            'constructor' => 'tf_users',
        ]);
    }

    /**
     * A child row whose TENANT is real and whose parent user row does not exist.
     */
    private function orphanRow(int $userId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $userId,
            'lang_code' => 'en',
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
            'the number of single-parent relations inside the tf_users family moved. That is expected when the '
            .'family gains or loses a table — but then this constant AND the docblock in '.self::MIGRATION
            .'_add_tf_users_family_foreign_keys.php are both stale. Re-measure with the anti-rot gate (the '
            .'command is in this file\'s docblock) and bump BOTH. Never edit this count to reach a green run: '
            .'it is the only thing standing between a deleted mirror table and a silent green.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused). If only one '
            .'of those two numbers moved, the constant and the REFUSED list have drifted apart.',
        );

        // The parent must be the family ROOT for every relation in it. A descendant
        // pointing at another descendant is a DIFFERENT family relation and would
        // be constrained by that descendant's own lane, not this one.
        $notRooted = [];
        foreach ($discovered as $relation) {
            if ($relation['parent'] !== self::FAMILY_ROOT) {
                $notRooted[] = $relation['child'].' → '.$relation['parent'];
            }
        }

        self::assertSame(
            [],
            $notRooted,
            'these family members do not hang off '.self::FAMILY_ROOT.', so constraining them to the root would '
            .'be a DIFFERENT relation than the plan derived. Name the right family in the plan rather than '
            .'widening this one.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_user_parent_foreign_key(): void
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
                // DERIVED legs. A table may carry several constraints to the same
                // parent legitimately (a tenant FK points elsewhere, but a
                // pre-existing user FK with different legs must not be mistaken for
                // this one).
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
            'these family relations declare NO foreign key to their user parent, so a user field row naming a '
            .'user that does not exist is admitted. Delete '.self::MIGRATION
            .'_add_tf_users_family_foreign_keys.php and this is the failure you get — which is why it is '
            .'asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A user row re-placed under a shorter field vector must '
            .'take its old children with it, or the dropped ones are orphaned under a re-placed parent; a field '
            .'row outliving its user fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the REFUSED list is a FILTER with reasons, not dead code
    // ------------------------------------------------------------------

    /**
     * `REFUSED` is asserted to be well-formed and to name only real family
     * relations, whether or not it is currently populated.
     *
     * It is EMPTY for this family — MEASURED, not omitted: all 19 relations carry
     * the parent's own `(account_id, id)` leg set with the parent's own column
     * types, so none of them needs an exception. The filter is kept and the
     * constant is kept because the sibling `tf_messages` family DOES carry a
     * refusal. A filter kept only in the family that needs one is a filter that has
     * never been exercised; asserting the SHAPE here is what keeps it honest.
     */
    public function test_every_refusal_names_a_real_family_relation_and_carries_its_reason(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discovered = array_column($this->familyRelations($db), 'child');
        $refused = $this->refused();

        foreach ($refused as $child) {
            self::assertContains(
                $child,
                $discovered,
                "REFUSED names {$child}, which discovery does not find in this family. A refusal for a table "
                .'outside the family is either a typo or a relation that belongs to another lane.',
            );
        }

        self::assertCount(
            self::MEASURED_RELATIONS - count($refused),
            array_diff($discovered, $refused),
            'the constrained count must be discovered − refused, always. If this fires, either the pin or the '
            .'REFUSED list moved without the other.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — CONTROL: the orphan is ADMITTED without this migration
    // ------------------------------------------------------------------

    public function test_the_orphan_is_admitted_without_this_migration(): void
    {
        $withheld = $this->migrations(withholdThisMigration: true);

        // The control withholds exactly one file, and it must be this migration.
        // If the filter matched nothing, this control is not a control and PROOF 5
        // would be proving something other than what it claims.
        self::assertSame(
            1,
            count($this->migrations()) - count($withheld),
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Enforcement is ON here too, on purpose. The default
        // `ShippedStoreSchema` pragma is OFF, and running the control permissively
        // would make the two halves differ by TWO variables (the file AND the
        // pragma) instead of one. This is the shipped dial minus one file, with
        // `PRAGMA foreign_keys` READ BACK as 1.
        $db = $this->enforcingConnection(...$withheld);
        self::assertSame(1, $this->foreignKeysPragma($db));

        $this->plantTenant($db, self::TENANT);
        $db->table(self::CHILD)->insert($this->orphanRow(self::ABSENT_USER));

        self::assertSame(
            1,
            $db->table(self::CHILD)->count(),
            'the orphan must actually LAND without '.self::MIGRATION.': its tenant is real and enforcement is on, '
            .'so if this row were refused the refusal came from another constraint and PROOF 5 would be proving '
            .'that instead of this migration.',
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
            $db->table(self::CHILD)->insert($this->orphanRow(self::ABSENT_USER));
            self::fail(
                'a '.self::CHILD.' row naming user '.self::ABSENT_USER.', which does not exist, was ACCEPTED on '
                .'a connection whose PRAGMA foreign_keys was read back as 1. Its tenant IS valid, so the tenant '
                .'constraint cannot be what should have refused it — either this migration\'s constraint is '
                .'absent, or enforcement is not on, and both mean this suite is not a gate.',
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
        $this->plantUser($db, self::USER);

        $row = $this->orphanRow(self::USER);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->first();
        self::assertNotNull($landed, 'the legitimate row must LAND. A constraint that refuses real engine rows is '
            .'worse than no constraint: it is an outage, not a safety net.');

        // The constrained columns, read back as the values inserted.
        foreach (['account_id', 'id'] as $leg) {
            self::assertSame(
                $row[$leg],
                (int) $landed->{$leg},
                "the constrained column {$leg} must come back as the value inserted; a mismatch would mean the "
                .'constraint is over a different column set than the one this fixture built.',
            );
        }

        $db->table('tf_users')
            ->where('account_id', self::TENANT)
            ->where('id', self::USER)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD)->count(),
            'deleting the parent user must take its field rows with it (ON DELETE CASCADE). A field row outliving '
            .'its user fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite test
     * and fails on the first real ALTER — which is exactly how the tenant
     * migration's first spelling shipped green and was refused.
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
            'a constrained leg disagrees with its parent\'s column type or signedness. MySQL refuses the ALTER '
            .'with error 3780 and sqlite cannot raise it at all, so this is the one defect class a sqlite-only '
            .'suite reports as green forever.',
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
