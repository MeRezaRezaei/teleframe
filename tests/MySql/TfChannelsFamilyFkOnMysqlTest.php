<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * The `tf_channels` family FK, on the engine that will actually hold the data.
 *
 * ## WHY THIS FILE IS NOT OPTIONAL
 *
 * SQLite cannot express the four failures that would make this migration
 * unusable in production, and three of them are green under a sqlite-only suite:
 *
 *   - **3780** — referencing and referenced columns differ in SIGNEDNESS.
 *     `tf_channels.id` is a SIGNED `bigInteger` because Telegram channel ids are
 *     negative (`2026_09_14_200003` says so in its own docblock), so this is a
 *     live risk here and not a hypothetical one. SQLite has no signedness.
 *   - **6125** — the referenced column set is not UNIQUE. This family has TWO
 *     children whose own primary key is `(account_id, id, position)` —
 *     `tf_channels_restriction_reason` and `tf_channels_usernames` — so a leg
 *     set taken from the CHILD rather than from the parent would reference a
 *     non-unique set, and SQLite would accept it.
 *   - **1059** — an auto-generated constraint name above 64 characters.
 *   - **1170** — TEXT inside a PRIMARY KEY.
 *
 * `2026_10_04_000830_add_tf_channels_family_foreign_keys.php` declares its
 * longest constraint name as 49 characters and its legs as the parent's own
 * `(account_id, id)`. Both are CLAIMS. This file is what makes them evidence:
 * `migratedMysqlConnection()` executes the shipped dial on real InnoDB, so a
 * 3780 or a 6125 aborts the migration outright and there is no schema to
 * assert about.
 *
 * ## EVERY NEGATIVE ASSERTION HERE SHIPS WITH ITS CONTROL HALF
 *
 * A refusal is green for the wrong reason whenever the session does not enforce.
 * So the SAME orphan row is inserted twice: once under
 * `SET SESSION foreign_key_checks = 0`, where it MUST land, and once with the
 * pragma at its default, where it MUST raise MySQL's own **1452**. A harness
 * that was unconditionally permissive raises in both passes and fails the
 * control half — which is what makes the refusal half evidence.
 *
 * The tenant is REAL in every fixture. Every `tf_*` table already carries
 * `_acc_tenant_fk` to `telegram_accounts.user_id` (D-005), so an orphan built
 * with a fabricated `account_id` would be refused by the TENANT constraint and
 * this file would prove that instead of this family. The orphan-ness lives ONLY
 * in the channel id.
 *
 * ## WHY A PER-LANE DATABASE
 *
 * `MySqlTestCase::setUp()` DROPS and CREATES the configured database. Sibling FK
 * lanes share this worktree and may run `tests/MySql` at the same moment, so
 * this file is documented to be run as:
 *
 *     TELEFRAME_MYSQL=1 TELEFRAME_MYSQL_DATABASE=teleframe_fk_f3_channels \
 *       vendor/bin/phpunit tests/MySql/TfChannelsFamilyFkOnMysqlTest.php
 *
 * which is why the measured numbers in this docblock carry that database name.
 * The scratch user holds `CREATE, DROP ON *.*` precisely so a per-lane database
 * works; never narrow that grant.
 */
final class TfChannelsFamilyFkOnMysqlTest extends MySqlTestCase
{
    /** The constraint suffix this family owns, as the migration spells it. */
    private const CONSTRAINT_SUFFIX = '_ch_parent_fk';

    private const FAMILY_ROOT = 'tf_channels';

    /**
     * The witness child — and the ONLY reason it was chosen.
     *
     * `tf_channels_level` is keyed `(account_id, id)` exactly like its parent and
     * carries exactly ONE payload column, so a row naming an absent channel
     * differs from a legitimate row in the parent coordinate ALONE and in
     * nothing else. MEASURED off `information_schema` on 8.4.11 in this lane:
     * 3 columns, all NOT NULL, no defaults, and exactly one pre-existing
     * FOREIGN KEY — the tenant one.
     */
    private const CHILD = 'tf_channels_level';

    private const CHILD_VALUE = 42;

    /** A real tenant, planted before every fixture. */
    private const TENANT = 1724372757;

    /** The planted channel. Negative, because Telegram channel ids are. */
    private const CHANNEL_ID = -1001234567890;

    /** A channel id no parent row carries — the orphan. */
    private const ABSENT_CHANNEL_ID = -1009999999999;

    /**
     * The whole family, on real InnoDB: valid child lands, orphan refused with
     * 1452 read from the DRIVER slot, and the control half lands.
     */
    public function test_the_channels_family_orphan_is_refused_with_mysql_error_1452(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_CHANNEL_ID));
            $landed = $db->table(self::CHILD)
                ->where('account_id', self::TENANT)
                ->where('id', self::ABSENT_CHANNEL_ID)
                ->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            'with foreign_key_checks = 0 the orphan MUST land. If it did not, this control half is not '
            .'measuring what it claims and the refusal half below proves nothing.',
        );

        // Removed so the enforced half measures the SECOND insert alone.
        $db->table(self::CHILD)->where('account_id', self::TENANT)
            ->where('id', self::ABSENT_CHANNEL_ID)->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_CHANNEL_ID));
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'the orphan insert was ACCEPTED with foreign_key_checks at its default — either '
            .self::MIGRATION_CONSTRAINT_HINT.' does not exist on this engine, or this engine is not '
            .'enforcing. Its tenant IS valid, so the tenant FK cannot be what admitted it.',
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

        self::assertSame(
            0,
            $db->table(self::CHILD)->where('account_id', self::TENANT)
                ->where('id', self::ABSENT_CHANNEL_ID)->count(),
            'the refused orphan insert left a row behind, so the constraint fired late rather than firing',
        );
    }

    /** Named in the failure message above; kept a constant so the two cannot drift. */
    private const MIGRATION_CONSTRAINT_HINT = 'this family\'s '.self::CONSTRAINT_SUFFIX.' constraint';

    /**
     * A legitimate child row LANDS, read back as inserted.
     *
     * A constraint that refuses real engine rows is worse than no constraint: it
     * is an outage, not a safety net. And the read-back matters on MySQL
     * specifically — a non-strict mode would silently coerce a value, and SQLite
     * cannot express that failure at all.
     */
    public function test_a_valid_channels_child_row_lands_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedChannel($db, self::CHANNEL_ID);

        $row = $this->childRow(self::CHANNEL_ID);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)
            ->where('account_id', self::TENANT)
            ->where('id', self::CHANNEL_ID)
            ->first();

        self::assertNotNull(
            $landed,
            'the legitimate row must LAND on this engine. An orphan refused by 1452 proves nothing if the '
            .'matching well-parented row is refused too.',
        );

        self::assertSame(self::CHANNEL_ID, (int) $landed->id, 'the constrained id did not survive the round-trip');
        self::assertSame(self::TENANT, (int) $landed->account_id, 'the constrained account_id did not survive');
        self::assertSame(
            self::CHILD_VALUE,
            (int) $landed->{self::CHILD_VALUE_COLUMN},
            'the payload column did not survive the round-trip',
        );
    }

    /**
     * ON DELETE CASCADE reaches the child, and the rule is READ BACK rather than
     * assumed from the migration text.
     */
    public function test_deleting_the_channel_cascades_to_its_child_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedChannel($db, self::CHANNEL_ID);

        $db->table(self::CHILD)->insert($this->childRow(self::CHANNEL_ID));

        self::assertSame(
            1,
            $db->table(self::CHILD)->where('account_id', self::TENANT)
                ->where('id', self::CHANNEL_ID)->count(),
            'the seeded child row is not there, so the cascade below would prove nothing',
        );

        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::CHILD, self::FAMILY_ROOT),
            self::FAMILY_ROOT.' -> '.self::CHILD.' is not ON DELETE CASCADE on this engine, so deleting the '
            .'parent cannot reach the mirror row and this test would be asserting nothing.',
        );

        $deleted = $db->table(self::FAMILY_ROOT)->where('account_id', self::TENANT)
            ->where('id', self::CHANNEL_ID)->delete();

        self::assertSame(1, $deleted, 'the channel row itself was not deleted');
        self::assertSame(
            0,
            $db->table(self::CHILD)->where('account_id', self::TENANT)
                ->where('id', self::CHANNEL_ID)->count(),
            'deleting the channel must cascade to its children on MySQL. A child row outliving its parent '
            .'fact is the state this whole family constraint exists to prevent.',
        );
    }

    /**
     * Every discovered family relation carries its constraint ON THIS ENGINE,
     * with the exact leg set — not merely "the migrate did not throw".
     *
     * This is the proof that the 6125 hazard did not bite: a leg set taken from
     * the child's own `(account_id, id, position)` key would have been REFUSED by
     * the migrate, and a leg set that somehow got created would show up here as a
     * column-set mismatch. The relation list is read off `information_schema`
     * rather than transcribed.
     */
    public function test_every_discovered_family_relation_carries_its_constraint_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $rows = $db->select(
            'SELECT kcu.TABLE_NAME AS child, kcu.REFERENCED_TABLE_NAME AS parent,
                    kcu.COLUMN_NAME AS leg, kcu.ORDINAL_POSITION AS pos,
                    rc.DELETE_RULE AS rule_name, rc.CONSTRAINT_NAME AS constraint_name
               FROM information_schema.KEY_COLUMN_USAGE kcu
               JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                 ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND rc.TABLE_NAME = kcu.TABLE_NAME
              WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME = ?
              ORDER BY kcu.TABLE_NAME, kcu.ORDINAL_POSITION',
            [self::FAMILY_ROOT],
        );

        self::assertNotEmpty(
            $rows,
            'no constraint on this engine references '.self::FAMILY_ROOT.'. The migration either did not '
            .'run or constrained nothing, and every other assertion here would be measuring an empty set.',
        );

        $byChild = [];
        foreach ($rows as $row) {
            $child = (string) $row->child;
            $byChild[$child]['legs'][] = (string) $row->leg;
            $byChild[$child]['rule'] = strtoupper((string) $row->rule_name);
            $byChild[$child]['name'] = (string) $row->constraint_name;
        }

        // Every relation this family constrains, DISCOVERED — the child tables
        // are the ones carrying a `CONSTRAINT_SUFFIX` constraint name, so no
        // table list is written into this file.
        $owned = array_filter(
            $byChild,
            static fn (array $info): bool => str_ends_with($info['name'], self::CONSTRAINT_SUFFIX),
        );

        self::assertNotSame(
            [],
            $owned,
            'no constraint on this engine carries the suffix '.self::CONSTRAINT_SUFFIX.', so nothing on this '
            .'engine was created by this family\'s migration.',
        );

        foreach ($owned as $child => $info) {
            self::assertSame(
                ['account_id', 'id'],
                $info['legs'],
                "{$child} references ".self::FAMILY_ROOT.' over ['.implode(',', $info['legs']).'] rather than '
                ."the parent's own key. A leg set including a positioned child's own `position` is the shape "
                .'MySQL refuses with 6125, and a leg set that did get created would refuse an ordinary channel '
                .'field vector.',
            );

            self::assertSame(
                'CASCADE',
                $info['rule'],
                "{$child} -> ".self::FAMILY_ROOT.' is not ON DELETE CASCADE on this engine.',
            );
        }
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    private const CHILD_VALUE_COLUMN = 'level';

    /**
     * The tenant row, exactly as the executed `telegram_accounts` requires it.
     *
     * Each payload is checked against the EXECUTED schema before insert, so a
     * fixture that disagrees with the shipped dial fails loudly here instead of
     * silently drifting into a false green.
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

    /**
     * The parent channel row.
     *
     * MEASURED on this engine: `tf_channels` has 34 NOT NULL columns, of which
     * only `account_id`, `id` and `constructor` carry no default — the other 31
     * are flags defaulting to `'0'`. So this payload is the minimum the engine
     * demands, asserted below rather than assumed.
     */
    private function seedChannel(Connection $db, int $channelId): void
    {
        $row = [
            'account_id' => self::TENANT,
            'id' => $channelId,
            'constructor' => 'channel',
        ];

        $this->assertRowCoversRequiredColumns($db, self::FAMILY_ROOT, $row);
        $db->table(self::FAMILY_ROOT)->insert($row);
    }

    /**
     * A `tf_channels_level` row naming `$channelId`. The tenant is always REAL;
     * only the channel id varies between the well-parented fixture and the
     * orphan — which is what makes the two halves the SAME insert.
     */
    private function childRow(int $channelId): array
    {
        return [
            'account_id' => self::TENANT,
            'id' => $channelId,
            self::CHILD_VALUE_COLUMN => self::CHILD_VALUE,
        ];
    }

    /**
     * The delete rule MySQL reports for `$child` → `$parent`, or null when there
     * is no such constraint.
     */
    private function deleteRule(Connection $db, string $child, string $parent): ?string
    {
        $row = $db->selectOne(
            'SELECT rc.DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_NAME = rc.TABLE_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = ?
                AND rc.REFERENCED_TABLE_NAME = ?
              LIMIT 1',
            [$child, $parent],
        );

        return $row === null ? null : strtoupper((string) $row->rule_name);
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it
     * names exists, and it supplies every NOT NULL column the engine has no
     * default for.
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
                "{$table} has no column '{$name}' on this engine — the fixture is written against a schema "
                .'the shipped dial does not produce.',
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
