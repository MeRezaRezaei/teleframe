<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Tests\Schema\Mirror\UpdateDifferencesMiscFamilyForeignKeyTest;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * This family's foreign keys on the TARGET engine: real MySQL 8.4 / InnoDB.
 *
 * ## WHY THIS FILE EXISTS AND CANNOT BE REPLACED BY THE SQLITE GATE
 *
 * `tests/Schema/Mirror/UpdateDifferencesMiscFamilyForeignKeyTest.php` proves the
 * constraints are declared, enforced and gone when the migration is withheld —
 * on sqlite. sqlite cannot express the failures that decide whether this family
 * can ship AT ALL:
 *
 *  - **6125** (parent key not unique). The heart of this family: five parents
 *    with five different keys, four of them keyed on `position` and one on a
 *    three-column `(account_id, seq, position)`. Legs taken from the CHILD's key
 *    instead of the parent's would reference a non-unique column set, and sqlite
 *    reports that as green forever.
 *  - **3780** (FK column signedness). Five different integer widths across five
 *    parents — `position` is a smallint, `seq` an int, `id` a bigint — which is
 *    exactly where a signedness disagreement hides.
 *  - **1059** (constraint name over 64 characters). sqlite has no identifier
 *    limit, so a too-long name passes every sqlite test and is refused by the
 *    first real ALTER.
 *  - **1170** (TEXT in a PRIMARY KEY).
 *
 * And the refusal itself: on MySQL the orphan refusal is **error 1452**, read
 * from `$e->errorInfo[1]` — the DRIVER slot. The SQLSTATE slot carries the
 * generic `HY000`/`23000`, so a test that asserted the SQLSTATE would pass on any
 * error at all and prove nothing.
 *
 * ## EVERY SHAPE IS PROVEN, NOT ONE REPRESENTATIVE
 *
 * The plan calls this family "the most heterogeneous — five different join
 * shapes". Proving one shape and generalising would leave four unproven, and this
 * family is where the shapes actually differ, so each is proven independently
 * through the same three-part structure:
 *
 *  1. a valid child row **lands**;
 *  2. the orphan is **refused with 1452**;
 *  3. the **control half** — the same orphan with
 *     `SET SESSION foreign_key_checks = 0` — **lands**.
 *
 * Part 3 is what makes part 2 evidence. A harness that is unconditionally
 * permissive raises in neither pass and fails the control; a harness that is
 * unconditionally strict refuses the control too. Only the pair distinguishes
 * them.
 *
 * ## WHY A REAL TENANT IS PLANTED FIRST
 *
 * Every `tf_*` table here carries a tenant FK on `account_id` to
 * `telegram_accounts.user_id`. An orphan built naively — a fabricated
 * `account_id` — would be refused by THAT constraint, and every 1452 assertion
 * below would be green while this migration contributed nothing. So a real tenant
 * is planted and the orphan-ness lives ONLY in the parent's own key coordinates.
 *
 * ## THE PARENT-KEY PROOF, READ FROM information_schema
 *
 * `test_the_parent_keys_really_are_unique_on_this_engine()` reads each parent's
 * PRIMARY index back from `information_schema.STATISTICS` and asserts the derived
 * leg set equals it. This is the assertion that turns "sqlite accepted it" into
 * "MySQL can reference it": a leg set that is not a UNIQUE index on the parent is
 * refused by the DDL itself with 6125, before any of these rows is inserted.
 *
 * @see UpdateDifferencesMiscFamilyForeignKeyTest
 *      for the sqlite half, the RED-first history and the measured
 *      `differenceLink()` inline-FK defect that made four of these nine relations
 *      unconstrained on BOTH engines before this migration.
 */
final class UpdateDifferencesMiscFamilyFkOnMysqlTest extends MySqlTestCase
{
    /**
     * The path fragment naming this family's migration.
     *
     * Substring, so withholding it below does not duplicate the date stamp the
     * file is renamed under.
     */
    private const MIGRATION = '2026_10_04_000900';

    /**
     * The family's five roots, read from the migration rather than transcribed —
     * a test that hardcodes what it is checking cannot notice the change that
     * broke it.
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

    /** A `telegram_accounts.user_id` every fixture below plants first. */
    private const TENANT = 1724372757;

    /**
     * Parent coordinates that are NEVER planted — the orphan, per shape.
     *
     * Distinct from the planted coordinates so a fixture that accidentally reuses
     * them fails its own assertion rather than proving a row that could match.
     *
     * @var array<string, mixed>
     */
    private const ABSENT = [
        'position' => 4242,
        'seq' => 4242,
        'link' => 'absent-link',
        'saved_reaction_tag_id' => 4242,
        'id' => 4242,
    ];

    /** @var array<string, array<string, mixed>> */
    private const PARENT_ROWS = [
        'tf_update_differences' => ['account_id' => self::TENANT, 'position' => 1, 'constructor' => 'differenceEmpty'],
        'tf_updates_pts' => ['account_id' => self::TENANT, 'seq' => 1, 'position' => 2, 'pts' => 99],
        'tf_business_chat_links' => ['account_id' => self::TENANT, 'link' => 'live-link', 'message' => 'm', 'views' => 3],
        'tf_wallpapers' => ['account_id' => self::TENANT, 'id' => 5, 'constructor' => 'wallPaper'],
        'tf_saved_reaction_tags' => ['account_id' => self::TENANT, 'saved_reaction_tag_id' => 7],
    ];

    /**
     * Every witness child row, keyed by child — the CHILD's own columns, never
     * the parent row reused.
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
     * `tf_updates_pts` carries its OWN foreign key to `tf_updates` over the same
     * three columns (`linkChild()` in `2026_09_14_200030`), so it cannot be
     * planted before `tf_updates` exists. The orphan control half plants this
     * ancestor at the ORPHAN coordinates so that shipped constraint cannot be
     * what refuses the row — see the sqlite gate's `plantAncestors()` for the
     * full argument.
     *
     * @var array<string, list<array{0:string, 1:array<string,mixed>}>>
     */
    private const ANCESTOR_ROWS = [
        'tf_updates_pts' => [
            ['tf_updates', ['account_id' => self::TENANT, 'seq' => 1, 'position' => 2, 'constructor' => 'updates']],
        ],
    ];

    /**
     * The PLANTED parent coordinates — the leg columns of each relation.
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
     * One case per JOIN SHAPE, so no shape is left unproven on the target engine.
     *
     * @return array<string, array{0:string, 1:string, 2:string, 3:string}>
     */
    public static function shapes(): array
    {
        // THREE values per set, because that is what both consumers accept.
        //
        // A fourth was supplied and PHPUnit reported, on every run, "Data set … has
        // more arguments (4) than the test method accepts (3)" — five times, once
        // per shape, twice over. MEASURED on this file. It was harmless only
        // because the fourth value was byte-identical to the first in every set
        // (`tf_update_differences_date` = the child, `tf_updates_pts_count` = the
        // child, and so on), so dropping it loses nothing.
        //
        // The alternative — widening the two signatures to four parameters — would
        // keep a value that says the same thing twice and invite the two names to
        // drift apart, which is precisely how a fixture starts contradicting
        // itself.
        return [
            'shape (account_id,position)' => [
                'tf_update_differences_date', 'tf_update_differences', 'account_id,position',
            ],
            'shape (account_id,seq,position)' => [
                'tf_updates_pts_count', 'tf_updates_pts', 'account_id,seq,position',
            ],
            'shape (account_id,link)' => [
                'tf_business_chat_links_title', 'tf_business_chat_links', 'account_id,link',
            ],
            'shape (account_id,saved_reaction_tag_id)' => [
                'tf_saved_reaction_tags_title', 'tf_saved_reaction_tags', 'account_id,saved_reaction_tag_id',
            ],
            'shape (account_id,id)' => [
                'tf_wallpapers_settings', 'tf_wallpapers', 'account_id,id',
            ],
        ];
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the parent's key really is UNIQUE on this engine (6125)
    // ------------------------------------------------------------------

    /**
     * Each parent's PRIMARY index, read from `information_schema.STATISTICS`, is
     * exactly the leg set its relations use.
     *
     * This is the assertion sqlite cannot make. A foreign key may only reference
     * a UNIQUE index; anything else is refused by the DDL with error 6125 and the
     * migration would not have run at all — so this also proves the dial really
     * executed rather than silently skipping.
     */
    public function test_the_parent_keys_really_are_unique_on_this_engine(): void
    {
        $db = $this->migratedMysqlConnection();

        foreach (self::shapes() as $label => [$child, $parent, $legs]) {
            $key = $this->primaryKeyColumns($db, $parent);

            self::assertNotSame(
                [],
                $key,
                "{$parent} has no PRIMARY key on this engine, so nothing on it can be referenced by a "
                .'foreign key — the ALTER that created this constraint must have failed.',
            );

            self::assertSame(
                explode(',', $legs),
                $key,
                "{$child} → {$parent} uses legs ({$legs}) but {$parent}'s PRIMARY key is ("
                .implode(',', $key).'). A foreign key must reference the parent\'s own key: legs taken '
                .'from the child\'s key instead write a constraint over a non-unique column set, which '
                .'MySQL refuses with error 6125 and sqlite reports as green forever.',
            );
        }
    }

    /**
     * Every relation this migration owns is actually CONSTRAINED on this engine,
     * with CASCADE, under the suffix the migration declares.
     *
     * Discovered by walking the five roots over the engine's own table list, not
     * from a transcribed child list — a hand-written list would go stale silently
     * the day a table is added.
     */
    public function test_every_family_relation_is_constrained_with_cascade_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();
        $suffix = $this->migrationConstant('CONSTRAINT_SUFFIX');

        self::assertIsString($suffix);

        $owned = [];
        foreach ($this->familyRelations($db) as $relation) {
            $declared = false;
            foreach ($db->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                    continue;
                }
                if (array_map('strval', $foreign['foreign_columns'] ?? []) !== $relation['columns']) {
                    continue;
                }

                $declared = true;
                self::assertSame(
                    'CASCADE',
                    strtoupper((string) ($foreign['on_delete'] ?? '')),
                    "{$relation['child']} → {$relation['parent']} does not CASCADE on this engine.",
                );
                break;
            }

            if ($declared) {
                $owned[] = $relation['child'];

                continue;
            }

            self::fail(
                "{$relation['child']} → {$relation['parent']} [".implode(',', $relation['columns'])
                .'] carries NO foreign key on MySQL. Either the migration did not run on this engine, or '
                .'the constraint was refused at the DDL stage — which is where error 6125, 3780 and 1059 '
                .'appear, and none of them can appear in the sqlite lane.',
            );
        }

        self::assertCount(
            9,
            $owned,
            'the number of relations this family constrains moved. Re-measure and bump this constant together '
            .'with the migration and the sqlite gate — never edit it to reach a green run. Owned: '
            .implode(', ', $owned),
        );
    }

    /**
     * ON DELETE CASCADE reaches the child on the engine that honours it.
     *
     * Read back from `information_schema.REFERENTIAL_CONSTRAINTS` rather than
     * assumed, so a migration that retargeted or dropped the leg fails HERE with
     * the actual delete rule in the message.
     */
    #[DataProvider('shapes')]
    public function test_deleting_the_parent_cascades_to_the_child_on_mysql(
        string $child,
        string $parent,
        string $legs,
    ): void {
        $db = $this->migratedMysqlConnection();

        $this->plantTenant($db);
        $this->plantAncestors($db, $parent);
        $this->assertRowCoversRequiredColumns($db, $parent, self::PARENT_ROWS[$parent]);
        $this->assertRowCoversRequiredColumns($db, $child, self::CHILD_ROWS[$child]);
        $db->table($parent)->insert(self::PARENT_ROWS[$parent]);
        $db->table($child)->insert(self::CHILD_ROWS[$child]);

        self::assertSame(
            1,
            $db->table($child)->count(),
            "the seeded {$child} row is not there, so the cascade below would prove nothing.",
        );

        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, $child, $parent) ?? '',
            "{$child} → {$parent} is not ON DELETE CASCADE on this engine, so deleting the parent cannot "
            .'reach the child and this test would be asserting nothing.',
        );

        $db->table($parent)->where('account_id', self::TENANT)->delete();

        self::assertSame(
            0,
            $db->table($child)->count(),
            "deleting {$parent} must take {$child} with it on MySQL. A cascade proven only on sqlite proves "
            .'the pragma, not the engine.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2+3 — per shape: the orphan is REFUSED with 1452, and the
    //             SAME orphan LANDS with foreign_key_checks = 0
    // ------------------------------------------------------------------

    /**
     * The mandatory pair, run per join shape.
     *
     * Order matters: the CONTROL runs first, at the orphan coordinates with
     * `foreign_key_checks = 0`, and must land. The enforced half then runs the
     * identical row and must raise 1452. Running the control first also means the
     * enforced half measures its own insert alone rather than inheriting a row
     * from the permissive pass — so the control row is deleted explicitly below.
     */
    #[DataProvider('shapes')]
    public function test_an_orphan_is_refused_with_mysql_error_1452(
        string $child,
        string $parent,
        string $legs,
    ): void {
        $db = $this->migratedMysqlConnection();

        // A real parent AND the ancestors the parent itself needs, so the ONLY
        // thing wrong with the orphan is the parent's own coordinates.
        $this->plantTenant($db);
        $this->plantAncestors($db, $parent);
        $this->assertRowCoversRequiredColumns($db, $parent, self::PARENT_ROWS[$parent]);
        $this->assertRowCoversRequiredColumns($db, $child, self::CHILD_ROWS[$child]);
        $db->table($parent)->insert(self::PARENT_ROWS[$parent]);

        $orphan = $this->orphanRow($child, $legs);

        // Enforcement is read back before anything else, so a session that was
        // altered upstream cannot make every assertion below vacuously green.
        //
        // THE ALIAS IS BACKTICKED, and that is a fix rather than a style choice.
        // Unquoted it was `AS on`, and `ON` is a RESERVED WORD in MySQL 8 — the
        // read raised
        //
        //     SQLSTATE[42000]: Syntax error or access violation: 1064 You have an
        //     error in your SQL syntax … near 'on' at line 1
        //     SQL: SELECT @@SESSION.foreign_key_checks AS on)
        //
        // MEASURED on MySQL 8.4.11-0ubuntu0.26.04.1, six times in this file — once
        // per data set of this test plus the withheld-dial control below. It could
        // ONLY ever fail here: sqlite has no `ON` keyword in that position and no
        // `@@SESSION` variable, so this line is invisible to every other lane.
        //
        // The alias is quoted rather than RENAMED so `->on` and the "is OFF"
        // wording below keep reading the way the sentence reads.
        self::assertSame(
            1,
            (int) ($db->selectOne('SELECT @@SESSION.foreign_key_checks AS `on`')->on ?? 0),
            'MySQL foreign_key_checks is OFF on this session. Every 1452 assertion below would be green '
            .'for the wrong reason until that is fixed.',
        );

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table($child)->insert($orphan);
            $landed = $db->table($child)->where($orphan)->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            "with foreign_key_checks = 0 the {$child} orphan MUST land. If it did not, this control half "
            .'is not measuring what it claims and the refusal below proves nothing. Shape: ('.$legs.').',
        );

        $db->table($child)->where($orphan)->delete();

        // ---- THE REAL HALF: enforcement ON, the SAME insert MUST raise. ----
        $thrown = null;
        try {
            $db->table($child)->insert($orphan);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            "the {$child} orphan insert was ACCEPTED with foreign_key_checks = 1 — either this "
            ."migration's constraint to {$parent} over ({$legs}) does not exist on this engine, or the "
            .'engine is not enforcing.',
        );

        // 1452 lives in errorInfo[1], the DRIVER slot. The SQLSTATE slot carries
        // the generic HY000/23000, so asserting that would pass on ANY error.
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table($child)->where($orphan)->count(),
            'the refused orphan insert left a row behind, so the constraint fired late rather than on '
            .'the insert itself.',
        );
    }

    /**
     * THE BITE PROOF, on the target engine: withhold this family's migration from
     * the dial and the orphan must be ADMITTED on MySQL too.
     *
     * This is the check a sqlite-only lane cannot do, and it is what proves the
     * 1452 above is attributable to THIS migration rather than to a constraint
     * some other migration already shipped. For this family it matters more than
     * usual: four of the nine relations have a constraint that a reader of
     * `2026_09_14_200031` would reasonably believe is already there, and
     * `tf_updates_pts_count` genuinely already carries a shipped constraint over
     * the same three columns (to `tf_updates`). Without this test, a green 1452
     * could be that shipped constraint doing the work.
     */
    public function test_the_orphan_is_admitted_on_mysql_without_this_migration(): void
    {
        $db = $this->connectionWithheldOfThisMigration();

        // With the migration absent, `tf_updates_pts_count` is the discriminating
        // case: the ancestor is planted at the ORPHAN coordinates so the shipped
        // `linkChild()` constraint to `tf_updates` is SATISFIED, and only the
        // missing link to `tf_updates_pts` remains. So a row landing here cannot
        // be explained by any other constraint.
        $child = 'tf_updates_pts_count';
        $parent = 'tf_updates_pts';

        $this->plantTenant($db);
        $this->plantAncestors($db, $parent, absent: true);

        $orphan = $this->orphanRow($child, 'account_id,seq,position');
        $this->assertRowCoversRequiredColumns($db, $child, $orphan);
        $db->table($child)->insert($orphan);

        self::assertSame(
            1,
            $db->table($child)->where($orphan)->count(),
            'the orphan must LAND on MySQL without '.self::MIGRATION.'. Its tenant is real and its '
            .'grandparent row is present, so nothing can refuse it — which is what makes the 1452 in '
            .'test_an_orphan_is_refused_with_mysql_error_1452 attributable to this migration.',
        );
    }

    // ------------------------------------------------------------------
    // harness
    // ------------------------------------------------------------------

    /**
     * A migrated MySQL connection with this migration's file REMOVED from the
     * dial, and enforcement read back ON.
     *
     * `MySqlTestCase::migratedMysqlConnection()` memoises the FULL dial per test
     * and drops the database in `setUp()`, so this builds its own rather than
     * reusing that connection: the whole point is a database whose dial differs.
     */
    private function connectionWithheldOfThisMigration(): Connection
    {
        // A DISPOSABLE DATABASE OF ITS OWN, and that is the fix for a 1050 this
        // method used to raise on every run.
        //
        // It used to point at `$cfg['database']` — the database `setUp()` had
        // already migrated with the FULL dial — and then re-ran every migration
        // file against it. The first `Schema::create` to reach a table that
        // already existed raised
        //
        //     SQLSTATE[42S01]: Base table or view already exists: 1050
        //     Table 'tl_user_bindings' already exists
        //
        // MEASURED on MySQL 8.4.11-0ubuntu0.26.04.1. Only ONE 1050 appeared
        // because `tl_user_bindings` is early in the dial: the run died at the
        // first collision and never reached the other 265.
        //
        // The docblock already said why this method cannot reuse
        // `migratedMysqlConnection()` — "the whole point is a database whose dial
        // differs" — and pointing it at the SAME database defeated that: it was
        // not a database whose dial differed, it was the full dial plus a second
        // attempt at it. So it gets its own database, dropped and recreated here
        // so no run can inherit another's schema, and dropped again in
        // `tearDown()` so nothing is left lying around.
        $scratch = $this->scratchDatabaseName();
        $this->dropAndCreateMysqlDatabase($scratch);

        return $this->migrateInto($scratch);
    }

    /**
     * The disposable database the withheld-dial control migrates into.
     *
     * DERIVED from the configured database rather than hardcoded, so the control
     * follows `TELEFRAME_MYSQL_DATABASE` instead of racing another lane for a
     * fixed name, and it is validated by `dropAndCreateMysqlDatabase()` before it
     * ever reaches DDL.
     */
    private function scratchDatabaseName(): string
    {
        return self::mysqlConnectionConfig()['database'].'_withheld';
    }

    /**
     * Drop the withheld-dial control's scratch database.
     *
     * WHY THIS IS NOT A `finally` AROUND THE RETURN. The caller INSERTS ROWS AND
     * ASSERTS on the returned connection, so the database has to outlive the
     * method that built it; a `finally` that dropped it would hand back a
     * connection whose database no longer exists, which fails later and further
     * from its cause. So the lifetime is the TEST METHOD's lifetime, and it is
     * stated here rather than left to chance.
     */
    private function dropDatabase(string $database): void
    {
        try {
            self::mysqlLifecyclePdo()->exec("DROP DATABASE IF EXISTS `{$database}`");
        } catch (\PDOException) {
            // A scratch database that cannot be dropped is litter, not a failed
            // assertion: the next run recreates it. Swallowing it keeps the
            // harness honest about what it is proving.
        }
    }

    protected function tearDown(): void
    {
        $this->dropDatabase($this->scratchDatabaseName());

        parent::tearDown();
    }

    /**
     * Run the dial, minus this family's migration, into the named database.
     *
     * The dial comes from `UpdateIngestor::migrationPaths()` — the same list the
     * ingest seam and the sqlite lane run — with exactly one file filtered out,
     * deduped by real path so the two entity files that live inside the curated
     * directory are still applied once each.
     */
    private function migrateInto(string $database): Connection
    {
        $cfg = self::mysqlConnectionConfig();

        $capsule = new Manager;
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $cfg['host'],
            'port' => $cfg['port'],
            'database' => $database,
            'username' => $cfg['username'],
            'password' => $cfg['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ], 'mysql');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $capsule->getDatabaseManager()->setDefaultConnection('mysql');

        $app = new Container;
        $app->instance('db', $capsule->getDatabaseManager());
        $app->instance('db.schema', $capsule->getConnection('mysql')->getSchemaBuilder());
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        Facade::clearResolvedInstances();

        $files = [];
        $seen = [];
        $withheld = 0;

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $inDir = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];
            sort($inDir);

            foreach ($inDir as $file) {
                $key = realpath($file) ?: $file;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if (str_contains(basename($file), self::MIGRATION)) {
                    $withheld++;

                    continue;
                }

                $files[] = $file;
            }
        }

        self::assertSame(
            1,
            $withheld,
            'the control withholds exactly one file, and it must be '.self::MIGRATION
            .'. If the filter matched nothing, this connection carries the full dial and the control '
            .'below would be testing nothing.',
        );

        foreach ($files as $file) {
            $migration = require $file;
            Assert::assertInstanceOf(
                Migration::class,
                $migration,
                "{$file} did not return a Migration",
            );
            $migration->up();
        }

        $db = $capsule->getConnection('mysql');

        // Backticked for the same reason as the sibling read above: `ON` is a
        // reserved word in MySQL 8 and an unquoted alias raises 1064 there.
        self::assertSame(
            1,
            (int) ($db->selectOne('SELECT @@SESSION.foreign_key_checks AS `on`')->on ?? 0),
            'foreign_key_checks is OFF on the withheld-dial connection, so a row landing there would '
            .'prove only that nothing was checking.',
        );

        return $db;
    }

    /**
     * A private constant READ FROM THE MIGRATION rather than transcribed here.
     */
    private function migrationConstant(string $name): mixed
    {
        $migration = require dirname(__DIR__, 2).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_update_differences_misc_family_foreign_keys.php';

        Assert::assertIsObject($migration, 'the migration file did not return an object');

        return (new \ReflectionClass($migration))->getConstant($name);
    }

    /**
     * The family relations, discovered from the BUILT schema on this engine.
     *
     * The same rule `MirrorRelationPlan` documents, implemented here against
     * `information_schema` so this file does not ask the class under test what
     * to assert. A hand-written child list would be a second copy of the schema,
     * and a second copy drifts.
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
            $present = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $present[(string) $column['name']] = true;
            }
            $columns[$table] = $present;
            $primaryKey[$table] = $this->primaryKeyColumns($db, $table);
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
     * A table's PRIMARY key columns, in KEY ORDER, read off the engine.
     *
     * Order matters: a composite FOREIGN KEY must be declared in the parent's
     * key order, and `information_schema.STATISTICS` reports `SEQ_IN_INDEX` rather
     * than assuming the column order in the table.
     *
     * @return list<string>
     */
    private function primaryKeyColumns(Connection $db, string $table): array
    {
        $rows = $db->select(
            'SELECT COLUMN_NAME AS name
               FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
              ORDER BY SEQ_IN_INDEX',
            [$table, 'PRIMARY'],
        );

        return array_map(static fn ($row): string => (string) ((array) $row)['name'], $rows);
    }

    /**
     * The delete rule MySQL reports for the constraint from `$child` to
     * `$parent`, or null when there is no such constraint.
     */
    private function deleteRule(Connection $db, string $child, string $parent): ?string
    {
        $row = $db->selectOne(
            'SELECT rc.DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = ? AND rc.REFERENCED_TABLE_NAME = ?
              LIMIT 1',
            [$child, $parent],
        );

        return $row === null ? null : (string) ((array) $row)['rule_name'];
    }

    /**
     * Every table name on this connection.
     *
     * @return list<string>
     */
    private function tableNames(Connection $db): array
    {
        $rows = $db->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME',
        );

        return array_map(static fn ($row): string => (string) ((array) $row)['name'], $rows);
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /** Plant the real tenant, so the tenant FK cannot be what refuses an orphan. */
    private function plantTenant(Connection $db): void
    {
        $row = [
            'user_id' => self::TENANT,
            'label' => 'mysql-track-'.self::TENANT,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * Rows a PARENT needs planted before it can be planted itself.
     *
     * `$absent` plants them on the ORPHAN coordinates, which is what isolates the
     * orphan-ness to the one link this migration adds — see the sqlite gate's
     * `plantAncestors()` for why that is load-bearing rather than cosmetic.
     */
    private function plantAncestors(Connection $db, string $parent, bool $absent = false): void
    {
        foreach (self::ANCESTOR_ROWS[$parent] ?? [] as [$table, $row]) {
            $this->assertRowCoversRequiredColumns($db, $table, $row);
            $db->table($table)->insert($absent ? $this->orphanise($parent, $row) : $row);
        }
    }

    /**
     * The witness child row with its leg columns moved onto coordinates no
     * planted parent carries.
     *
     * `account_id` is never rewritten, so the tenant stays real and the tenant
     * constraint cannot be what refuses the row.
     *
     * @return array<string, mixed>
     */
    private function orphanRow(string $child, string $legs): array
    {
        $row = self::CHILD_ROWS[$child];

        // The leg columns are named by the SHAPE rather than re-derived from a
        // list: every leg except `account_id` is the one that must move.
        $moved = 0;
        foreach (explode(',', $legs) as $leg) {
            if ($leg === 'account_id') {
                continue;
            }

            self::assertArrayHasKey(
                $leg,
                $row,
                "the {$child} fixture has no '{$leg}' column, so the orphan cannot be built on shape "
                ."({$legs}). Fix the fixture, not the assertion.",
            );

            $row[$leg] = self::ABSENT[$leg];
            $moved++;
        }

        self::assertGreaterThan(
            0,
            $moved,
            "no leg column moved for {$child} on shape ({$legs}), so the 'orphan' is the row that exists.",
        );

        return $row;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function orphanise(string $parent, array $row): array
    {
        foreach (self::PLANTED[$parent] ?? [] as $column => $plantedValue) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $absent = self::ABSENT[$column];

            // On the value the row will END UP carrying: the templates hold the
            // PLANTED coordinates so one template serves both halves.
            self::assertNotSame(
                $absent,
                $plantedValue,
                "the orphan's {$column} coordinate equals the planted one, so the 'orphan' IS the row "
                .'that exists and its refusal would prove nothing. Fix the fixture, not the assertion.',
            );

            $row[$column] = $absent;
        }

        return $row;
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it
     * names exists, and it supplies every NOT NULL column the engine has no
     * default for.
     *
     * Read off the engine rather than typed from a migration, so this is a
     * statement about the schema that will actually receive the insert. A fixture
     * that happens to agree today would otherwise drift silently the day a column
     * changes, and the suite would keep reporting green.
     *
     * @param  array<string, mixed>  $row
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
            $column = (array) $column;
            $name = (string) $column['name'];
            $present[] = $name;

            $autoIncrement = stripos((string) $column['extra'], 'auto_increment') !== false;
            $hasDefault = $column['def'] !== null;

            if (strtoupper((string) $column['nullable']) === 'NO' && ! $hasDefault && ! $autoIncrement) {
                $required[] = $name;
            }
        }

        foreach (array_keys($row) as $name) {
            self::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — the fixture is written against a "
                .'schema the shipped dial does not produce.',
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
