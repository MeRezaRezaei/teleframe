<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use ReflectionClass;

/**
 * The shipped curated dial must APPLY on real MySQL 8.4 — not merely avoid the
 * four shapes a source scanner looks for.
 */
final class CuratedDialMigratesOnMysqlTest extends MySqlTestCase
{
    public function test_the_whole_shipped_dial_migrates_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();
        $tables = $this->mysqlTableNames();

        self::assertNotEmpty($tables, 'the migration run created no tables at all');

        // Every root table the curated dial creates, by name. Taken from the
        // dial's own migration filenames, never from a table list typed here.
        foreach ($this->expectedRootTables() as $table) {
            self::assertContains($table, $tables, "{$table} must exist on MySQL after the full dial");
        }
    }

    public function test_the_tenant_foreign_keys_exist_on_mysql_and_not_only_in_sqlite(): void
    {
        $db = $this->migratedMysqlConnection();

        $count = (int) $db->selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.REFERENTIAL_CONSTRAINTS
              WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND REFERENCED_TABLE_NAME = 'telegram_accounts'",
        )->c;

        // Assert the FLOOR against the MIGRATION'S OWN PIN, read by reflection
        // rather than transcribed. A transcription here is a second copy of the
        // dial and it drifts: this test said 265 while
        // 2026_10_04_000700's own `EXPECTED_TENANT_TABLES` had already moved to
        // 264 (commit da926d87 deleted a table), so the test failed against a
        // schema that was exactly right. MEASURED on real MySQL 8.4.11:
        // 266 `tf_*` tables exist, 264 of them carry the tenant FK, and the
        // two that do not are `tf_accounts` (it IS the tenant table) and this
        // harness's own fingerprint sentinel. 264 is the correct number.
        // The pin counts what 000700 SAW; subtract what a LATER migration retires.
        $expected = $this->expectedTenantTables() - $this->tenantFksRetiredAfterThePin();

        self::assertGreaterThanOrEqual(
            $expected,
            $count,
            "expected at least the {$expected} tenant FKs of 2026_10_04_000700 on MySQL; found {$count}. "
            .'If this is lower, the migration applied fewer constraints than it claims — which is '
            .'invisible to the SQLite track because SQLite accepts a superset.',
        );
    }

    /**
     * The tenant-table count `2026_10_04_000700` pins in its own source.
     *
     * WHY IT IS READ AND NOT WRITTEN DOWN: a hard-coded number here is a second
     * copy of a constant that already exists in the migration, and the two WILL
     * drift — which is exactly what happened, and it cost a red MySQL track on a
     * schema that was correct. A missing or non-integer constant is a loud
     * failure rather than a silent default, because "the pin moved and the test
     * did not follow" must never read as "the pin is fine".
     */
    private function expectedTenantTables(): int
    {
        $file = dirname(__DIR__, 2)
            .'/src/Laravel/Migrations/2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php';

        self::assertFileIsReadable($file, "the tenant-FK migration is missing at {$file}");

        // The migration file RETURNS its object (that is how the dial runs it),
        // and ReflectionClass reflects an object — it does not take a file path.
        $migration = require $file;

        self::assertIsObject($migration, "{$file} did not return a Migration object");

        $constant = (new ReflectionClass($migration))->getConstant('EXPECTED_TENANT_TABLES');

        self::assertIsInt(
            $constant,
            '2026_10_04_000700 no longer declares an integer EXPECTED_TENANT_TABLES. If the pin was '
            .'renamed or removed, fix THIS test to follow it — never substitute a number of your own, '
            .'because a second copy of a dial constant is what drifted in the first place.',
        );

        return $constant;
    }

    /**
     * Tenant FKs a LATER migration deliberately retires.
     *
     * 🔴 TIMING, NOT DRIFT. `2026_10_04_000700` runs BEFORE the C5 fold
     * (`2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact`), and it pins
     * the count it sees at ITS point in the dial — 264, with
     * `tf_saved_star_gifts_gift_num` still present and carrying its tenant leg.
     *
     * The fold then drops that table, so the FINAL schema has 263 tenant FKs while the
     * migration's pin says 264. Both numbers are correct at the moment they are true:
     *
     *     expected at least the 264 tenant FKs … found 263
     *
     * The assertion's own failure message used to read "if this is lower, the migration
     * applied fewer constraints THAN IT CLAIMS" — which is the wrong inference here. It
     * applied exactly what it claims; a later migration removed one on purpose.
     *
     * So the floor is the pin MINUS the tables retired after it ran. The subtraction is
     * only ever applied to tables whose RETIREMENT is proven by a `Schema::drop`/`dropIfExists`
     * in a migration with a LATER timestamp — so removing a name here can never mask a
     * constraint the migration genuinely failed to apply. Drop the drop-migration and this
     * subtraction stops counting, and the floor returns to the full pin.
     */
    private function tenantFksRetiredAfterThePin(): int
    {
        $pinnedAt = '2026_10_04_000700';
        $retired = 0;

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $files = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];

            foreach ($files as $file) {
                // Only migrations that ran AFTER the pinning one may retire its counts.
                if (basename($file) <= $pinnedAt.'_add_tenant_foreign_keys_to_mirror_tables.php') {
                    continue;
                }

                $src = (string) file_get_contents($file);

                // The retiring table is named by a CONSTANT (`private const CHILD = …`),
                // not by a literal — the fold migration drops `Schema::dropIfExists(self::CHILD)`
                // — so matching the literal string alone finds nothing. Resolve the constant
                // first, then look for a drop of THAT name. Matching on the literal would
                // have silently returned 0 and left the floor wrong, which is exactly the
                // kind of quietly-wrong helper this whole finding is about.
                $dropped = $this->tablesDroppedBy($src);

                // 🔴 2026-10-08: a NAME LIST, deliberately, and not the C5 name alone.
                //
                // This helper is the MySQL half of the "how many tenant FKs exist" pair, and
                // the floor it feeds is the pin MINUS what a LATER migration retires. The C5
                // fold retired one table; the participant fold
                // (2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact)
                // retires three more, so the floor moves 264-1=263 -> 264-4=260.
                //
                // It has to be a list because `tenantFksRetiredAfterThePin()` asks a
                // per-TABLE question ("did a later migration retire THIS table's tenant
                // FK?") while the SQLite twin
                // (`TenantAccountForeignKeyTest::tenantTablesRetiredAfterThePin()`) asks the
                // per-MIGRATION one and counts generically. The two are pinned to the same
                // arithmetic on purpose: a name present in one and absent from the other is
                // exactly how these two floors silently disagree, which is the failure
                // 2026_10_04_000700's own docblock was written to stop.
                //
                // Every name here must have BOTH halves in the same shipped set — a
                // `Schema::create` somewhere and the `Schema::dropIfExists` in the fold. A
                // name with no matching drop would lower the floor and mask a constraint
                // the migration genuinely failed to apply.
                foreach (self::DROPPED_BY_THE_DIAL as $retiredTable) {
                    if (in_array($retiredTable, $dropped, true)) {
                        $retired++;
                    }
                }
            }
        }

        return $retired;
    }

    /**
     * Table names a migration actually DROPS, resolving `Schema::drop(self::CONST)` forms.
     *
     * @return list<string>
     */
    private function tablesDroppedBy(string $src): array
    {
        $dropped = [];

        if (preg_match_all("/Schema::drop(?:IfExists)?\('(tf_[a-z0-9_]+)'\)/", $src, $m)) {
            foreach ($m[1] as $name) {
                $dropped[] = $name;
            }
        }

        if (preg_match_all("/private const [A-Z_]+ = '(tf_[a-z0-9_]+)'/", $src, $c)) {
            foreach ($c[1] as $constant) {
                if (preg_match_all("/Schema::drop(?:IfExists)?\(self::[A-Z_]+\)/", $src)) {
                    $dropped[] = $constant;
                }
            }
        }

        return array_values(array_unique($dropped));
    }

    public function test_every_mirror_table_is_innodb(): void
    {
        $db = $this->migratedMysqlConnection();

        $nonInnoDb = $db->select(
            "SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND ENGINE <> 'InnoDB'",
        );

        self::assertSame(
            [],
            array_map(static fn (object $r): string => $r->name.' ('.$r->engine.')', $nonInnoDb),
            'a non-InnoDB table silently ignores every foreign key. This is invisible on SQLite, '
            .'which has no such engine split.',
        );
    }

    /**
     * Root tables the dial creates, read out of the dial's OWN migration text
     * so the list is never a second hand-typed copy.
     *
     * WHY THE SHAPE IS RESOLVED EXPLICITLY (measured 2026-10-05): the obvious
     * `glob($path)` mishandles `UpdateIngestor::migrationPaths()`'s mixed
     * list in BOTH directions. On the DIRECTORY entry it returns
     * `[the directory itself]`, so `file_get_contents()` reads a directory and
     * this method silently discovers NOTHING — the caller then loops over an
     * empty root list and every `assertContains` beneath it is vacuously
     * green, which is the exact failure this class exists to prevent. On a
     * plain FILE entry glob returns that file, so only the two entity anchors
     * were ever being read: 2 of the dial's 42 migrations.
     *
     * So each entry is resolved by shape and the file list is de-duplicated,
     * for the same reason migrationPaths() itself is: a migration must be read
     * once, and exactly once.
     *
     * ## WHY `ksort` AND NOT `sort` — MEASURED 2026-10-05 on real MySQL 8.4.11
     *
     * `$roots` is a SET, built as `$roots[$tableName] = true`, so its KEYS are
     * the table names and its VALUES are all `true`. `sort()` sorts by VALUE and
     * REINDEXES, which throws the table names away: the array becomes `[0 => true,
     * 1 => true, 2 => true, …]` and `array_keys()` then hands the caller the
     * INTEGERS 0…264 instead of the names.
     *
     * That is not a subtle degradation, it is the test asserting nothing about
     * the dial. Measured on a fully-migrated database with all 265 root tables
     * present, it failed with the literal message `0 must exist on MySQL after
     * the full dial` — a table named "0" that no migration has ever created.
     * `ksort()` sorts by key and preserves the key/value pairing, which is what
     * a set of names needs.
     *
     * @return list<string>
     */
    private function expectedRootTables(): array
    {
        $roots = [];
        $seen = [];

        foreach (UpdateIngestor::migrationPaths() as $path) {
            $files = is_dir($path) ? (glob($path.'/*.php') ?: []) : [$path];

            foreach ($files as $file) {
                if (isset($seen[$file])) {
                    continue;
                }

                $seen[$file] = true;
                $src = (string) file_get_contents($file);
                if (preg_match_all("/Schema::create\('(tf_[a-z0-9_]+)'/", $src, $m)) {
                    foreach ($m[1] as $name) {
                        $roots[$name] = true;
                    }
                }
            }
        }

        ksort($roots);

        // 🔴 A `Schema::create` is not the same as a table that SURVIVES the dial.
        //
        // This scraper used to collect every `Schema::create('tf_…')` in the migration
        // source and assert each one exists after the full run. That was true only while
        // no migration ever dropped a table — and the C5 fold
        // (2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact) is the
        // first that does:
        //
        //     tf_saved_star_gifts_gift_num must exist on MySQL after the full dial
        //     Failed asserting that an array contains 'tf_saved_star_gifts_gift_num'.
        //
        // The table is still CREATED by 200040 and later DROPPED, so the scraper demanded a
        // table the shipped dial deliberately removes. Asserting against creation is
        // asserting against history; what the gate must protect is the END STATE.
        //
        // So a dropped table is subtracted. This is not weakening: the assertion's whole
        // purpose is "the dial produces the tables it is supposed to produce", and a table
        // the catalog and TL no longer call for is not one of them. Removing a name from
        // this list can never make a real table disappear — it only stops the gate
        // demanding one the schema has deliberately retired.
        foreach (self::DROPPED_BY_THE_DIAL as $dropped) {
            unset($roots[$dropped]);
        }

        return array_map(strval(...), array_keys($roots));
    }

    /**
     * Tables the shipped dial creates and then deliberately drops.
     *
     * MEASURED, not guessed: each name is a `Schema::create` in the dial paired with a
     * `Schema::dropIfExists` in a LATER migration of the same shipped set. Add an entry
     * only when both halves are present — a name here with no matching drop would hide a
     * real regression.
     */
    private const DROPPED_BY_THE_DIAL = [
        // C5, audit row C5: `savedStarGift#41df43fc … gift_num:flags.19?int` is a
        // SINGLE int, so the child table that held it asserted an object the wire does
        // not nest. `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact`
        // folds the value onto `tf_saved_star_gifts` — the parent fact, whose TL ctor
        // declares the field — and drops this table.
        //
        // NOT `tf_saved_star_gifts_gift`: the union row already stores the StarGift's own
        // `starGiftUnique#85f0a9cd … num:int` in a `num` column, and `gift_num` is a
        // different field on a different ctor. See the migration's docblock for the
        // three-way measurement that settles the parent.
        'tf_saved_star_gifts_gift_num',

        // C5, audit rows C5 x3: `channelParticipantSelf#a9478a1a … inviter_id:long`,
        // `channelParticipantAdmin#34c3bb53 … promoted_by:long` and
        // `channelParticipantBanned#d5f0ad91 … kicked_by:long` are each a bare `long` on
        // ONE constructor — no `Vector<>`, no element ordinal, so there is no list and
        // nothing for a child TABLE to hold.
        // `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact`
        // folds each value onto `tf_channel_participants` and drops these tables.
        //
        // MEASURED, not assumed: the assertion failed and printed
        // `tf_channel_participants_inviter must exist on MySQL after the full dial`
        // before these three were listed. Each name below has BOTH halves — a
        // `Schema::create` in 2026_09_14_200031 and a `Schema::dropIfExists` in the
        // 2026-10-08 fold.
        //
        // NOT `tf_channel_participants_{rank,subscription_until_date,peer}`: those are the
        // same shape of over-decomposition, but the audit does not name them — `tf_channels`
        // is one of its eight BLIND parents, and a blind row may NOT be acted on. Listing
        // them here would delete a table on the strength of my own reading rather than a
        // finding, which is the exact move the blind-bucket rule forbids.
        'tf_channel_participants_inviter',
        'tf_channel_participants_kicked_by',
        'tf_channel_participants_promoted_by',

        // C5, audit rows C5 x3: `updateChannelTooLong#108d941f flags:# channel_id:long`,
        // `updateChatUserTyping#83487af0 chat_id:long` and `updateUserStatus#e5bdf8de
        // user_id:long` are each a bare `long` on `Update` — no `Vector<>`, no element
        // ordinal, so there is no list and nothing for a child TABLE to hold.
        // `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact` folds each
        // value onto `tf_updates` and drops these tables. Measured, not assumed: the
        // assertion fails with `tf_updates_channel must exist on MySQL after the full
        // dial` if these are absent from this list.
        //
        // NOT `tf_updates_from`: `from_id` is `Peer` on every `Update` constructor that
        // declares it, so that table is a correct nested storage translation and still
        // exists after the dial. `tf_updates_peer` likewise survives. See the fold
        // migration's docblock for the measurement that settles `from_id`.
        'tf_updates_channel',
        'tf_updates_chat',
        'tf_updates_user',

        // C5, audit row C5: `botInfo#4d8a0299 … description:flags.1?string` is a
        // bare `string` on ONE constructor — no `Vector<>`, no element ordinal, so
        // there is nothing for a child TABLE to hold.
        // `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact`
        // folds the value onto `tf_bot_infos.description` and drops this table.
        //
        // NOT `tf_bot_infos_description_{photo,document}`: those are genuine
        // `Vector<Photo>` / `Vector<Document>` nested collections and still exist
        // after the dial — only their `*_bot_parent_fk` was re-pointed from
        // `(account_id,id)` on this table to `(account_id,id)` on `tf_bot_infos`.
        // Listing them here would delete two tables the schema still calls for.
        'tf_bot_infos_description',
    ];
}
