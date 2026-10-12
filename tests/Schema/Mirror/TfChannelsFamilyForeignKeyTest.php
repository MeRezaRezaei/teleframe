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
 * The `tf_channels` family is constrained to its parent, and the database says so.
 *
 * ## WHAT IS BEING PROVED
 *
 * `2026_10_04_000830_add_tf_channels_family_foreign_keys.php` adds a FOREIGN KEY
 * from every single-parent relation inside the `tf_channels` family to that
 * parent's key. This file proves the constraints BITE — that a valid child row
 * lands, that an orphan is refused BY the constraint and not by anything else,
 * and that the whole thing disappears when the migration does.
 *
 * ## THE RELATION COUNT WAS MEASURED HERE, IN THIS LANE
 *
 *     timeout -k 10 900 vendor/bin/phpunit --no-coverage \
 *       --filter test_every_single_parent_relation_has_a_real_constraint \
 *       tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php 2>&1 \
 *       | grep -oE "tf_[a-z0-9_]+\([^)]*\) → tf_[a-z0-9_]+" | sort -u \
 *       | grep "→ tf_channels" | wc -l
 *     → 18
 *
 * The same run measured **97** single-parent relations tree-wide, not the 128 the
 * plan's baseline table carries, because sibling lanes had already landed their
 * families by the time this lane ran — which is exactly why the count is
 * re-derived per lane and never copied (D-010). The 18 this family pins agrees
 * with the plan's table, so nothing had to be overridden here.
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
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). So an "orphan" child built naively —
 * inventing an `account_id` that no tenant carries — is refused by the TENANT
 * constraint, and every assertion below would be green while this migration
 * contributed nothing.
 *
 * Every fixture below therefore plants a REAL tenant first, and makes the
 * orphan-ness live ONLY in the channel id. The tenant is valid, the parent
 * channel does not exist, so the only constraint that can refuse these rows is
 * the one this migration adds.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * `test_the_orphan_is_admitted_without_this_migration()` inserts the SAME orphan
 * row into the SAME table on the SAME engine with exactly one file withheld from
 * the dial, and requires it to LAND. That is what makes the refusal in
 * `test_the_orphan_is_refused()` attributable to this migration rather than to
 * sqlite, to the pragma, or to the tenant constraint.
 */
final class TfChannelsFamilyForeignKeyTest extends RelationConstraintTestCase
{
    /**
     * The path fragment naming THIS migration's file.
     *
     * Substring, not a full name, so the control half withholds the file without
     * duplicating the date stamp the migration is renamed under.
     */
    private const MIGRATION = '2026_10_04_000830';

    /** The family ROOT. Descendants are discovered by name prefix, never listed. */
    private const FAMILY_ROOT = 'tf_channels';

    /** A descendant of the root keeps its name. */
    private const FAMILY_PREFIX = 'tf_channels_';

    /**
     * The number of single-parent relations MEASURED inside this family, and the
     * number this migration therefore constrains.
     *
     * 18 of 18: `REFUSED` is EMPTY for this family, and that is a measurement
     * rather than an omission — every one of the 18 carries the parent's own
     * `(account_id, id)` leg set with the parent's own column types, so none of
     * them needs an exception. The two 1:N children
     * (`tf_channels_restriction_reason` and `tf_channels_usernames`, both keyed
     * `(account_id, id, position)`) yield `(account_id, id)` because the parent's
     * key is what a FOREIGN KEY must reference, and the extra `position` is what
     * makes them 1:N — it stays in the child's own key.
     *
     * Both numbers are pinned so that discovery cannot silently change length in
     * either direction. If the family gains or loses a table, re-measure and bump
     * BOTH here and in the migration — never edit a count to reach a green run.
     */
    private const MEASURED_RELATIONS = 18;

    private const MEASURED_CONSTRAINED = 18;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 1724372757;

    /**
     * The witness child, and the ONLY reason it was chosen.
     *
     * `tf_channels_level` has exactly three columns, all NOT NULL, two of which
     * are the parent channel key and one of which is the payload — MEASURED off
     * the executed DDL, above. So the fixture cannot accidentally satisfy the
     * constraint through some other declared FK (the only FK it carries today is
     * the tenant one), there is nothing else to build, and every column the
     * constraint names is visible in the insert.
     */
    private const CHILD = 'tf_channels_level';

    /** Id of the planted parent channel. Telegram channel ids are often negative. */
    private const CHANNEL_ID = -1001234567890;

    /** The payload column of the witness child — the only non-key column. */
    private const CHILD_VALUE_COLUMN = 'level';

    private const CHILD_VALUE = 42;

    /** An id no parent channel carries — the orphan. */
    private const ABSENT_CHANNEL_ID = -1009999999999;

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
                // entity FILES that already live inside it. Deduped by real path,
                // keeping apply order, because the curated directory is listed
                // first and therefore owns the position of both files.
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

    /** This migration's file, as an absolute path. */
    private function migrationPath(): string
    {
        return dirname(__DIR__, 3).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_channels_family_foreign_keys.php';
    }

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke the rollback, so the suffix and the refusal
     * list are read out of the file itself by reflection.
     *
     * ## WHY IT ANSWERS null INSTEAD OF THROWING WHEN THE FILE IS ABSENT
     *
     * PROOF 2 is the test whose failure message must NAME the absent
     * constraints. A `require` of a missing file aborts it with "Failed opening
     * required …" before the assertion that lists `child -> parent [legs]` ever
     * runs — which is a red suite for a reason nobody can act on, and it is
     * precisely the "it was supposed to be red" trap: the run goes red without
     * the gate having said anything about the schema.
     *
     * So a missing file yields `null` here, and the two proofs that DEPEND on
     * the migration existing — `test_the_migration_file_ships_in_the_curated_
     * dial` and PROOF 4's "withholds exactly one file" count — are what make
     * the file's absence loud, with a message that names it.
     */
    private function migrationConstant(string $name): mixed
    {
        if (! is_file($this->migrationPath())) {
            return null;
        }

        $migration = require $this->migrationPath();

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new ReflectionClass($migration))->getConstant($name);
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
            // `getColumns()` carries no key position, and a composite FOREIGN
            // KEY must be declared in the parent's key ORDER, which is not the
            // order the columns sit in the table.
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
     * The children the migration REFUSES to constrain, by its own account.
     *
     * Tolerant of a missing migration file (see `migrationConstant()`), because
     * PROOF 2 must reach the assertion that NAMES the absent constraints rather
     * than abort one line earlier. An empty result therefore does NOT prove the
     * migration has no refusals — PROOF 3 is what proves that, and it asserts
     * the constant is an array, so a vanished file fails there.
     *
     * @return list<string>
     */
    private function refused(): array
    {
        $refused = $this->migrationConstant('REFUSED');

        if (! is_array($refused)) {
            return [];
        }

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
     * Plant the parent channel row.
     *
     * `constructor` is the only NOT NULL column without a default on the parent
     * besides the key itself — MEASURED off the executed DDL — so the fixture
     * supplies exactly what the engine demands and nothing else.
     */
    private function plantChannel(Connection $db, int $channelId): void
    {
        $db->table(self::FAMILY_ROOT)->insert([
            'account_id' => self::TENANT,
            'id' => $channelId,
            'constructor' => 'channel',
        ]);
    }

    /**
     * A `tf_channels_level` row naming `$channelId`.
     *
     * The tenant is always REAL; only the channel id varies between the
     * well-parented fixture and the orphan.
     */
    private function childRow(int $channelId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $channelId,
            self::CHILD_VALUE_COLUMN => self::CHILD_VALUE,
        ];
    }

    // ------------------------------------------------------------------
    // PROOF 0 — this migration SHIPS, in the curated dial, under this name
    // ------------------------------------------------------------------

    /**
     * The file exists, at the path every other proof in this file reads it from,
     * and the curated dial actually runs it.
     *
     * `migrationConstant()` answers `null` for an absent file so PROOF 2 can
     * reach its assertion and NAME the constraints that are missing. That
     * tolerance has to be paid for here, or a deleted migration would leave
     * PROOF 2 red for a reason nobody can act on and PROOF 3 vacuously green.
     */
    public function test_the_migration_file_ships_in_the_curated_dial(): void
    {
        self::assertFileExists(
            $this->migrationPath(),
            self::MIGRATION.'_add_tf_channels_family_foreign_keys.php is not on disk. Every other proof in '
            .'this file reads the family\'s discovery and constraint suffix out of that file, so without it '
            .'this suite measures nothing.',
        );

        // The dial runs it, not merely the filesystem: a file renamed so that no
        // migration name sorts it into the dial would satisfy assertFileExists
        // and constrain nothing.
        $inDial = array_filter(
            $this->migrations(),
            static fn (string $file): bool => str_contains(basename($file), self::MIGRATION),
        );

        self::assertCount(
            1,
            $inDial,
            self::MIGRATION.' must be reached by UpdateIngestor::migrationPaths() exactly once. It is on disk '
            .'but not applied, so no constraint it declares exists on any engine — and this suite would go '
            .'green proving a migration that never runs.',
        );
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
            'the number of single-parent relations inside the tf_channels family moved. That is expected when '
            .'the family gains or loses a table — but then this constant AND the docblock in '
            .self::MIGRATION.'_add_tf_channels_family_foreign_keys.php are both stale. Re-measure on the '
            .'target engine and bump BOTH. Never edit this count to reach a green run: it is the only thing '
            .'standing between a deleted mirror table and a silent green.',
        );

        self::assertCount(
            self::MEASURED_CONSTRAINED,
            array_diff(array_column($discovered, 'child'), $this->refused()),
            'the number of relations this migration actually constrains is (discovered − refused). If only one '
            .'of those two numbers moved, the constant and the REFUSED list have drifted apart.',
        );

        // Every discovered relation must really hang under the family ROOT's own
        // subtree. Discovery is by name prefix, so a `tf_channels_*` table whose
        // true parent were something else would pass the count assertions above
        // and then be constrained against the wrong table. This names the parent
        // set instead of assuming it.
        $parents = array_unique(array_column($discovered, 'parent'));
        self::assertContains(
            self::FAMILY_ROOT,
            $parents,
            'no discovered relation hangs under '.self::FAMILY_ROOT.' itself, so this family pins a count that '
            .'belongs to no relation at all — re-read the ancestry rule before trusting it.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the DECLARATION, on every relation the migration claims
    // ------------------------------------------------------------------

    public function test_every_family_relation_declares_its_channel_parent_foreign_key(): void
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
                // The constraint this migration owns is the one whose legs are
                // the DERIVED legs. A table may carry several constraints to the
                // same parent legitimately.
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
            'these family relations declare NO foreign key to their channel parent, so a channel child row '
            .'naming a channel that does not exist is admitted. Delete '
            .self::MIGRATION.'_add_tf_channels_family_foreign_keys.php and this is the failure you get — which '
            .'is why it is asserted, not assumed.',
        );

        self::assertSame(
            [],
            $wrong,
            'the constraint exists but does not CASCADE. A re-delivered channel with a shorter field vector '
            .'must take its old children with it, or the dropped ones are orphaned under a re-placed parent; '
            .'a child that outlives its parent fact is the state this whole family constraint exists to '
            .'prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 3 — the REFUSED list is SHAPED and IN-FAMILY
    // ------------------------------------------------------------------

    /**
     * `REFUSED` is empty for this family — MEASURED, not assumed — so this proof
     * cannot assert a refusal exists. What it CAN assert is that the list is a
     * well-formed, in-family filter rather than an unexamined constant, because a
     * refusal added later without a mechanical reason, or aimed at a table
     * outside this family, must fail HERE rather than silently shrink the count.
     *
     * The sibling `tf_messages` family carries a real refusal
     * (`tf_messages_reply_markup_rows_buttons`, whose parent-key intersection
     * picks the button's index WITHIN its row instead of the row it hangs
     * under), and `TfMessagesFamilyForeignKeyTest::test_the_refused_relation_
     * carries_only_the_shipped_correct_constraint` is the proof for that case.
     */
    public function test_the_refusal_list_is_shaped_and_every_refusal_is_a_real_family_relation(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        $discoveredChildren = array_column($this->familyRelations($db), 'child');
        $refused = $this->refused();

        self::assertSame(
            [],
            array_values(array_diff($refused, $discoveredChildren)),
            'REFUSED names a table that is NOT a discovered relation of this family. A refusal aimed at a table '
            .'outside the family is either a stale name or a typo, and either way it is not being enforced by '
            .'this file — so a relation that needs one is not getting it.',
        );

        // Asserted explicitly, not left to PROOF 1's arithmetic, because the
        // honest reading of this family is "empty" and a reader must be able to
        // tell an empty list from a filter that stopped being read.
        self::assertSame(
            [],
            $refused,
            'REFUSED is no longer empty, so this file\'s "all 18 constrained" claim is stale. A refusal is a '
            .'NAMED mechanical exception: record the reason here and in the migration\'s docblock, and keep '
            .'the count arithmetic in PROOF 1 honest.',
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
            .'. If the filter matched nothing, every refusal asserted elsewhere in this file is unattributable.',
        );

        // Deliberately the shipped default, NOT enforcingConnection(): the point
        // is what the dial WITHOUT this migration permits. The pragma state is
        // asserted so a future default cannot quietly change what this means.
        $permissive = ShippedStoreSchema::capsuleFor(...$withheld)
            ->getConnection();
        self::assertSame(0, $this->foreignKeysPragma($permissive));

        $this->plantTenant($permissive, self::TENANT);
        $permissive->table(self::CHILD)->insert($this->childRow(self::ABSENT_CHANNEL_ID));

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
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_CHANNEL_ID));
            self::fail(
                'a '.self::CHILD.' row naming channel '.self::ABSENT_CHANNEL_ID.', which does not exist, was '
                .'ACCEPTED on a connection whose PRAGMA foreign_keys was read back as 1. Its tenant IS valid, '
                .'so the tenant constraint cannot be what should have refused it — either this migration\'s '
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
        $this->plantChannel($db, self::CHANNEL_ID);

        $row = $this->childRow(self::CHANNEL_ID);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->first();
        self::assertNotNull($landed, 'the legitimate row must LAND. A constraint that refuses real engine rows '
            .'is worse than no constraint: it is an outage, not a safety net.');

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
            ->where('id', self::CHANNEL_ID)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::CHILD)->count(),
            'deleting the parent channel must take its children with it (ON DELETE CASCADE). A child row '
            .'outliving its parent fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 7 — every constraint NAME fits the engine's identifier budget
    // ------------------------------------------------------------------

    /**
     * MEASURED on MySQL 8.4.11: the limit is exactly 64 characters accepted, 65
     * refused with `ERROR 1059 ... Identifier name is too long`. sqlite has NO
     * identifier limit at all, so a name that is too long passes every sqlite
     * test and fails on the first real ALTER — which is exactly how the tenant
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
     * sqlite has no signedness to disagree about. Asserting it here — against
     * the executed DDL of BOTH tables — is the only local check available, and
     * it is why the MySQL lane exists.
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
