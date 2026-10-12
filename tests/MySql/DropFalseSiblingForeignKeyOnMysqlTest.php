<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * LANE `SVC-FK` — `2026_10_04_001200_drop_false_sibling_foreign_keys.php` is
 * ACCEPTED by real MySQL/InnoDB, and the acceptance is what re-admits the
 * messages surface.
 *
 * ## ⛔ THIS TEST WAS WRITTEN BUT NOT RUN BY `SVC-FK`
 *
 * Lane `MYSQL-ONE` holds sole MySQL ownership while this change landed, and two
 * MySQL lanes on one server is exactly how a previous wave was stopped. So the
 * assertions here are authored against the shipped harness and the executed
 * dialect, and the RUN is `MYSQL-ONE`'s to make. Nothing in this file's claim of
 * MySQL behaviour is measured by `SVC-FK`, and the docblock says so rather than
 * implying a run that did not happen.
 *
 * ## WHY MYSQL IS THE ENGINE THAT MATTERS FOR THIS MIGRATION
 *
 * `001200` is a DROP, and the two engines do not merely differ in enforcement —
 * they differ in whether the statement exists:
 *
 *  - **sqlite** has no `ALTER TABLE … DROP CONSTRAINT`. Laravel's SQLite grammar
 *    achieves the drop by REBUILDING the table, which is why
 *    `tests/Ingest/ServiceMessageIsNotAChildOfMessagesTest.php` reads the column
 *    list and the primary key back off the engine after the drop instead of
 *    trusting it. It also records no constraint NAMES, so `dropForeign()` by name
 *    refuses outright there ("This database driver does not support dropping
 *    foreign keys by name") — which is why the migration drops by the leg list
 *    on a nameless driver and by the declared name on a named one.
 *  - **MySQL** names constraints, so the migration takes the `dropForeign($name)`
 *    branch, and a name it guessed wrong would compile to
 *    `drop foreign key tf_messages_service_account_id_foreign` and raise
 *    **ERROR 3941** on any install whose constraint was named something else.
 *    That branch is therefore invisible to every SQLite gate in this repo, which
 *    is what makes it worth a MySQL test at all.
 *
 * The SQLite gate proves the drop is CORRECT; only this file proves the
 * name-bearing branch is ACCEPTED and takes the right constraint with it.
 *
 * ## WHAT IT ASSERTS, IN ORDER
 *
 * 1. the dial, `001200` included, installs on InnoDB at all — an `ALTER` MySQL
 *    rejects is a question strictly earlier than "does it enforce", and a gate
 *    that asserted enforcement on a connection whose migration had been skipped
 *    would be proving nothing;
 * 2. `tf_messages_service` carries NO constraint to `tf_messages` — the false
 *    edge is gone on the engine that named it;
 * 3. it still carries the TENANT constraint to `telegram_accounts` — the drop
 *    matched on the parent AND the leg set, not on the table alone;
 * 4. the service surface's OWN children still carry theirs to
 *    `tf_messages_service` — refusing one false edge must not cost six true ones;
 * 5. a real `messageService` row LANDS — the operator's criterion, on the engine
 *    that refused it;
 * 6. THE CONTROL: the same row under a fabricated `account_id` is refused with
 *    **1452**, read from `$e->errorInfo[1]` — the DRIVER slot, never the SQLSTATE,
 *    which is the generic HY000/23000 and would satisfy any assertion about "a
 *    foreign key error". Without this, "it lands now" would also be consistent
 *    with "nothing enforces anything here any more".
 *
 * ## AND THE ROW SHAPES ARE NOT TYPED AGAINST A MIGRATION
 *
 * Every insert is checked against `information_schema.COLUMNS`, read off the
 * engine after the dial ran, and refused unless the payload covers every NOT NULL
 * column with no default and names no column the engine does not have. A fixture
 * that happened to agree today would drift the day a column changes while the
 * suite kept passing.
 */
final class DropFalseSiblingForeignKeyOnMysqlTest extends MySqlTestCase
{
    private const SERVICE = 'tf_messages_service';

    private const MESSAGES = 'tf_messages';

    private const TENANT_TABLE = 'telegram_accounts';

    private const TENANT_ACCOUNT_ID = 7;

    private const FABRICATED_ACCOUNT_ID = 998877;

    private const SERVICE_ID = 4243;

    private const CHANNEL_ID = 1737473577;

    private const USER_ID = 501558149;

    /**
     * THE PREMISE, on the engine that named the constraint: after the dial,
     * `tf_messages_service` does NOT point at `tf_messages`.
     */
    public function test_the_false_sibling_constraint_is_gone_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $declared = $this->constraintsFrom($db, self::SERVICE);

        self::assertNotContains(
            self::MESSAGES,
            $declared,
            self::SERVICE.' still declares a foreign key to '.self::MESSAGES.' on MySQL, so 001200 either did '
            .'not run, took the wrong branch, or the derivation wrote the constraint AFTER it. Declared: '
            .implode(' | ', array_keys($declared)),
        );

        self::assertArrayHasKey(
            self::TENANT_TABLE,
            $declared,
            'the tenant constraint was dropped along with the false one. 001200 matched on the table instead '
            .'of on the parent AND the leg set. Declared: '.implode(' | ', array_keys($declared)),
        );
    }

    /**
     * THE ACCEPTANCE, in the only form that means anything: a real service
     * message lands, on the engine that used to refuse it.
     */
    public function test_a_real_service_message_lands_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();
        $this->seedTenant($db);

        $row = $this->serviceRow(self::TENANT_ACCOUNT_ID);
        $this->assertRowCoversRequiredColumns($db, self::SERVICE, $row);

        $db->table(self::SERVICE)->insert($row);

        self::assertSame(
            1,
            $db->table(self::SERVICE)
                ->where('account_id', self::TENANT_ACCOUNT_ID)
                ->where('id', self::SERVICE_ID)
                ->count(),
            'a real messageService did not land on MySQL. 001200 is the migration that makes it land, so this '
            .'is either a drop that did not happen or a second constraint still refusing it.',
        );
    }

    /**
     * THE CONTROL HALF — the tenant FK is still enforced on this very table and
     * this very insert shape.
     */
    public function test_the_tenant_constraint_still_refuses_an_orphan_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();
        $this->seedTenant($db);

        $thrown = null;

        try {
            $db->table(self::SERVICE)->insert($this->serviceRow(self::FABRICATED_ACCOUNT_ID));
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'a row naming a tenant that does not exist was ACCEPTED on a migrated MySQL connection. 001200 '
            .'removed a constraint it should not have, and the landing asserted elsewhere in this file would '
            .'then prove nothing.',
        );

        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'the fabricated tenant must be refused with MySQL error 1452. Got: '.$thrown->getMessage(),
        );

        self::assertSame(
            0,
            $db->table(self::SERVICE)
                ->where('account_id', self::FABRICATED_ACCOUNT_ID)
                ->count(),
            'the refused row was HELD — a 1452 that leaves the row behind is not a constraint.',
        );
    }

    /**
     * THE SIX TRUE CONSTRAINTS SURVIVE — the half a "drop the whole family" fix
     * gets wrong.
     *
     * Each of these is decomposed from a `tf_messages_service` row the SAME
     * payload writes, so its parent always resolves. Unlinking the family instead
     * of the one false edge would leave six silent gaps on the surface this
     * migration exists to save.
     *
     * The child set is DISCOVERED from `information_schema` by name prefix, never
     * transcribed, so a table added to the surface is covered without an edit.
     */
    public function test_the_service_surfaces_own_children_keep_their_constraint_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $tables = [];
        foreach ($db->select(
            'SELECT TABLE_NAME AS name FROM information_schema.tables
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE ?',
            [self::SERVICE.'\_%'],
        ) as $row) {
            $tables[] = (string) ((array) $row)['name'];
        }

        self::assertNotSame(
            [],
            $tables,
            'no table descends from '.self::SERVICE.' on this engine, so the assertions below are vacuous. '
            .'Either the dial did not install or the discovery is wrong.',
        );

        $unconstrained = [];

        foreach ($tables as $table) {
            if (! array_key_exists(self::SERVICE, $this->constraintsFrom($db, $table))) {
                $unconstrained[] = $table;
            }
        }

        self::assertSame(
            [],
            $unconstrained,
            'these tables descend from '.self::SERVICE.' but carry no constraint to it: '.implode(', ', $unconstrained)
            .'. Their parent row is written by the same payload, so their constraint was TRUE — 001200 removes '
            .'one false edge, not the family.',
        );
    }

    // -----------------------------------------------------------------------

    /**
     * Every constraint declared by `$table`, keyed by the table it targets.
     *
     * Read from the engine through the driver, so this cannot drift from the
     * DDL, and grouped the way `MirrorFactWriter::parentTables()` reads it.
     *
     * @return array<string, list<list<string>>>
     */
    private function constraintsFrom(Connection $db, string $table): array
    {
        $declared = [];

        foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreignKey) {
            $declared[(string) ($foreignKey['foreign_table'] ?? '')][] = array_map(
                'strval',
                (array) ($foreignKey['foreign_columns'] ?? []),
            );
        }

        return $declared;
    }

    private function seedTenant(Connection $db): void
    {
        $row = [
            'user_id' => self::TENANT_ACCOUNT_ID,
            'label' => 'mysql-svc-fk-'.self::TENANT_ACCOUNT_ID,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, self::TENANT_TABLE, $row);

        if ($db->table(self::TENANT_TABLE)->where('user_id', self::TENANT_ACCOUNT_ID)->exists()) {
            return;
        }

        $db->table(self::TENANT_TABLE)->insert($row);
    }

    /**
     * A real `messageService` row — the 13 columns the shipped DDL gives the
     * table, no `message` key, because the column does not exist.
     *
     * @return array<string, mixed>
     */
    private function serviceRow(int $accountId): array
    {
        return [
            'account_id' => $accountId,
            'id' => self::SERVICE_ID,
            'peer_type' => 3,
            'peer_id' => self::CHANNEL_ID,
            'constructor' => 'messageService',
            'date' => 1724852400,
            'out' => true,
            'mentioned' => false,
            'media_unread' => false,
            'reactions_are_possible' => false,
            'silent' => false,
            'post' => false,
            'legacy' => false,
        ];
    }

    /**
     * Refuse to insert a row the ENGINE would reject for a reason this test is
     * not about.
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

            $hasDefault = $column['def'] !== null;
            $autoIncrement = stripos((string) $column['extra'], 'auto_increment') !== false;

            if (strtoupper((string) $column['nullable']) === 'NO' && ! $hasDefault && ! $autoIncrement) {
                $required[] = $name;
            }
        }

        foreach (array_keys($row) as $column) {
            self::assertContains(
                $column,
                $present,
                $table.' has no column named '.$column.' on this engine — this fixture was typed against a '
                .'migration rather than read from the executed DDL, so it is measuring a schema that is not '
                .'the one that shipped.',
            );
        }

        $missing = array_values(array_diff($required, array_keys($row)));
        self::assertSame(
            [],
            $missing,
            $table.' has NOT NULL columns with no default that this fixture does not fill: '.implode(', ', $missing)
            .'. The insert would be refused for a shape reason unrelated to the constraint under test.',
        );
    }
}
