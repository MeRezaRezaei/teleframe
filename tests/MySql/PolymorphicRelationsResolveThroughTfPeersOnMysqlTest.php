<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Assert;

/**
 * `tf_peers` on the TARGET engine — and the STOP CONDITION, measured on MySQL.
 *
 * ## WHY THIS FILE EXISTS WHEN THE ANSWER IS "NO"
 *
 * The SQLite half (`tests/Schema/Mirror/PolymorphicRelationsResolveThroughTfPeersTest.php`)
 * measures the population count and reports it. SQLite is not the engine that
 * holds the data, and this repo has a standing rule that a claim proven only on
 * SQLite is a claim about SQLite: MySQL/InnoDB raises four errors SQLite cannot
 * raise at all (3780 signedness, 6125 non-unique parent key, 1059 identifier
 * over 64 characters, 1170 TEXT in a PRIMARY KEY). So the two facts that decide
 * this task get re-measured here, on real MySQL 8.4:
 *
 *  1. the registry table's absence is a fact about the ENGINE's catalog, not
 *     about one driver's reflection of it; and
 *  2. the claim that the 8 polymorphic relations need no registry is only worth
 *     anything if MySQL ACCEPTS the registry-free foreign key and REFUSES the
 *     orphan with its own 1452 — not with "some error happened".
 *
 * ## WHAT THIS FILE DELIBERATELY DOES NOT DO
 *
 * It does not create `tf_peers`. It does not create the foreign key. The plan's
 * Step 1 makes the population count a stop condition, and the count is zero, so
 * a green `tf_peers` FK here would be a constraint the database cannot satisfy
 * for any real Telegram row. The assertions below are what make that a
 * MEASUREMENT rather than an assertion in prose.
 *
 * ## AND IT NEVER SKIPS QUIETLY
 *
 * `MySqlTestCase::setUp()` skips only when the track is off AND not forced.
 * Under `TELEFRAME_MYSQL=1` there is no skip path, so an unreachable server is
 * a FAILURE — a track that silently skips is a track nobody reads.
 */
final class PolymorphicRelationsResolveThroughTfPeersOnMysqlTest extends MySqlTestCase
{
    /** No such Telegram account; far outside any plausible real user id. */
    private const ACCOUNT = 31;

    /** A `telegram_accounts.user_id` this file plants before every fixture. */
    private const TENANT = 4242;

    /**
     * THE POPULATION COUNT, read off the ENGINE's own catalog.
     *
     * `information_schema` rather than `hasTable()`, because `hasTable()` is
     * Laravel's reflection and the claim being made is about what MySQL actually
     * holds after executing the shipped dial.
     */
    public function test_the_registry_table_does_not_exist_on_this_engine(): void
    {
        $db = $this->migratedMysqlConnection();

        $found = $db->select(
            "SELECT TABLE_NAME AS name FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tf_peers'",
        );

        fwrite(
            STDERR,
            "\n[tf_peers/MySQL] STEP 1 — tf_peers rows in information_schema: ".count($found)."\n",
        );

        self::assertSame(
            [],
            array_map(static fn (object $r): string => (string) $r->name, $found),
            'tf_peers EXISTS on this engine. If a sibling lane created it, the population count in '
            .'tests/Schema/Mirror/PolymorphicRelationsResolveThroughTfPeersTest.php must be re-run: '
            .'a registry that exists but is never populated is the plan\'s named failure, and it '
            .'turns every real Telegram insert into a foreign-key violation.',
        );

        // Corroborated through a second, independent door. Two reflections
        // agreeing is weak evidence, but ONE reflection reading "no" when the
        // table exists is a false negative that would hide the whole finding.
        self::assertFalse(
            $db->getSchemaBuilder()->hasTable('tf_peers'),
            'information_schema and getSchemaBuilder().hasTable() disagree about tf_peers. One of '
            .'them is wrong about the engine, so neither measurement above can be trusted.',
        );
    }

    /**
     * THE CONTROL HALF for the absence: the dial DID run.
     *
     * A table list that comes back with no `tf_peers` proves nothing if the dial
     * never executed at all — an empty database also has no `tf_peers`. So the
     * engine must be shown to hold the tables it should, and specifically the
     * peer-carrying ones whose FK is being withheld.
     */
    public function test_the_dial_ran_and_the_peer_carrying_tables_are_present(): void
    {
        $tables = $this->mysqlTableNames();

        self::assertNotEmpty($tables, 'the shipped dial created no tables on MySQL, so every absence '
            .'asserted in this file would be true of an empty database');

        foreach (['tf_messages', 'tf_dialogs', 'tf_users', 'tf_chats', 'telegram_accounts'] as $required) {
            self::assertContains(
                $required,
                $tables,
                "the shipped dial did not create {$required} on MySQL, so this suite is measuring an "
                .'empty or half-migrated database',
            );
        }

        // `tf_messages` is the loudest of the 71: it is the table carrying
        // (account_id, peer_type, peer_id) that the plan wants pointed at a
        // registry, so its presence is what makes the registry's absence a
        // finding rather than an artefact.
        self::assertContains(
            'tf_messages',
            $tables,
            'tf_messages is the table the registry would constrain; without it the count above says '
            .'nothing about the 71',
        );
    }

    /**
     * THE CLAIM THAT DECIDES THE TASK, ON MYSQL: the registry-free foreign key
     * applies, and MySQL refuses the orphan with its OWN error 1452.
     *
     * `MirrorRelationPlan::polymorphic()` reports 8 relations excused with
     * "a single FOREIGN KEY cannot branch on a type int; the structural fix is a
     * `tf_peers` registry". MEASURED on SQLite in the sibling half, all 8 have
     * legs IDENTICAL to their parent's primary key, so the FK applies with no
     * registry at all — the parent is one concrete table and `peer_type` is just
     * a column inside its own key.
     *
     * SQLite proving that is not enough, because the four MySQL-specific
     * rejections above are exactly the ones a SQLite-green constraint can still
     * trip on. So this arm runs the real ALTER on real InnoDB and asserts the
     * DRIVER error code, not merely that something raised.
     */
    public function test_a_registry_free_foreign_key_is_accepted_and_refuses_the_orphan_with_1452(): void
    {
        $db = $this->migratedMysqlConnection();
        $schema = $db->getSchemaBuilder();

        $legs = ['account_id', 'peer_type', 'peer_id'];
        $child = 'tf_dialogs_pts';
        $parent = 'tf_dialogs';

        // The parent must really be keyed by exactly these legs, read off the
        // ENGINE. Hard-coding the leg list and asserting against it would prove
        // nothing about which columns the constraint actually uses.
        $parentKey = [];
        foreach ($schema->getIndexes($parent) as $index) {
            if (($index['primary'] ?? false) === true) {
                $parentKey = array_map('strval', $index['columns']);
                break;
            }
        }

        self::assertSame(
            $legs,
            $parentKey,
            $parent.'\'s primary key on MySQL is ['.implode(',', $parentKey).'], not the leg set '
            .'the derived relation carries. The registry-free FK does not apply to this parent, so '
            .'the claim this file is testing no longer holds - re-derive the relation before trusting it.',
        );

        $this->seedTenant($db, self::TENANT);

        // ---- ADD the registry-free FK. MySQL must ACCEPT it. ----
        $schema->table($child, function ($blueprint) use ($legs): void {
            $blueprint->foreign($legs, 'tf_peers_probe_pts_fk')
                ->references($legs)
                ->on('tf_dialogs')
                ->cascadeOnDelete();
        });

        $declared = null;
        foreach ($schema->getForeignKeys($child) as $foreign) {
            if ((string) ($foreign['name'] ?? '') === 'tf_peers_probe_pts_fk') {
                $declared = $foreign;
            }
        }

        Assert::assertNotNull(
            $declared,
            'MySQL accepted the ALTER but reports no constraint by that name, so the refusal half '
            .'below would be proving something else. Re-read the engine\'s own catalog.',
        );

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $orphan = $this->orphanPtsRow();
        $this->assertRowCoversRequiredColumns($db, $child, $orphan);

        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table($child)->insert($orphan);
            $landed = true;
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            'with foreign_key_checks = 0 the orphan MUST land. If it did not, this control is not '
            .'measuring what it claims and the 1452 assertion below proves nothing.',
        );

        $db->table($child)->delete();

        // ---- THE REAL HALF: enforcement ON, the SAME insert MUST raise 1452. ----
        $thrown = null;
        try {
            $db->table($child)->insert($orphan);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            "MySQL accepted a {$child} row naming a {$parent} peer that does not exist, with "
            .'foreign_key_checks = 1 and the constraint just declared. Either the ALTER did not take '
            .'or this engine is not enforcing — and the 1452 assertion below would be vacuous.',
        );

        // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the
        // SQLSTATE slot. Asserting the driver code is what makes this MySQL
        // evidence rather than "some error happened".
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table($child)->count(),
            'the refused orphan insert left a row behind, which means the constraint fired late '
            .'rather than at the statement.',
        );

        // ---- AND the parent DELETE CASCADES, read back from the engine. ----
        // The parent peer row is planted FIRST: without it the legitimate child
        // below would be refused by this very constraint, and the cascade half
        // would be measuring its own fixture's mistake.
        $this->seedParentPeer($db);

        $real = $this->realPtsRow();
        $this->assertRowCoversRequiredColumns($db, $child, $real);
        $db->table($child)->insert($real);

        self::assertSame(
            1,
            $db->table($child)->count(),
            'the legitimate child row did not land, so the cascade below would prove nothing. A '
            .'constraint that refuses real engine rows is worse than no constraint.',
        );

        $deleteRule = $this->deleteRule($db, $child, 'tf_dialogs');
        self::assertSame(
            'CASCADE',
            strtoupper((string) ($deleteRule ?? '')),
            'the constraint does not CASCADE on this engine, so deleting the parent cannot reach the '
            .'child and the cascade this file relies on would be asserted from nothing.',
        );

        $db->table($parent)
            ->where('account_id', self::TENANT)
            ->where('peer_type', 1)
            ->where('peer_id', self::REAL_PEER_ID)
            ->delete();

        self::assertSame(
            0,
            $db->table($child)->count(),
            'deleting the parent peer must take its child row with it (ON DELETE CASCADE) on MySQL. '
            .'A child outliving its parent is the state this constraint exists to prevent.',
        );
    }

    /** A peer id that is never planted. */
    private const ABSENT_PEER_ID = 999999999;

    /** A peer id this file plants in the parent. */
    private const REAL_PEER_ID = 501558149;

    private function seedTenant(Connection $db, int $userId): void
    {
        $row = [
            'user_id' => $userId,
            'label' => 'tf-peers-track-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * The parent peer row, planted so a legitimate child can land.
     *
     * `tf_dialogs` needs `peer_type` AND `peer_id` because that pair IS its
     * primary key — which is the whole point of the relation under test.
     */
    private function seedParentPeer(Connection $db): void
    {
        $row = [
            'account_id' => self::TENANT,
            'peer_type' => 1,
            'peer_id' => self::REAL_PEER_ID,
            'constructor' => 'dialog',
            'top_message' => 1,
            'unread_count' => 0,
        ];

        $this->assertRowCoversRequiredColumns($db, 'tf_dialogs', $row);
        $db->table('tf_dialogs')->insert($row);
    }

    /** A child row whose PARENT PEER does not exist — the orphan. */
    private function orphanPtsRow(): array
    {
        return [
            'account_id' => self::TENANT,
            'peer_type' => 1,
            'peer_id' => self::ABSENT_PEER_ID,
            'pts' => 1,
        ];
    }

    /** The same row, naming a peer that IS planted. */
    private function realPtsRow(): array
    {
        return [
            'account_id' => self::TENANT,
            'peer_type' => 1,
            'peer_id' => self::REAL_PEER_ID,
            'pts' => 1,
        ];
    }

    /**
     * The delete rule MySQL reports for the constraint, read off
     * `information_schema` rather than assumed from the migration text.
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
                AND rc.TABLE_NAME = ?
                AND rc.REFERENCED_TABLE_NAME = ?
              LIMIT 1',
            [$child, $parent],
        );

        return $row === null ? null : (string) $row->rule_name;
    }

    /**
     * Assert the payload is legal against the EXECUTED table.
     *
     * Read off the engine, so a fixture that drifts from the shipped schema
     * fails HERE with a precise message instead of being refused by an unrelated
     * NOT NULL and read as evidence the constraint bit.
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

        Assert::assertNotEmpty($columns, "no columns found for {$table} — the dial did not create it");

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
            Assert::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — the fixture is written against a "
                .'schema the shipped dial does not produce.',
            );
        }

        $missing = array_values(array_diff($required, array_keys($row)));

        Assert::assertSame(
            [],
            $missing,
            "the {$table} insert omits NOT NULL columns with no default: ".implode(', ', $missing),
        );
    }
}
