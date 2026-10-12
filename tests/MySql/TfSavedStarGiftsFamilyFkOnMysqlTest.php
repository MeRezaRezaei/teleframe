<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * `tf_saved_star_gifts` family FKs are ACCEPTED and ENFORCED on real MySQL/InnoDB.
 *
 * ## WHY THIS FILE EXISTS RATHER THAN TRUSTING THE SQLITE GATE
 *
 * sqlite cannot express any of the five ways this migration fails on the engine it
 * will actually run on, and four of them would have shipped green in a sqlite-only
 * suite:
 *
 *   1452 — a child row naming an absent parent is REFUSED
 *   3780 — a constrained leg disagrees with its parent's column type/signedness
 *   6125 — the referenced parent key is not UNIQUE
 *   1059 — a constraint name exceeds the 64-character identifier limit
 *   1170 — TEXT in a PRIMARY KEY
 *
 * **6125 is not hypothetical for this family, and that is why the parent-key
 * resolution is proved on the ENGINE here.** `MirrorRelationPlan` derives each leg
 * set from the parent's PRIMARY KEY intersected with the child's real columns. If
 * it had derived one from a merely same-named column, the `ALTER TABLE` would be
 * refused with 6125 and the family would not land at all.
 *
 * ## ⭐ THE HAZARD THIS FAMILY WAS DISPATCHED FOR, PROVED ON THE ENGINE
 *
 * The plan names `tf_saved_star_gifts_message` and `…_from_id` specifically: their
 * legs must resolve to the gift's OWN primary key, not to a column of the same name.
 *
 * MEASURED on this engine, from `information_schema.COLUMNS` and
 * `information_schema.STATISTICS`, after the dial ran:
 *
 *   tf_saved_star_gifts_message  columns: account_id, id, text
 *   tf_saved_star_gifts_from_id  columns: account_id, id, from_id_type, from_id_id
 *   tf_saved_star_gifts          PRIMARY KEY (account_id, id), NON_UNIQUE = 0
 *
 * Neither hazard table has a column of the same name — the `from_id` value is
 * stored DECOMPOSED as `from_id_type` + `from_id_id`. So the leg can only be `id`,
 * the parent's real key. The adjacent trap, which CAN bite and is asserted below,
 * is that `tf_saved_star_gifts_msg_id.msg_id` and
 * `tf_saved_star_gifts_saved_id.saved_id` DO exist as same-named payload columns
 * and are correctly NOT the leg.
 *
 * The other hazard this engine alone can catch is 3780, and it is live here:
 * `2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php` retyped the sixteen
 * children's key columns to BIGINT and deliberately left the PARENT out of that
 * list. If the parent and child had disagreed on signedness, every one of these 16
 * ALTERs would have been refused. They were not — MEASURED in the test below.
 *
 * ## AND THE CONTROL HALF, WITHOUT WHICH THE REFUSAL PROVES NOTHING
 *
 * The same orphan insert runs twice: once with `SET SESSION foreign_key_checks = 0`,
 * where it MUST land, and once with it at 1, where it MUST raise MySQL's own error
 * 1452 read from `$e->errorInfo[1]` — the DRIVER slot, never the SQLSTATE slot,
 * which is the generic HY000. A harness that is unconditionally permissive raises
 * in both passes and fails the control half, which is what makes the refusal half
 * evidence rather than decoration.
 */
final class TfSavedStarGiftsFamilyFkOnMysqlTest extends MySqlTestCase
{
    /** A tenant no plausible real id collides with, and which this file plants. */
    private const TENANT = 4242;

    /** A gift id this file plants, so the legitimate child row has a parent. */
    private const GIFT = 4242;

    /** A gift id that is NEVER planted — the orphan. */
    private const ABSENT_GIFT = 918273645;

    /** The witness child: three columns, two of them the parent gift's key. */
    private const CHILD = 'tf_saved_star_gifts_message';

    /** The family root — the parent this file's orphans are missing. */
    private const PARENT = 'tf_saved_star_gifts';

    // ------------------------------------------------------------------
    // PROOF 1 — the constraint is ACCEPTED, and over the parent's OWN key
    // ------------------------------------------------------------------

    /**
     * Every family relation's constraint references its parent's ACTUAL PRIMARY KEY
     * columns, in the parent's key order, and that key is UNIQUE.
     *
     * Read from `information_schema.STATISTICS` on the engine, not transcribed. If
     * the leg set were derived from a same-named column instead of the parent's
     * key, this assertion fails — and so would the `ALTER TABLE`, with 6125.
     */
    public function test_every_family_constraint_references_its_parents_own_unique_primary_key(): void
    {
        $db = $this->migratedMysqlConnection();

        $family = $this->familyTables($db);
        self::assertNotEmpty(
            $family,
            'no tf_saved_star_gifts* table exists on this engine, so the dial did not create the family '
            .'and every assertion beneath this one would be vacuous.',
        );

        $checked = 0;
        $problems = [];

        foreach ($family as $child) {
            $parent = $this->parentOf($db, $child);
            if ($parent === null) {
                continue;
            }

            $parentKey = $this->primaryKeyColumns($db, $parent);
            if ($parentKey === []) {
                $problems[] = "{$parent} has no PRIMARY KEY on this engine";

                continue;
            }

            // The parent's key must be UNIQUE, or no FK may reference it at all.
            if (! $this->primaryKeyIsUnique($db, $parent)) {
                $problems[] = "{$parent}'s primary key is NON_UNIQUE, so every constraint referencing it "
                    .'would be refused with MySQL 6125';
            }

            $constraint = $this->constraintTo($db, $child, $parent);
            if ($constraint === null) {
                $problems[] = "{$child} declares no foreign key to {$parent}";

                continue;
            }

            $checked++;

            if ($constraint['referenced'] !== $parentKey) {
                $problems[] = sprintf(
                    '%s references %s on [%s] but that parent\'s key is [%s] — the leg set is not the '
                    .'parent\'s own key.',
                    $child,
                    $parent,
                    implode(',', $constraint['referenced']),
                    implode(',', $parentKey),
                );
            }

            if (strtoupper($constraint['delete_rule']) !== 'CASCADE') {
                $problems[] = "{$child} → {$parent} is ON DELETE {$constraint['delete_rule']}, not CASCADE";
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no constraint was inspected, so this proof is vacuous — the dial created the family tables but '
            .'none of them carries the constraint this migration adds.',
        );

        self::assertSame([], $problems, implode("\n", $problems));
    }

    // ------------------------------------------------------------------
    // PROOF 2 — a valid child row LANDS
    // ------------------------------------------------------------------

    public function test_a_valid_child_row_lands_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedGift($db, self::GIFT);

        $row = $this->childRow(self::GIFT);
        $db->table(self::CHILD)->insert($row);

        $landed = $db->table(self::CHILD)->where('account_id', self::TENANT)->first();

        self::assertNotNull(
            $landed,
            'the legitimate child row did not LAND. A constraint that refuses real engine rows is worse '
            .'than no constraint: it is an outage, not a safety net.',
        );
        self::assertSame(self::GIFT, (int) $landed->id);
        self::assertSame('probe', (string) $landed->text);
    }

    // ------------------------------------------------------------------
    // PROOF 3 — THE ORPHAN IS REFUSED WITH 1452, AND THE CONTROL HALF LANDS
    // ------------------------------------------------------------------

    public function test_an_orphan_is_refused_with_mysql_error_1452_and_the_control_half_lands(): void
    {
        $db = $this->migratedMysqlConnection();

        // A real tenant AND a real sibling gift, so the ONLY thing wrong with the
        // orphan is the gift id it names. A fabricated tenant would be refused by
        // the tenant FK instead, and this test would prove that rather than this
        // migration.
        $this->seedTenant($db, self::TENANT);
        $this->seedGift($db, self::GIFT);

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_GIFT));
            $landed = $db->table(self::CHILD)
                ->where('account_id', self::TENANT)
                ->where('id', self::ABSENT_GIFT)
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
        $db->table(self::CHILD)
            ->where('account_id', self::TENANT)
            ->where('id', self::ABSENT_GIFT)
            ->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table(self::CHILD)->insert($this->childRow(self::ABSENT_GIFT));
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'the orphan insert was ACCEPTED with foreign_key_checks = 1 — either this migration\'s '
            .'constraint on '.self::CHILD.' does not exist on this engine, or the engine is not enforcing.',
        );

        // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the
        // SQLSTATE slot (which is the generic HY000). Asserting the driver code is
        // what makes this MySQL evidence rather than "some error happened".
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table(self::CHILD)
                ->where('account_id', self::TENANT)
                ->where('id', self::ABSENT_GIFT)
                ->count(),
            'the refused orphan insert left a row behind, so the constraint fired late rather than firing.',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 4 — ON DELETE CASCADE on the engine that honours it
    // ------------------------------------------------------------------

    public function test_deleting_the_parent_gift_cascades_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedGift($db, self::GIFT);
        $db->table(self::CHILD)->insert($this->childRow(self::GIFT));

        self::assertSame(
            1,
            $db->table(self::CHILD)->where('account_id', self::TENANT)->count(),
            'the seeded child row is not there, so the cascade below would prove nothing.',
        );

        $constraint = $this->constraintTo($db, self::CHILD, self::PARENT);
        self::assertNotNull($constraint, self::CHILD.' declares no foreign key to '.self::PARENT.' at all.');
        self::assertSame(
            'CASCADE',
            strtoupper((string) $constraint['delete_rule']),
            'the constraint exists but is not ON DELETE CASCADE on this engine, so deleting the gift '
            .'cannot reach the child and this test would be asserting nothing.',
        );

        $deleted = $db->table(self::PARENT)
            ->where('account_id', self::TENANT)
            ->where('id', self::GIFT)
            ->delete();
        self::assertSame(1, $deleted, 'the gift row itself was not deleted');

        self::assertSame(
            0,
            $db->table(self::CHILD)->where('account_id', self::TENANT)->count(),
            'deleting the parent gift must cascade to its children on MySQL. A child row outliving its '
            .'parent fact is the state this whole family constraint exists to prevent.',
        );
    }

    // ------------------------------------------------------------------
    // helpers — every one of them reads the ENGINE, never a transcribed list
    // ------------------------------------------------------------------

    /**
     * Every table on this engine whose name extends the family root.
     *
     * @return list<string>
     */
    private function familyTables(Connection $db): array
    {
        $rows = $db->select(
            "SELECT TABLE_NAME AS name FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'tf\\_saved\\_star\\_gifts%'",
        );

        return array_map(static fn (object $r): string => (string) $r->name, $rows);
    }

    /**
     * The single-parent ancestor of `$child` inside this family, read from the
     * executed schema by the same longest-prefix ancestry rule the migration uses.
     */
    private function parentOf(Connection $db, string $child): ?string
    {
        $family = array_flip($this->familyTables($db));

        $parts = explode('_', $child);
        for ($i = count($parts) - 1; $i >= 1; $i--) {
            $candidate = implode('_', array_slice($parts, 0, $i));
            if (! isset($family[$candidate])) {
                continue;
            }

            $childKey = $this->primaryKeyColumns($db, $child);
            $parentKey = $this->primaryKeyColumns($db, $candidate);

            if ($parentKey === [] || array_diff($parentKey, $childKey) !== []) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /**
     * The PRIMARY key columns of `$table` on this engine, in key order.
     *
     * @return list<string>
     */
    private function primaryKeyColumns(Connection $db, string $table): array
    {
        $rows = $db->select(
            'SELECT COLUMN_NAME AS name FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
              ORDER BY SEQ_IN_INDEX',
            [$table, 'PRIMARY'],
        );

        return array_map(static fn (object $r): string => (string) $r->name, $rows);
    }

    /** Whether `$table`'s PRIMARY index is UNIQUE on this engine. */
    private function primaryKeyIsUnique(Connection $db, string $table): bool
    {
        $row = $db->selectOne(
            'SELECT NON_UNIQUE AS non_unique FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
              ORDER BY SEQ_IN_INDEX LIMIT 1',
            [$table, 'PRIMARY'],
        );

        return $row !== null && (int) $row->non_unique === 0;
    }

    /**
     * The one constraint from `$child` to `$parent`, with its REFERENCED columns
     * and delete rule, or null when there is none.
     *
     * `REFERENCED_COLUMN_NAME` is what the FK points AT on the parent, read from
     * `information_schema.KEY_COLUMN_USAGE` — which is the question this family's
     * hazard asks: does the constraint reference the parent's KEY, or something
     * that merely shares a name?
     *
     * @return array{referenced:list<string>, delete_rule:string}|null
     */
    private function constraintTo(Connection $db, string $child, string $parent): ?array
    {
        $rows = $db->select(
            'SELECT kcu.REFERENCED_COLUMN_NAME AS referenced, rc.DELETE_RULE AS delete_rule
               FROM information_schema.KEY_COLUMN_USAGE kcu
               JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                 ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                AND rc.TABLE_NAME = kcu.TABLE_NAME
              WHERE kcu.TABLE_SCHEMA = DATABASE()
                AND kcu.TABLE_NAME = ?
                AND kcu.REFERENCED_TABLE_NAME = ?
              ORDER BY kcu.ORDINAL_POSITION',
            [$child, $parent],
        );

        if ($rows === []) {
            return null;
        }

        return [
            'referenced' => array_map(static fn (object $r): string => (string) $r->referenced, $rows),
            'delete_rule' => (string) $rows[0]->delete_rule,
        ];
    }

    // ------------------------------------------------------------------
    // fixtures — payloads checked against the EXECUTED schema
    // ------------------------------------------------------------------

    /**
     * A `telegram_accounts` row, exactly as the executed table requires it.
     *
     * MEASURED on this engine: `label` and `type` are NOT NULL with no default;
     * `dc_id` has a default and `id` auto-increments, so both are left out.
     */
    private function seedTenant(Connection $db, int $userId): void
    {
        $row = ['user_id' => $userId, 'label' => 'ssg-'.$userId, 'type' => 'user'];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * A `tf_saved_star_gifts` row — the parent the orphan is missing.
     *
     * MEASURED on this engine: `account_id`, `id` and `date` are the NOT NULL
     * columns with no default; the seven flags all default to 0.
     */
    private function seedGift(Connection $db, int $giftId): void
    {
        $row = ['account_id' => self::TENANT, 'id' => $giftId, 'date' => 1700000000];

        $this->assertRowCoversRequiredColumns($db, self::PARENT, $row);
        $db->table(self::PARENT)->insert($row);
    }

    /**
     * A `tf_saved_star_gifts_message` row. Only `$giftId` varies, so the orphan and
     * the legitimate row are the SAME insert with one value changed.
     *
     * @return array<string, int|string>
     */
    private function childRow(int $giftId): array
    {
        return ['account_id' => self::TENANT, 'id' => $giftId, 'text' => 'probe'];
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it names
     * exists, and it supplies every NOT NULL column the engine has no default for.
     *
     * Read off the engine rather than typed from the migration, so this is a
     * statement about the schema that will actually receive the insert.
     *
     * @param  array<string, int|string>  $row
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
