<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `tf_channel_participants` + `tf_channel_updates` constraints are ACCEPTED
 * and ENFORCED by real MySQL/InnoDB.
 *
 * ## WHY THIS LANE IS NOT OPTIONAL FOR THIS FAMILY SPECIFICALLY
 *
 * `2026_10_04_000860_add_tf_channel_participants_family_foreign_keys.php` adds
 * nine `ALTER TABLE … ADD CONSTRAINT` statements, every one of them a THREE-COLUMN
 * composite. A composite leg is where MySQL's acceptance failures concentrate, and
 * this family's shape is the exact one the plan flagged:
 *
 *   - **6125**, "Failed to add the foreign key constraint" because the parent key
 *     is not UNIQUE. This is THE live risk for a three-column leg: the parent must
 *     carry a UNIQUE index over exactly `(account_id, channel_id, user_id)` /
 *     `(account_id, channel_id, position)`. The SQLite gate proves the legs are
 *     *derived*; only this engine proves they are *acceptable*.
 *   - **3780**, a referencing and a referenced column of incompatible type or
 *     SIGNEDNESS. `account_id` is `unsignedBigInteger` on both sides, but the
 *     composite also carries `channel_id` (signed), `user_id` (signed) and
 *     `position` (`smallInteger`), and sqlite has no signedness to disagree about.
 *   - **1059**, a constraint name over 64 characters. This family's longest table
 *     is `tf_channel_participants_subscription_until_date` at 47 characters, so
 *     only 17 characters remain for a suffix and sqlite — which has NO identifier
 *     limit at all — cannot see an overrun coming.
 *   - **1170**, TEXT in a PRIMARY KEY.
 *   - **1452**, the enforcement refusal itself, which sqlite only raises with
 *     `PRAGMA foreign_keys` turned on by hand.
 *
 * So the SQLite gate in `tests/Schema/Mirror/TfChannelParticipantsFamilyForeignKeyTest.php`
 * proves the constraints BITE, and this file proves MySQL ACCEPTS the same DDL and
 * enforces it.
 *
 * ## THE CONTROL HALF IS THE POINT OF THE FIRST TEST
 *
 * "The orphan insert was refused" is green for the wrong reason whenever the
 * session does not enforce. So the SAME orphan row is inserted twice: once with
 * `SET SESSION foreign_key_checks = 0`, where it MUST LAND, and once with it at 1,
 * where it MUST RAISE with MySQL's own error 1452 read from `$e->errorInfo[1]`
 * (the DRIVER slot — never the SQLSTATE, which is the generic HY000/23000 and
 * would satisfy any assertion about "a foreign key error"). A harness that is
 * unconditionally permissive raises in both passes and fails the control half,
 * which is what makes the refusal half evidence.
 *
 * ## BOTH JOIN SHAPES ARE PROVEN, NOT ONE
 *
 * This family has two roots and two different three-column leg sets. A MySQL gate
 * that exercised only one of them would leave the other's 6125/3780 risk entirely
 * unmeasured — which is the whole reason the family exists as a task. So both
 * witnesses run the full land / control / refuse / cascade sequence:
 *
 *   tf_channel_participants_rank   (account_id, channel_id, user_id)   → tf_channel_participants
 *   tf_channel_updates_dialog     (account_id, channel_id, position)  → tf_channel_updates
 *
 * ## THE FIXTURE PLANTS A REAL TENANT AND A REAL CHANNEL, ON PURPOSE
 *
 * Every `tf_*` table here already carries `_acc_tenant_fk` to
 * `telegram_accounts.user_id`, AND `tf_channel_participants` carries
 * `RESTRICT ON DELETE (account_id, channel_id) → tf_channels` from
 * `2026_09_14_299999`. A naive orphan — a fabricated `account_id` or a channel
 * that does not exist — would be refused by one of THOSE, and this file would be
 * measuring the wrong migration. So the tenant is real, the channel is real, and
 * the orphan-ness lives only in the third column of the leg.
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
final class TfChannelParticipantsFamilyFkOnMysqlTest extends MySqlTestCase
{
    /** A `telegram_accounts.user_id` this file plants, so the tenant FK cannot refuse. */
    private const TENANT = 1724372757;

    /** A channel this file plants, so the `tf_channels` RESTRICT cannot refuse. */
    private const CHANNEL = 777000111;

    /**
     * The witness child for the `(account_id, channel_id, user_id)` shape.
     *
     * `tf_channel_participants_rank` carries exactly `(account_id, channel_id,
     * user_id, rank)` — three legs plus a TEXT payload — so every column this
     * family's constraint names is visible in the insert and nothing else can
     * satisfy the FK. Chosen by SHAPE, not copied from a list.
     */
    private const CHILD_PARTICIPANT = 'tf_channel_participants_rank';

    /** The witness child for the `(account_id, channel_id, position)` shape. */
    private const CHILD_UPDATE = 'tf_channel_updates_dialog';

    /** The planted parent participant. */
    private const PARTICIPANT = 5150;

    /** A participant that is NEVER planted — the orphan on the participant shape. */
    private const ABSENT_PARTICIPANT = 999999001;

    /** The planted parent channel-diff row. */
    private const POSITION = 4;

    /** A position that is NEVER planted — the orphan on the channel-diff shape. */
    private const ABSENT_POSITION = 4242;

    /**
     * A valid child row LANDS, an orphan is refused with 1452, and the control
     * half — the same orphan with `foreign_key_checks = 0` — LANDS. Run for BOTH
     * join shapes.
     *
     * @return iterable<string, array{0:string, 1:callable(Connection, int): array<string, mixed>, 2:int, 3:string, 4:string}>
     */
    public static function shapes(): iterable
    {
        yield 'participant shape (account_id,channel_id,user_id)' => [
            self::CHILD_PARTICIPANT,
            static fn (Connection $db, int $absent): array => [
                'account_id' => self::TENANT,
                'channel_id' => self::CHANNEL,
                'user_id' => $absent,
                'rank' => 'moderator',
            ],
            self::ABSENT_PARTICIPANT,
            'user_id',
            'tf_channel_participants',
        ];

        yield 'channel-diff shape (account_id,channel_id,position)' => [
            self::CHILD_UPDATE,
            static fn (Connection $db, int $absent): array => [
                'account_id' => self::TENANT,
                'channel_id' => self::CHANNEL,
                'position' => $absent,
                'peer_type' => 0,
                'peer_id' => 4242,
                'top_message' => 31,
            ],
            self::ABSENT_POSITION,
            'position',
            'tf_channel_updates',
        ];
    }

    #[DataProvider('shapes')]
    public function test_a_valid_child_lands_and_an_orphan_is_refused_with_mysql_error_1452(
        string $child,
        callable $rowFor,
        int $absent,
        string $absentLeg,
        string $parent,
    ): void {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedChannel($db, self::CHANNEL);
        $this->seedParent($db, $parent, $parent === 'tf_channel_participants' ? self::PARTICIPANT : self::POSITION);

        // ---- THE POSITIVE HALF: a well-parented facet MUST land. ----
        $present = $parent === 'tf_channel_participants' ? self::PARTICIPANT : self::POSITION;
        $row = $rowFor($db, $present);
        $this->assertRowCoversRequiredColumns($db, $child, $row);
        $db->table($child)->insert($row);

        $landed = $db->table($child)
            ->where('account_id', self::TENANT)
            ->where('channel_id', self::CHANNEL)
            ->where($absentLeg, $present)
            ->first();

        self::assertNotNull(
            $landed,
            "the well-parented {$child} row did not LAND. A constraint that refuses real engine rows is worse "
            .'than no constraint: it is an outage, not a safety net.',
        );

        foreach (['account_id', 'channel_id', $absentLeg] as $leg) {
            self::assertSame(
                (int) $row[$leg],
                (int) $landed->{$leg},
                "the constrained leg {$leg} of {$child} came back as a different value, so the constraint is "
                .'over another column set than the one this fixture built.',
            );
        }

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $orphan = $rowFor($db, $absent);
        $this->assertRowCoversRequiredColumns($db, $child, $orphan);

        $controlLanded = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table($child)->insert($orphan);
            $controlLanded = $db->table($child)
                ->where('account_id', self::TENANT)
                ->where('channel_id', self::CHANNEL)
                ->where($absentLeg, $absent)
                ->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $controlLanded,
            "with foreign_key_checks = 0 the {$child} orphan MUST land. If it did not, this control half is "
            .'not measuring what it claims and the refusal half below proves nothing.',
        );

        // The control row is removed so the enforced half measures the SECOND
        // insert alone and the table is left holding exactly what it should.
        $db->table($child)
            ->where('account_id', self::TENANT)
            ->where('channel_id', self::CHANNEL)
            ->where($absentLeg, $absent)
            ->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table($child)->insert($orphan);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            "the {$child} row naming {$absentLeg}={$absent}, which does not exist, was ACCEPTED with "
            .'foreign_key_checks = 1. Its tenant AND its channel are real, so neither the tenant FK nor the '
            .'tf_channels RESTRICT can be what should have refused it — either '
            .'2026_10_04_000860 did not create the constraint, or this engine is not enforcing.',
        );

        // MySQL reports 1452 in errorInfo[1] — the DRIVER slot — never in the
        // SQLSTATE slot (which is the generic HY000/23000). Asserting the driver
        // code is what makes this MySQL evidence rather than "some error happened".
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            "expected MySQL error 1452 (cannot add or update a child row) on {$child}; got: "
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table($child)
                ->where('account_id', self::TENANT)
                ->where('channel_id', self::CHANNEL)
                ->where($absentLeg, $absent)
                ->count(),
            "the refused orphan insert into {$child} left a row behind, so the constraint fired late rather "
            .'than not at all.',
        );
    }

    /**
     * ON DELETE CASCADE reaches the child on the engine that honours it.
     *
     * The rule is READ BACK from `information_schema` rather than assumed from the
     * migration text, so a later migration that retargets or drops the leg fails
     * here with the actual delete rule in the message instead of failing as a
     * mystery of zero surviving rows.
     *
     * @param  callable(Connection, int): array<string, mixed>  $rowFor
     */
    #[DataProvider('shapes')]
    public function test_deleting_the_parent_cascades_to_its_facets(
        string $child,
        callable $rowFor,
        int $absent,
        string $absentLeg,
        string $parent,
    ): void {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT);
        $this->seedChannel($db, self::CHANNEL);

        $present = $parent === 'tf_channel_participants' ? self::PARTICIPANT : self::POSITION;
        $this->seedParent($db, $parent, $present);

        $row = $rowFor($db, $present);
        $this->assertRowCoversRequiredColumns($db, $child, $row);
        $db->table($child)->insert($row);

        self::assertSame(
            1,
            $db->table($child)
                ->where('account_id', self::TENANT)
                ->where('channel_id', self::CHANNEL)
                ->where($absentLeg, $present)
                ->count(),
            "the seeded {$child} row is not there, so the cascade below would prove nothing",
        );

        self::assertSame(
            'CASCADE',
            strtoupper((string) ($this->parentDeleteRule($db, $child, $parent) ?? '')),
            "{$child} → {$parent} is not ON DELETE CASCADE on this engine, so deleting the parent cannot reach "
            .'the facet row and this test would be asserting nothing.',
        );

        $query = $db->table($parent)
            ->where('account_id', self::TENANT)
            ->where('channel_id', self::CHANNEL);

        $deleted = $parent === 'tf_channel_participants'
            ? $query->where('user_id', $present)->delete()
            : $query->where('position', $present)->delete();

        self::assertSame(1, $deleted, "the parent {$parent} row itself was not deleted");
        self::assertSame(
            0,
            $db->table($child)
                ->where('account_id', self::TENANT)
                ->where('channel_id', self::CHANNEL)
                ->where($absentLeg, $present)
                ->count(),
            "deleting the parent {$parent} must cascade to its facets on MySQL. A cascade proven on sqlite "
            .'proves the pragma, not the engine.',
        );
    }

    /**
     * The COUNT, read back from `information_schema`, over BOTH roots.
     *
     * MySQL refuses an incompatible leg with 3780/6125 and an over-long name with
     * 1059 — so if any of the nine ALTERs had been wrong, `migratedMysqlConnection()`
     * would already have raised and this file could assert nothing at all. That is
     * the ACCEPTANCE half, and it is already proven by getting here.
     *
     * What this adds is that "the migration ran" is not mistaken for "the migration
     * did its work": a dial that silently skipped this family would still migrate
     * cleanly and leave all nine relations unconstrained. So every family member is
     * checked individually against the engine, for BOTH roots — which is also the
     * assertion that catches a single-root discovery having quietly dropped the
     * `tf_channel_updates_*` half.
     *
     * The constraint NAME is read at the same time because MySQL's identifier limit
     * is 64 characters and sqlite has none: this family's longest name lands 12
     * characters under the ceiling, and an overrun is invisible on sqlite until the
     * first real ALTER.
     */
    public function test_every_discovered_family_relation_carries_a_constraint_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $constraints = $db->select(
            "SELECT kcu.TABLE_NAME AS child, kcu.CONSTRAINT_NAME AS name,
                    kcu.REFERENCED_TABLE_NAME AS parent,
                    GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS legs
               FROM information_schema.KEY_COLUMN_USAGE kcu
              WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                AND kcu.REFERENCED_TABLE_NAME IN ('tf_channel_participants', 'tf_channel_updates')
              GROUP BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME, kcu.REFERENCED_TABLE_NAME",
        );

        self::assertNotSame(
            [],
            $constraints,
            'no constraint on this engine references tf_channel_participants or tf_channel_updates, so '
            .'2026_10_04_000860 did not run or did nothing. The migration being in the dial is not the same '
            .'as the migration having done its work.',
        );

        // The family is READ OFF the engine against BOTH roots — never transcribed.
        // A transcription is a second copy of the schema and it drifts: a sibling
        // lane adding a `tf_channel_updates_*` table would be silently skipped.
        //
        // 🔴 SCOPED TO THIS DATABASE, 2026-10-10, and this is a REAL defect the
        // participant fold exposed rather than caused.
        //
        // On this engine `getSchemaBuilder()->getTableListing()` returns entries for
        // EVERY schema the server holds, schema-qualified — MEASURED on this tree:
        // 330 entries spanning `teleframe_*`, `websv_*`, `typa_kb.*` and even
        // `/tmp/llp-drill/full/restored.sqlite.cache`, a filesystem path that merely
        // CONTAINS a dot. Stripping everything left of the last dot and keeping the
        // bare name therefore imports identically-named tables from OTHER databases
        // into this one's family, while `$constraints` above is correctly restricted
        // by `kcu.CONSTRAINT_SCHEMA = DATABASE()`. The two halves then disagree by
        // construction, and a table that exists only elsewhere is reported as a local
        // member carrying no constraint.
        //
        // It stayed hidden while the folded tables were still local: they carried
        // constraints here, so the imported duplicate was masked by the real one.
        // `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact`
        // drops `tf_channel_participants_{inviter,kicked_by,promoted_by}` from THIS
        // database — MEASURED: 0 hits each in `teleframe_mysql_track` — and the
        // assertion then failed with
        //   `these family tables carry NO foreign key to their parent on this engine:
        //    tf_channel_participants_inviter, tf_channel_participants_kicked_by,
        //    tf_channel_participants_promoted_by`
        // naming three tables that are not in this database at all. Databases such
        // as `teleframe` and `teleframe_carrier` still hold all three, which is
        // exactly where the imported names came from.
        //
        // The fix is the DISCOVERY, not the expectation, and it is the helper this
        // class already inherits: `MySqlTestCase::mysqlTableNames()` reads
        // `information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()`, so it is
        // scoped by construction, returns BARE names needing no normalisation, and
        // excludes the harness's own sentinel tables. This is the same answer
        // `BackfillCrawlReachesMessageIdOneOnMysqlTest` already adopted for the same
        // defect, and reusing it keeps one way of asking this question on this lane.
        $family = [];
        foreach ($this->mysqlTableNames() as $bare) {
            if ($bare === 'tf_channel_participants'
                || str_starts_with($bare, 'tf_channel_participants_')
                || $bare === 'tf_channel_updates'
                || str_starts_with($bare, 'tf_channel_updates_')
            ) {
                $family[$bare] = true;
            }
        }

        // The roots are parents here, never children — their own link upward to
        // `tf_channels` belongs to a different family and a different lane.
        $constraintsByChild = [];
        foreach ($constraints as $constraint) {
            $constraintsByChild[(string) $constraint->child] = (string) $constraint->parent;
        }

        $unconstrained = [];
        foreach (array_keys($family) as $table) {
            if ($table === 'tf_channel_participants' || $table === 'tf_channel_updates') {
                continue;
            }
            if (! isset($constraintsByChild[$table])) {
                $unconstrained[] = $table;
            }
        }
        sort($unconstrained);

        self::assertSame(
            [],
            $unconstrained,
            'these family tables carry NO foreign key to their parent on this engine: '.implode(', ', $unconstrained)
            .'. Either the family migration did not run, or a discovery that walked only ONE of the two '
            .'roots left the other half behind.',
        );

        // The identifier budget, on the engine that actually enforces it.
        $tooLong = [];
        foreach ($constraints as $constraint) {
            $name = (string) $constraint->name;
            if (strlen($name) > 64) {
                $tooLong[] = $name.' ('.strlen($name).' characters)';
            }
        }

        self::assertSame(
            [],
            $tooLong,
            'these constraint names exceed MySQL\'s 64-character identifier limit, which it refuses with ERROR '
            .'1059: '.implode(', ', $tooLong).'. sqlite has no identifier limit, so this cannot be caught there.',
        );

        // Both roots must be represented, or the single-root failure mode this
        // family is specifically prone to is back and unnoticed.
        $parents = array_values(array_unique(array_map(
            static fn ($constraint): string => (string) $constraint->parent,
            $constraints,
        )));
        sort($parents);

        self::assertSame(
            ['tf_channel_participants', 'tf_channel_updates'],
            $parents,
            'constraints on this engine reference ['.implode(', ', $parents).'], but this family has TWO roots '
            .'and both must be referenced. A family whose two roots share the prefix `tf_channel_` is exactly '
            .'the shape where a single-root discovery loses half its work with no other symptom.',
        );
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /** The tenant row, exactly as the executed `telegram_accounts` requires it. */
    private function seedTenant(Connection $db, int $userId): void
    {
        $row = [
            'user_id' => $userId,
            'label' => 'mysql-ch-participants-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * The channel row.
     *
     * `2026_09_14_299999` declares `(account_id, channel_id) → tf_channels` on
     * `tf_channel_participants` with `RESTRICT ON DELETE`, so without this row every
     * participant fixture would be refused by that constraint instead of by the one
     * under test. `assertRowCoversRequiredColumns` decides what else is needed, so
     * this row is not a transcription of the migration's column list.
     */
    private function seedChannel(Connection $db, int $channel): void
    {
        $row = [
            'account_id' => self::TENANT,
            'id' => $channel,
            'constructor' => 'tf_channels',
        ];

        $this->assertRowCoversRequiredColumns($db, 'tf_channels', $row);
        $db->table('tf_channels')->insert($row);
    }

    /** The parent row — a participant, or a channel-diff row. */
    private function seedParent(Connection $db, string $parent, int $key): void
    {
        $row = $parent === 'tf_channel_participants'
            ? [
                'account_id' => self::TENANT,
                'channel_id' => self::CHANNEL,
                'user_id' => $key,
                'constructor' => 'channelParticipant',
                'date' => 1700000000,
            ]
            : [
                'account_id' => self::TENANT,
                'channel_id' => self::CHANNEL,
                'position' => $key,
                'constructor' => 'channelDifference',
            ];

        $this->assertRowCoversRequiredColumns($db, $parent, $row);
        $db->table($parent)->insert($row);
    }

    /**
     * The delete rule MySQL reports for `$child` → `$parent`, or null when there is
     * no such constraint.
     */
    private function parentDeleteRule(Connection $db, string $child, string $parent): ?string
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
