<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * FK enforcement on real MySQL/InnoDB, WITH A CONTROL HALF.
 *
 * A negative assertion — "this orphan insert is refused" — is green for the
 * wrong reason whenever the session does not enforce. So this suite runs the
 * SAME orphan insert twice: once with `foreign_key_checks = 0`, where it MUST
 * LAND, and once with it at 1, where it MUST RAISE with MySQL's own error 1452.
 * A harness that is unconditionally permissive raises in both passes and fails
 * the control half, which is what makes the refusal half evidence.
 *
 * ## WHY THE ROW SHAPES ARE NOT TYPED HERE
 *
 * The plan's version of this test hand-writes the insert payloads. That is the
 * exact failure mode `RunsMysqlMigrations` was written against: a fixture that
 * disagrees with the shipped schema fails loudly here, but a fixture that
 * happens to agree today silently drifts the day a column changes, and the
 * suite keeps reporting green. So every insert below is checked against the
 * EXECUTED schema — `information_schema.COLUMNS` read off the engine after the
 * dial ran — and the test refuses to proceed unless its row covers every NOT
 * NULL column that has no default and no auto-increment. A payload that is a
 * strict subset is still allowed; a payload naming a column the engine does not
 * have is not.
 */
final class ForeignKeysAreEnforcedOnMysqlTest extends MySqlTestCase
{
    /** No such Telegram user; chosen far outside any plausible real id. */
    private const ORPHAN_ACCOUNT_ID = 999999;

    private const TENANT_ACCOUNT_ID = 4242;

    /**
     * The same orphan row, twice: once permissive (must land), once enforced
     * (must raise 1452).
     */
    public function test_an_orphan_row_is_refused_with_mysql_error_1452(): void
    {
        $db = $this->migratedMysqlConnection();

        // A real parent, so the ONLY thing wrong with the child is its tenant.
        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table('tf_messages')->insert($this->orphanRow(messageId: 1));
            $landed = $db->table('tf_messages')
                ->where('account_id', self::ORPHAN_ACCOUNT_ID)
                ->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            'with foreign_key_checks = 0 the orphan MUST land. If it did not, this control half '
            .'is not measuring what it claims and the refusal half below proves nothing.',
        );

        // The control row is removed so the enforced half measures the SECOND
        // insert alone and the table is left holding exactly what it should.
        $db->table('tf_messages')->where('account_id', self::ORPHAN_ACCOUNT_ID)->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table('tf_messages')->insert($this->orphanRow(messageId: 2));
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'the orphan insert was ACCEPTED with foreign_key_checks = 1 — either the tenant FK on '
            .'tf_messages.account_id does not exist, or this engine is not enforcing.',
        );

        // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the
        // SQLSTATE slot (which is the generic HY000/23000). Asserting the driver
        // code is what makes this MySQL evidence rather than "some error happened".
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        // And the row must be absent afterwards — a raised insert that left a
        // row behind would mean the constraint fired late, not that it fired.
        self::assertSame(
            0,
            $db->table('tf_messages')->where('account_id', self::ORPHAN_ACCOUNT_ID)->count(),
            'the refused orphan insert left a row behind',
        );
    }

    /**
     * ON DELETE CASCADE, on the engine that honours it.
     *
     * The constraint is read back from `information_schema` rather than assumed
     * from the migration text, so a later migration that retargets or drops the
     * leg fails HERE with the actual delete rule in the message instead of
     * failing as a mystery of zero surviving rows.
     */
    public function test_a_cascade_delete_reaches_the_child_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);

        $row = $this->mirrorRow(self::TENANT_ACCOUNT_ID, messageId: 7, message: 'cascade me');
        $db->table('tf_messages')->insert($row);

        self::assertSame(
            1,
            $db->table('tf_messages')->where('account_id', self::TENANT_ACCOUNT_ID)->count(),
            'the seeded child row is not there, so the cascade below would prove nothing',
        );

        self::assertSame(
            'CASCADE',
            strtoupper((string) ($this->tenantDeleteRule($db) ?? '')),
            'telegram_accounts -> tf_messages is not ON DELETE CASCADE on this engine, so deleting '
            .'the tenant cannot reach the mirror row and this test would be asserting nothing.',
        );

        $deleted = $db->table('telegram_accounts')->where('user_id', self::TENANT_ACCOUNT_ID)->delete();

        self::assertSame(1, $deleted, 'the tenant row itself was not deleted');
        self::assertSame(
            0,
            $db->table('tf_messages')->where('account_id', self::TENANT_ACCOUNT_ID)->count(),
            'deleting the tenant must cascade to its mirror rows on MySQL. ON DELETE CASCADE is '
            .'declared in 2026_10_04_000700 and a cascade test on SQLite proves the pragma, not the '
            .'engine.',
        );
    }

    /**
     * The tenant row, exactly as the executed `telegram_accounts` requires it.
     *
     * `app_id` is left out deliberately: it is nullable, and every other NOT
     * NULL column without a default is supplied.
     */
    private function seedTenant(Connection $db, int $userId): void
    {
        $row = [
            'user_id' => $userId,
            'label' => 'mysql-track-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /** The orphan row: a fabricated tenant and nothing else wrong with it. */
    private function orphanRow(int $messageId): array
    {
        return $this->mirrorRow(self::ORPHAN_ACCOUNT_ID, $messageId, 'orphan');
    }

    /**
     * A `tf_messages` row. `account_id` is the parameter, so the same builder
     * produces the orphan and the well-parented row — which is what makes the
     * two halves of the first test the SAME insert.
     */
    private function mirrorRow(int $accountId, int $messageId, string $message): array
    {
        return [
            'account_id' => $accountId,
            'id' => $messageId,
            'peer_type' => 1,
            'peer_id' => 1,
            'constructor' => 'message',
            'date' => 1700000000,
            'message' => $message,
        ];
    }

    /**
     * The delete rule MySQL reports for the tenant constraint on `tf_messages`,
     * or null when there is no such constraint.
     */
    private function tenantDeleteRule(Connection $db): ?string
    {
        $row = $db->selectOne(
            "SELECT rc.DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = 'tf_messages'
                AND rc.REFERENCED_TABLE_NAME = 'telegram_accounts'
                AND kcu.COLUMN_NAME = 'account_id'
              LIMIT 1",
        );

        return $row === null ? null : (string) $row->rule_name;
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it
     * names exists, and it supplies every NOT NULL column the engine has no
     * default for.
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
