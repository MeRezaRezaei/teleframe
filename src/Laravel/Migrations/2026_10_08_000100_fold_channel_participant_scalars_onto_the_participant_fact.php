<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tf_channel_participants_{inviter,kicked_by,promoted_by}` were never tables the
 * wire asked for. Fold each one up onto the `tf_channel_participants` fact row and
 * drop it with the false constraint that made it look like one.
 *
 * ## THE DEFECT, IN ONE LINE — audit row C5, `php bin/audit-fidelity.php`
 *
 * ```
 * C5  tf_channel_participants_inviter    fk:tf_channel_participants_inviter->tf_channel_participants
 *     the TL: `ChannelParticipant` declares `inviter_id` as a plain `scalar` — a single long value,
 *     so the wire nests nothing there
 *     ours: a live foreign key on [account_id,channel_id,user_id] makes `tf_channel_participants` the
 *     parent of a row, so a child TABLE exists where the wire holds a single value
 *     move: store the scalar on `tf_channel_participants` as the column the TL names, and drop the
 *     child table and its constraint — the wire has no object there to store
 * ```
 *
 * The audit names three rows in this family and they are the same defect three
 * times: `inviter`, `kicked_by`, `promoted_by`. One migration folds all three,
 * because they are one fact about one table and splitting them would mean three
 * files asserting the same arithmetic.
 *
 * ## THE WIRE, READ BY HAND — `schema/sources/TL_telegram_v227.tl`
 *
 * ```
 * 766: channelParticipant#1bd54456        flags:# user_id:long date:int subscription_until_date:flags.0?int rank:flags.2?string = ChannelParticipant;
 * 767: channelParticipantSelf#a9478a1a   flags:# via_request:flags.0?true user_id:long inviter_id:long date:int …
 * 769: channelParticipantAdmin#34c3bb53  flags:# can_edit:flags.0?true self:flags.1?true user_id:long inviter_id:flags.1?long promoted_by:long date:int …
 * 770: channelParticipantBanned#d5f0ad91 flags:# left:flags.0?true peer:Peer kicked_by:long date:int …
 * ```
 *
 * Every one of the three is a bare `long` on ONE constructor. `inviter_id` is
 * declared twice — unconditionally on `channelParticipantSelf` (767) and
 * conditionally as `flags.1?long` on `channelParticipantAdmin` (769) — but in
 * neither case is it an OBJECT. There is no `Vector<>` on any of them, so there
 * is no element ordinal, so there is no list, so there is nothing for a child
 * TABLE to hold. A row in `tf_channel_participants_inviter` asserts that a
 * `ChannelParticipant` contains an `inviter_id` object. It does not. It contains
 * an `inviter_id` value.
 *
 * Contrast the two children that DO stay, because they are the control:
 * `tf_channel_participants_rank` is `rank:flags.2?string` — a scalar too, and
 * still a table. That is a genuine over-decomposition of the same family and it
 * is NOT this migration's business (the audit does not flag it, because
 * `tf_channels` is one of the audit's eight BLIND parents and a blind row may not
 * be acted on). `tf_channel_participants_subscription_until_date` likewise stays.
 * This migration closes the three rows the audit named and leaves the rest of the
 * family exactly as it found it — a lane that fixed the family would be editing
 * its neighbours' verdicts.
 *
 * ## WHY THE TABLE NAMES DROPPED THE `_id` SUFFIX
 *
 * `tf_channel_participants_inviter` vs `inviter_id`. The audit tolerates this
 * deliberately and for a measured reason: `childFieldName()` tries both spellings
 * and records which one matched, because a strict comparison would report "the
 * authorities are silent" for a table whose descent is perfectly faithful — and
 * silence is what used to produce a deletion recommendation
 * (`bin/audit-fidelity.php:790-824`). So `inviter_id` here is the TL spelling and
 * the table spelling was the abbreviation. The folded COLUMN takes the TL
 * spelling, because after the fold there is no table name to abbreviate.
 *
 * ## NULLABILITY — THE TABLE'S OWN PRECEDENT, NOT A NEW RULE
 *
 * Each of the three fields is absent on some constructors: `inviter_id` is on
 * none of `channelParticipant`/`Creator`/`Banned`/`Left`, `promoted_by` only on
 * `Admin`, `kicked_by` only on `Banned`. A single column therefore has to carry
 * "this participant declares no such field". The house answer on THIS table is
 * already shipped, and it is NOT NULL plus a falsy default:
 *
 * ```
 * 2026_09_14_200031_create_tf_channel_participants_tables.php:17  "'date' is 0 for
 *     channelParticipantCreator (no date field)."
 * 2026_09_14_200031_create_tf_channel_participants_tables.php:77  $table->integer('date')->default(0);
 * ```
 *
 * `date` is the exact same shape of field — a scalar `int` that only SOME union
 * members declare — and it is `NOT NULL DEFAULT 0` on this very table. The three
 * folded columns follow that convention rather than inventing a second one on the
 * same row, and 0 is a representable `long` that names no real Telegram user, so
 * it does not collide with a genuine value.
 *
 * This is also what audit rule C4 requires, and it is worth being honest about
 * WHY. `paramAcrossCtors()` returns the FIRST constructor that declares the field,
 * and the ctors are listed in catalog order: `channelParticipantSelf` declares
 * `inviter_id:long` UNCONDITIONALLY (767), so C4 reads the field as always-present
 * and requires NOT NULL. `promoted_by` (769) and `kicked_by` (770) are each
 * declared unconditionally by the one ctor that has them, so both also require
 * NOT NULL. A nullable column here would be scored as a COPYING-GAP on the very
 * table the fold is meant to make faithful — and the honest answer to C4 is that
 * the table's convention already satisfies it, not that the convention is wrong.
 *
 * ## TYPE — `long` is BIGINT, and it is already BIGINT
 *
 * Every one of the three fields is TL `long` = signed 64-bit, and the value
 * columns in the child tables were ALREADY `bigint` (read off the executed shape,
 * `src/Schema/schema/mirror-shape.json`). So the fold moves a value without
 * retyping it, and no truncation is introduced in either direction.
 *
 * ## WHY A NEW FILE AND NOT AN EDIT TO 200031
 *
 * `2026_09_14_200031` is SHIPPED (tags v1.3.0/v1.3.1/v1.3.2; the convention and
 * its evidence are recorded at
 * `2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php`, "WHY A NEW FILE AND
 * NOT AN EDIT TO 200040"). An install that already ran it has the three fabricated
 * tables recorded as applied forever. Dropping them is a change a reader has to be
 * able to justify, and this file is that justification — the same reasoning, in the
 * same shape, as the C5 fold this file follows.
 *
 * `2026_10_04_000860_add_tf_channel_participants_family_foreign_keys.php` is not
 * edited either and does not need to be: its relations are DISCOVERED at runtime
 * (`MirrorRelationPlan::fromConnection()` plus a name prefix), so a table that no
 * longer exists is simply not discovered. What DOES change there is its docblock,
 * which claims six edges into this family, so the prose is corrected below rather
 * than left asserting a schema that no longer ships.
 *
 * ## WHY NO RAW SQL — D-032, LEARNED THE HARD WAY
 *
 * `2026_10_04_000600` shipped a two-way `isPostgres()` branch whose `else` was
 * assumed to be MySQL; the third engine was SQLite, and SQLite received
 * `ALTER TABLE … MODIFY`, which is MySQL-only and a hard error there. A two-way
 * dialect branch is a three-way bug. So this file emits NO hand-written DDL
 * dialect: everything goes through the schema builder, which picks the right
 * statement on all three engines.
 *
 *   - `Schema::table(…)->bigInteger(…)` — ADD COLUMN, valid on MySQL/PG/SQLite.
 *   - `Schema::dropIfExists(…)` — the child table goes with BOTH its constraints
 *     attached (the tenant leg from 000700 and the false parent leg from 000860),
 *     so no constraint is named here and no name can be spelled wrong per driver.
 *   - the backfill runs in PHP through the query builder, not as a correlated
 *     `UPDATE … SET x = (SELECT …)`, because a PHP loop cannot be dialect-wrong.
 *
 * ## THE BACKFILL IS NOT OPTIONAL
 *
 * Dropping the table drops its rows. On a fresh install it is empty and the
 * backfill is a no-op; on an install that has been running, `inviter_id` is a real
 * value and dropping it silently is data loss. The join needs no guess: the child
 * carries the parent's own `(account_id, channel_id, user_id)` key — it is that
 * column triple in its PRIMARY KEY — so it denotes one and the same participant on
 * both tables. Orphan child rows (parent gone) are COUNTED, not papered over by
 * inventing a parent, for the same reason the C5 fold does it that way.
 *
 * ## down() RESTORES EXACTLY WHAT WAS THERE
 *
 * The child table is recreated with 200031's three key columns, 000700's tenant
 * leg, 000860's parent leg, and the value column, so a rollback returns the schema
 * to its pre-migration state rather than reporting a rollback that changed nothing.
 * The restored `…_ch_parent_fk` constraint is the FALSE one this migration exists
 * to remove, and the docblock at the restore site says so where a reader of
 * `down()` will meet it.
 *
 * Both restored constraint names are the names the executed schema already carries
 * (`2026_10_04_000860`'s `CONSTRAINT_SUFFIX = '_ch_parent_fk'`, `2026_10_04_000700`'s
 * `CONSTRAINT_SUFFIX = '_acc_tenant_fk'`), so this file restates no spelling that
 * could drift, and every one is inside the 64-character identifier cap that MySQL
 * enforces at 65 (MySQL-1059):
 *
 *   php -r 'echo strlen("tf_channel_participants_promoted_by_acc_tenant_fk");'  -> 49
 *   php -r 'echo strlen("tf_channel_participants_promoted_by_ch_parent_fk");'  -> 48
 *   php -r 'echo strlen("tf_channel_participants_kicked_by_ch_parent_fk");'    -> 46
 *   php -r 'echo strlen("tf_channel_participants_inviter_ch_parent_fk");'      -> 44
 * ```
 *
 * `promoted_by` is the longest stem in the family and therefore the worst case;
 * the longest single identifier this file restores is 49.
 *
 * ## THE CONTROL — WHAT IS RED WITHOUT THIS FILE
 *
 * MEASURED 2026-10-08 on the tree as it stood, before any edit:
 *
 * ```
 * timeout -k 5 900 php bin/audit-fidelity.php
 *   verdicts  comparisons=81  COPYING-GAP=5  STORAGE-TRANSLATION=25  DELIBERATE=0  UNEXPLAINED=51
 * ```
 *
 * with all three of these C5 rows present and with
 * `tf_channel_participants_{inviter,kicked_by,promoted_by}` in the `Schema::create`
 * list of `CuratedDialMigratesOnMysqlTest::expectedRootTables()` and in the 206
 * derived relations `TfMessagesServiceIsNotAChildTest` pins. After this file the
 * same command must report COPYING-GAP=2 — only the two `tf_stars_transactions_*`
 * C3 rows remain, and those are a different defect this migration does not touch —
 * and the 25 STORAGE-TRANSLATION / 51 UNEXPLAINED rows must be UNCHANGED.
 */
return new class extends Migration
{
    /** The fact row the values belong on. `ChannelParticipant` -> this table. */
    private const PARENT = 'tf_channel_participants';

    /**
     * Each fold: the fabricated table, and the column the TL names on it.
     *
     * The table stem and the column name DIFFER, and that difference is load-bearing:
     * `tf_channel_participants_inviter` held a column called `inviter_id`. The audit's
     * `childFieldName()` drops the `_id` suffix when it derives a child table's field
     * name from the table name, and tolerates both spellings for exactly that reason.
     * So these pairs are stored explicitly rather than derived, which also keeps this
     * migration from ever re-implementing a rule the audit already owns.
     *
     * TL, verbatim, for each:
     *   767: channelParticipantSelf#a9478a1a … inviter_id:long …
     *   769: channelParticipantAdmin#34c3bb53 … promoted_by:long …
     *   770: channelParticipantBanned#d5f0ad91 … kicked_by:long …
     *
     * @var list<array{0:string,1:string}>
     */
    private const FOLDS = [
        ['tf_channel_participants_inviter', 'inviter_id'],
        ['tf_channel_participants_kicked_by', 'kicked_by'],
        ['tf_channel_participants_promoted_by', 'promoted_by'],
    ];

    /**
     * The three key columns every child of this family carries — 200031's
     * `Schema::create` block, which is literally `unsignedBigInteger account_id`,
     * `bigInteger channel_id`, `bigInteger user_id` followed by `primary([...])`.
     * They are restated rather than derived because `down()` has to reproduce the
     * key EXACTLY: a parent key that is not byte-equal to the child's is not a
     * rollback, it is a different schema.
     */
    private const KEY = ['account_id', 'channel_id', 'user_id'];

    /** Rows per chunk in the backfill. Small because these are facts, not a log. */
    private const CHUNK = 500;

    /** How many rows each backfill carried across. Recorded so a reader sees it ran. */
    private array $moved = [];

    public function up(): void
    {
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        foreach (self::FOLDS as [$child, $column]) {
            $this->addFoldedColumn($column);
            $this->backfillFromChild($child, $column);
        }

        // Dropping a table takes BOTH of its constraints with it — the tenant leg
        // from 000700 and the false parent leg from 000860 — so nothing is named
        // here and no driver-specific spelling can be wrong. Idempotent: a fresh
        // install, or a re-run, has no table to drop.
        //
        // Written as three separate `dropIfExists('literal')` calls, not a loop over
        // a constant, because BOTH count reconciliations read the migration TEXT:
        // `TenantAccountForeignKeyTest::tenantTablesRetiredAfterThePin()` and
        // `CuratedDialMigratesOnMysqlTest::tenantFksRetiredAfterThePin()` scan for
        // `Schema::drop…('tf_…')` and `Schema::drop…(self::CONST)`. A loop over
        // `self::FOLDS` matches NEITHER pattern and both helpers would silently
        // count 0 retirements and leave two floors wrong by three.
        Schema::dropIfExists('tf_channel_participants_inviter');
        Schema::dropIfExists('tf_channel_participants_kicked_by');
        Schema::dropIfExists('tf_channel_participants_promoted_by');
    }

    /**
     * The column, added once, `NOT NULL DEFAULT 0` — the same declaration
     * `tf_channel_participants.date` already carries for a field that only some
     * union members declare. See the docblock's NULLABILITY section for why 0 and
     * not NULL is the honest encoding here rather than a gate concession.
     *
     * `Schema::hasColumn` rather than a try/catch, so a re-run is a no-op instead of
     * a duplicate-column error — the same idempotence 2026_10_04_000600 and
     * 2026_10_04_001200 both rely on.
     */
    private function addFoldedColumn(string $column): void
    {
        if (Schema::hasColumn(self::PARENT, $column)) {
            return;
        }

        Schema::table(self::PARENT, function (Blueprint $table) use ($column): void {
            $table->bigInteger($column)->default(0);
        });
    }

    /**
     * Carry every stored value across before its table goes.
     *
     * PHP, not a correlated UPDATE: it cannot be dialect-wrong, and it lets the value
     * be cast on the way rather than trusting each engine's coercion. The join is the
     * child's own PRIMARY KEY against the parent's, so it is the same participant on
     * both sides by construction.
     */
    private function backfillFromChild(string $child, string $column): void
    {
        $this->moved[$child] = 0;

        if (! Schema::hasTable($child)) {
            return;
        }

        DB::table($child)
            ->select([...self::KEY, $column])
            ->orderBy('account_id')
            ->orderBy('channel_id')
            ->orderBy('user_id')
            ->chunk(self::CHUNK, function ($rows) use ($child, $column): void {
                foreach ($rows as $row) {
                    $target = DB::table(self::PARENT);
                    foreach (self::KEY as $key) {
                        $target->where($key, $row->{$key});
                    }

                    // A child row whose parent is gone is an orphan this migration
                    // must not paper over by inventing a parent. It is skipped, not
                    // silently dropped into somebody else's row.
                    if ($target->first() === null) {
                        continue;
                    }

                    $update = DB::table(self::PARENT);
                    foreach (self::KEY as $key) {
                        $update->where($key, $row->{$key});
                    }
                    $update->update([$column => $row->{$column}]);

                    $this->moved[$child]++;
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::PARENT)) {
            return;
        }

        foreach (self::FOLDS as [$child, $column]) {
            if (Schema::hasColumn(self::PARENT, $column)) {
                Schema::table(self::PARENT, function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }

            $this->restoreChildTable($child, $column);
        }
    }

    /**
     * Put the fabricated table back, with both constraints and the key it had.
     *
     * The `…_ch_parent_fk` constraint below is the FALSE one this migration exists
     * to remove: it makes a participant row the parent of a row the wire does not
     * nest. It is restored because `down()` must be a true inverse, and it is named
     * here so a reader who rolls this back knows the schema they are returning to is
     * the one audit row C5 calls a defect.
     */
    private function restoreChildTable(string $child, string $column): void
    {
        if (Schema::hasTable($child)) {
            return; // already restored — a re-run of down() is a no-op
        }

        // 🔴 `$child` IS CAPTURED and not merely `$column`. A closure that reaches for
        // an uncaptured variable is a RUNTIME error, not a parse error, so `php -l`
        // passes it silently and the two constraint names below come out as the
        // bare `'_acc_tenant_fk'` / `'_ch_parent_fk'` — two constraints that collide
        // with every other restore in this family. The static analyser is what caught
        // it; a syntax check could not have.
        Schema::create($child, function (Blueprint $table) use ($child, $column): void {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('channel_id');
            $table->bigInteger('user_id');
            $table->bigInteger($column);
            $table->primary(self::KEY);

            // 2026_10_04_000700's CONSTRAINT_SUFFIX, restated so no name can drift.
            $table->foreign('account_id', $child.'_acc_tenant_fk')
                ->references('user_id')
                ->on('telegram_accounts')
                ->cascadeOnDelete();

            // 🔴 THE FALSE CONSTRAINT — see the method docblock above.
            $table->foreign(self::KEY, $child.'_ch_parent_fk')
                ->references(self::KEY)
                ->on(self::PARENT)
                ->cascadeOnDelete();
        });
    }
};
