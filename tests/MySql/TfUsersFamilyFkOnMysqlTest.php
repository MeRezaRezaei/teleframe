<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * The `tf_users` family constraints are ACCEPTED and ENFORCED by real MySQL/InnoDB.
 *
 * ## WHY THIS LANE IS NOT OPTIONAL
 *
 * `2026_10_04_000820_add_tf_users_family_foreign_keys.php` adds nineteen
 * `ALTER TABLE … ADD CONSTRAINT` statements. sqlite can express none of the five
 * ways MySQL rejects one, so a sqlite-only suite reports this migration green in
 * four distinct situations where a real install would have refused to migrate:
 *
 *   - 1452, a child row naming a user that does not exist — ENFORCEMENT, which
 *     sqlite only raises with `PRAGMA foreign_keys` turned on by hand;
 *   - 3780, a referencing and a referenced column of incompatible type or
 *     SIGNEDNESS — sqlite has no signedness, so it cannot raise this at all;
 *   - 6125, a parent key that is not UNIQUE — sqlite accepts the reference;
 *   - 1059, a constraint name over 64 characters — sqlite has no identifier limit
 *     at all, which is exactly how the tenant migration's first spelling shipped
 *     green and was refused on the first real ALTER;
 *   - 1170, TEXT in a PRIMARY KEY.
 *
 * So the SQLite gate in `tests/Schema/Mirror/TfUsersFamilyForeignKeyTest.php`
 * proves the constraints BITE, and this file proves MySQL ACCEPTS the same DDL
 * and enforces it.
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
 * ## THE FIXTURE PLANTS A REAL TENANT, ON PURPOSE
 *
 * Every `tf_*` table in this family already carries `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (`2026_10_04_000700`). A naive orphan — a fabricated
 * `account_id` — would be refused by THAT constraint, and this file would be
 * measuring the tenant migration instead of this one. So the tenant is real and
 * the orphan-ness lives ONLY in the user id.
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
final class TfUsersFamilyFkOnMysqlTest extends MySqlTestCase
{
    /** A `telegram_accounts.user_id` this file plants, so the tenant FK cannot refuse. */
    private const TENANT = 1724372757;

    /** The planted parent user. */
    private const USER = 424242;

    /** A user id that is NEVER planted — the orphan. */
    private const ABSENT_USER = 999999999;

    /**
     * The witness child.
     *
     * `tf_users_lang_code` carries exactly `(account_id, id, lang_code)`, so every
     * column this family's constraint names is visible in the insert and nothing
     * else can satisfy the FK. Chosen by SHAPE, not copied from a list.
     */
    private const CHILD = 'tf_users_lang_code';

    /**
     * A valid child row LANDS, an orphan is refused with 1452, and the control
     * half — the same orphan with `foreign_key_checks = 0` — LANDS.
     */
    public function test_a_valid_child_lands_and_an_orphan_is_refused_with_mysql_error_1452(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedUser($db, self::USER);

        // ---- THE POSITIVE HALF: a well-parented field row MUST land. ----
        $row = $this->childRow(self::USER);
        $this->assertRowCoversRequiredColumns($db, self::CHILD, $row);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->where('id', self::USER)->first();
        self::assertNotNull(
            $landed,
            'the well-parented '.self::CHILD.' row did not LAND. A constraint that refuses real engine rows is '
            .'worse than no constraint: it is an outage, not a safety net.',
        );
        self::assertSame(
            self::TENANT,
            (int) $landed->account_id,
            'the constrained account_id came back as a different value, so the constraint is over another column '
            .'set than the one this fixture built.',
        );
        self::assertSame(self::USER, (int) $landed->id, 'the constrained id must come back as inserted.');

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $orphan = $this->childRow(self::ABSENT_USER);
        $this->assertRowCoversRequiredColumns($db, self::CHILD, $orphan);

        $controlLanded = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table(self::CHILD)->insert($orphan);
            $controlLanded = $db->table(self::CHILD)->where('id', self::ABSENT_USER)->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $controlLanded,
            'with foreign_key_checks = 0 the orphan MUST land. If it did not, this control half is not '
            .'measuring what it claims and the refusal half below proves nothing.',
        );

        // The control row is removed so the enforced half measures the SECOND
        // insert alone and the table is left holding exactly what it should.
        $db->table(self::CHILD)->where('id', self::ABSENT_USER)->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table(self::CHILD)->insert($orphan);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            sprintf(
                'the orphan %s row naming user %d, which does not exist, was ACCEPTED with foreign_key_checks = 1. '
                .'Its tenant IS real, so the tenant FK cannot be what should have refused it — either '
                .'2026_10_04_000820 did not create the constraint, or this engine is not enforcing.',
                self::CHILD,
                self::ABSENT_USER,
            ),
        );

        // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the SQLSTATE
        // slot (which is the generic HY000/23000). Asserting the driver code is what
        // makes this MySQL evidence rather than "some error happened".
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '.$thrown->getMessage()
            .' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table(self::CHILD)->where('id', self::ABSENT_USER)->count(),
            'the refused orphan insert left a row behind, so the constraint fired late rather than not at all.',
        );
    }

    /**
     * ON DELETE CASCADE reaches the child on the engine that honours it.
     *
     * The rule is READ BACK from `information_schema` rather than assumed from the
     * migration text, so a later migration that retargets or drops the leg fails
     * here with the actual delete rule in the message instead of failing as a
     * mystery of zero surviving rows.
     */
    public function test_deleting_the_parent_user_cascades_to_its_field_rows(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedUser($db, self::USER);

        $row = $this->childRow(self::USER);
        $this->assertRowCoversRequiredColumns($db, self::CHILD, $row);
        $db->table(self::CHILD)->insert($row);

        self::assertSame(
            1,
            $db->table(self::CHILD)->where('id', self::USER)->count(),
            'the seeded child row is not there, so the cascade below would prove nothing',
        );

        self::assertSame(
            'CASCADE',
            strtoupper((string) ($this->userParentDeleteRule($db, self::CHILD) ?? '')),
            self::CHILD.' -> tf_users is not ON DELETE CASCADE on this engine, so deleting the user cannot reach '
            .'the field row and this test would be asserting nothing.',
        );

        $deleted = $db->table('tf_users')->where('account_id', self::TENANT)->where('id', self::USER)->delete();

        self::assertSame(1, $deleted, 'the parent user row itself was not deleted');
        self::assertSame(
            0,
            $db->table(self::CHILD)->where('id', self::USER)->count(),
            'deleting the parent user must cascade to its field rows on MySQL. A cascade proven on sqlite proves '
            .'the pragma, not the engine.',
        );
    }

    /**
     * MySQL refuses a constraint whose referencing and referenced columns disagree
     * on type or signedness (error 3780), and sqlite has no signedness to disagree
     * about. So the migration ACCEPTED is itself the assertion: if any of the
     * nineteen ALTERs had mismatched legs, `migratedMysqlConnection()` would have
     * raised before this test could assert anything.
     *
     * What this adds is the COUNT, read back from `information_schema`, so "the
     * migration ran" is not mistaken for "the migration did its work": a dial that
     * silently skipped this family would still migrate cleanly.
     */
    public function test_every_discovered_family_relation_carries_a_constraint_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $constraints = $db->select(
            "SELECT kcu.TABLE_NAME AS child, kcu.REFERENCED_TABLE_NAME AS parent,
                    GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS legs
               FROM information_schema.KEY_COLUMN_USAGE kcu
              WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME = 'tf_users'
              GROUP BY kcu.TABLE_NAME, kcu.REFERENCED_TABLE_NAME",
        );

        self::assertNotSame(
            [],
            $constraints,
            'no constraint on this engine references tf_users, so 2026_10_04_000820 did not run or did nothing. '
            .'The migration being in the dial is not the same as the migration having done its work.',
        );

        // Every table whose name extends the family root that carries NO constraint
        // to it is an unconstrained family member. The list is READ OFF the
        // engine, never transcribed, so a sibling lane landing a new tf_users_*
        // table is caught here rather than being silently skipped.
        //
        // 🔴 SCOPED TO THIS DATABASE, 2026-10-10 — the same defect the sibling
        // `TfChannelParticipantsFamilyFkOnMysqlTest` had, fixed there in this commit.
        // On this engine `getSchemaBuilder()->getTableListing()` returns entries for
        // EVERY schema on the server, schema-qualified (MEASURED on this host:
        // `ai_brain.cache`, `websv_football.matches`, and
        // `/tmp/llp-drill/full/restored.sqlite.cache`, a PATH that merely contains a
        // dot). Stripping everything left of the last dot imports identically-named
        // tables from OTHER databases, while `$constraints` above is correctly
        // restricted by `kcu.CONSTRAINT_SCHEMA = DATABASE()` — so the two halves
        // disagree by construction. `MySqlTestCase::mysqlTableNames()` reads
        // `information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()`, is scoped by
        // construction, returns bare names needing no normalisation, and excludes the
        // harness sentinel tables. Same answer `BackfillCrawlReachesMessageIdOneOnMysqlTest`
        // already adopted; reused so this lane has ONE way of asking the question.
        $family = [];
        foreach ($this->mysqlTableNames() as $bare) {
            if ($bare === 'tf_users' || str_starts_with($bare, 'tf_users_')) {
                $family[$bare] = true;
            }
        }

        $constrained = [];
        foreach ($constraints as $constraint) {
            $constrained[(string) $constraint->child] = true;
        }

        $unconstrained = [];
        foreach (array_keys($family) as $table) {
            if ($table === 'tf_users' || isset($constrained[$table])) {
                continue;
            }
            $unconstrained[] = $table;
        }

        sort($unconstrained);

        self::assertSame(
            [],
            $unconstrained,
            'these tf_users_* tables carry NO foreign key to tf_users on this engine: '.implode(', ', $unconstrained)
            .'. Either the family migration did not run, or a new family member landed without a lane.',
        );
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /**
     * The tenant row, exactly as the executed `telegram_accounts` requires it.
     */
    private function seedTenant(Connection $db, int $userId): void
    {
        $row = [
            'user_id' => $userId,
            'label' => 'mysql-users-family-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /** The parent user row, exactly as the executed `tf_users` requires it. */
    private function seedUser(Connection $db, int $userId): void
    {
        $row = [
            'account_id' => self::TENANT,
            'id' => $userId,
            'constructor' => 'tf_users',
        ];

        $this->assertRowCoversRequiredColumns($db, 'tf_users', $row);
        $db->table('tf_users')->insert($row);
    }

    /**
     * The child field row. The user id is the parameter, so the same builder
     * produces the well-parented row and the orphan — which is what makes the two
     * halves of the first test the SAME insert.
     */
    private function childRow(int $userId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $userId,
            'lang_code' => 'en',
        ];
    }

    /**
     * The delete rule MySQL reports for this family's constraint on `$child`, or
     * null when there is no such constraint.
     */
    private function userParentDeleteRule(Connection $db, string $child): ?string
    {
        $row = $db->selectOne(
            "SELECT rc.DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = ?
                AND rc.REFERENCED_TABLE_NAME = 'tf_users'
              LIMIT 1",
            [$child],
        );

        return $row === null ? null : (string) $row->rule_name;
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
