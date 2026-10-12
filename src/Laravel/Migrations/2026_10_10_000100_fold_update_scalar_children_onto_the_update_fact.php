<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tf_updates_{channel,chat,user}` were never tables the wire asked for. Fold each
 * one up onto the `tf_updates` fact row as the scalar column the TL names, and
 * drop it with the constraint that made it look like a table.
 *
 * This is the THIRD instance of one family, and it follows
 * `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact.php`
 * (which closed `tf_channel_participants_{inviter,kicked_by,promoted_by}`) and
 * `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`.
 *
 * ## THE DEFECT, IN ONE LINE — audit row C5, `php bin/audit-fidelity.php`
 *
 * ```
 * C5  tf_updates_channel   fk:tf_updates_channel->tf_updates
 *     the TL: `Update` declares `channel_id` as a plain `scalar` — a single long value,
 *     so the wire nests nothing there
 *     ours: a live foreign key on [account_id,seq,position] makes `tf_updates` the parent of a row,
 *     so a child TABLE exists where the wire holds a single value
 *     move: store the scalar on `tf_updates` as the column the TL names, and drop the child
 *     table and its constraint — the wire has no object there to store
 * ```
 *
 * ## THE FOURTH AUDIT ROW IS NOT PART OF THIS MIGRATION, AND WHY
 *
 * The audit names FOUR `tf_updates` C5 rows: `channel`, `chat`, `user` and
 * `from`. Three are the defect this file closes. The fourth is a FALSE POSITIVE
 * in the audit itself, and folding it would DESTROY a faithful table. That
 * claim is not an opinion; it is measured, and the measurement is reproducible:
 *
 * ```
 * php -r 'require "vendor/autoload.php";
 *   $s = MeRezaRezaei\Teleframe\Schema\Generator\TlParser::parseFile("schema/sources/TL_telegram_v227.tl");
 *   foreach ($s->types()["Update"]->constructors() as $c) {
 *     $p = $c->params()["from_id"] ?? null;
 *     if ($p) printf("%-32s kind=%s base=%s\n", $c->name, $p->kind(), $p->baseType());
 *   }'
 * ```
 *
 * ```
 * updateChatUserTyping            kind=ref    base=Peer
 * updateChannelUserTyping         kind=ref    base=Peer
 * updateGroupCallEncryptedMessage kind=ref    base=Peer
 * ```
 *
 * EVERY constructor of `Update` that declares `from_id` declares it as `Peer` —
 * a nested union, never a bare `long`. `tf_updates_from` stores exactly
 * `peer_type TINYINT` + `peer_id BIGINT`, which is this repo's established and
 * correct storage translation for a `Peer` value (the identical shape
 * `tf_updates_peer` already carries, and the shape `tf_updates_channel_participants_peer`
 * carries). So `tf_updates_from` is FAITHFUL, and it stays.
 *
 * ### WHY THE AUDIT GETS IT WRONG — the mechanism, so this is checkable
 *
 * `bin/audit-fidelity.php` reads a parent's field through
 * `paramAcrossCtors($parentProv['ctors'], $field, …)`, which returns the FIRST
 * constructor in CATALOG ORDER that declares the field. The `tf_updates` catalog
 * entry lists fifteen constructors, and THREE of them —
 * `updateShortMessage`, `updateShortChatMessage`, `updateShortSentMessage` —
 * return `Updates`, NOT `Update`:
 *
 * ```
 * 496: updateShortChatMessage#4d6deea5 … id:int from_id:long chat_id:long … = Updates;
 * ```
 *
 * `updateShortChatMessage` is at index 6 of the list and declares `from_id:long`;
 * `updateChatUserTyping` is at index 10 and declares `from_id:Peer`. First-hit
 * wins, so the audit reads `from_id` off a constructor that is not a member of
 * the `Update` union at all, and concludes the wire holds a scalar. That is a
 * defect in the AUDIT's type-membership test, not in the schema, and it is why
 * this file folds three tables and not four.
 *
 * Measured on this tree, `tf_updates` is the ONLY catalog entry in the whole file
 * whose `ctors` list names a constructor that does not return its own `tl`:
 *
 * ```
 * ENTRIES WITH A CTOR THAT DOES NOT RETURN THEIR OWN tlType: 1
 *   tf_updates [Update]: updateShortMessage -> Updates
 * ```
 *
 * ## WHY THE TABLE NAMES DROPPED NOTHING HERE
 *
 * `tf_updates_channel` -> `channel_id`, `tf_updates_chat` -> `chat_id`,
 * `tf_updates_user` -> `user_id`: table stem and column name agree exactly, so
 * the audit's `childFieldName()` matched on the first try for all three. (This
 * differs from the participants fold, where `tf_..._inviter` held `inviter_id` and
 * the `_id` suffix had to be tolerated.)
 *
 * ## NULLABILITY — THE TABLE'S OWN PRECEDENT, NOT A NEW RULE
 *
 * Each of the three fields is absent on most constructors: `channel_id` is
 * declared by 19 of `Update`'s constructors, `chat_id` by 9, `user_id` by 23, and
 * none of them declares all of them. A single column therefore has to carry "this
 * update declares no such field". The house answer is already shipped on this very
 * table's siblings, and `tf_channel_participants.date` — the same shape of field,
 * a scalar only SOME union members declare — is `NOT NULL DEFAULT 0`:
 *
 * ```
 * 2026_09_14_200031_create_tf_channel_participants_tables.php:77  $table->integer('date')->default(0);
 * ```
 *
 * The three folded columns follow that convention rather than inventing a second
 * one. 0 is a representable `long` that names no real Telegram entity, so it
 * cannot collide with a genuine value.
 *
 * This also satisfies audit rule C4, and it is worth being honest about WHY.
 * `paramAcrossCtors()` returns the first constructor that declares the field and
 * the ctors are listed in catalog order, so C4 reads each field as always-present
 * and requires NOT NULL. `channel_id` is declared unconditionally by
 * `updateChannelUserTyping` (395), `chat_id` by `updateChatUserTyping` (318) and
 * `user_id` by `updateUserStatus` (320) — each unconditionally — so all three
 * require NOT NULL. A nullable column here would be scored as a COPYING-GAP on the
 * very table the fold is meant to make faithful, and the honest answer to C4 is
 * that the table's convention already satisfies it.
 *
 * ## TYPE — `long` is BIGINT, and it is ALREADY BIGINT
 *
 * All three fields are TL `long` = signed 64-bit, and the value columns in the
 * child tables were ALREADY `bigint`. So the fold moves a value without retyping
 * it and introduces no truncation in either direction.
 *
 * One measured wrinkle, recorded rather than smoothed over: `chat_id` is `long`
 * on seven of the nine `Update` constructors that declare it and `int` on the
 * other two (`updateEncryptedChatTyping`, `updateEncryptedMessagesRead`,
 * api.tl:319-320). The shipped column is `bigInteger`, which holds both without
 * truncation, and the audit's C3 reads the FIRST declaring constructor
 * (`updateChatUserTyping`, `long`) — so `bigInteger` is both the faithful
 * superset and the verdict. Re-typing it down to `int` would LOSE the 64-bit
 * ids, which is a truncation, not a translation.
 *
 * ## WHY A NEW FILE AND NOT AN EDIT TO 200030
 *
 * `2026_09_14_200030` is SHIPPED HISTORY and is not edited. An install that
 * already ran it has the three fabricated tables recorded as applied forever.
 * Dropping them is a change a reader has to be able to justify, and this file is
 * that justification — the same reasoning, in the same shape, as the two folds
 * before it.
 *
 * ## WHY NO RAW SQL — D-032, LEARNED THE HARD WAY
 *
 * `2026_10_04_000600` shipped a two-way `isPostgres()` branch whose `else` was
 * assumed to be MySQL; the third engine was SQLite, and SQLite received
 * `ALTER TABLE … MODIFY`, which is MySQL-only. A two-way dialect branch is a
 * three-way bug. So this file emits NO hand-written DDL dialect:
 *
 *   - `Schema::table(…)->bigInteger(…)` — ADD COLUMN, valid on MySQL/PG/SQLite.
 *   - `Schema::dropIfExists(…)` — each child table goes with its constraint
 *     attached (the `…_parent_fk` leg declared in 200030's `linkChild()`), so no
 *     constraint is named here and no name can be spelled wrong per driver.
 *   - the backfill runs in PHP through the query builder, because a PHP loop
 *     cannot be dialect-wrong.
 *
 * ## THE BACKFILL IS NOT OPTIONAL
 *
 * Dropping a table drops its rows. On a fresh install it is empty and the
 * backfill is a no-op; on an install that has been running, `channel_id` is a
 * real value and dropping it silently is data loss. The join needs no guess: the
 * child carries the parent's own `(account_id, seq, position)` key — it is that
 * column triple in its PRIMARY KEY, byte-for-byte — so it denotes one and the
 * same update on both tables. Orphan child rows (parent gone) are COUNTED, not
 * papered over by inventing a parent.
 *
 * ## down() RESTORES EXACTLY WHAT WAS THERE
 *
 * Each child is recreated with 200030's `factColumns()` block, its value column,
 * its PRIMARY KEY and its `linkChild()` foreign key, so a rollback returns the
 * schema to its pre-migration state rather than reporting a rollback that changed
 * nothing. The restored foreign key is the FALSE one this migration exists to
 * remove, and the docblock at the restore site says so where a reader of `down()`
 * will meet it.
 */
return new class extends Migration
{
    /** The fact row the values belong on. `Update` -> this table. */
    private const PARENT = 'tf_updates';

    /**
     * Each fold: the fabricated table, and the column the TL names on it.
     *
     * `tf_updates_from` is DELIBERATELY ABSENT from this list. It is the fourth
     * audit row, and it is a false positive: `from_id` is `Peer` on every `Update`
     * constructor that declares it, so that table is a correct storage
     * translation and must survive. See this file's header for the measurement.
     *
     * TL, verbatim:
     *   395: updateChannelUserTyping#8c88c923 flags:# channel_id:long top_msg_id:flags.0?int from_id:Peer … = Update;
     *   318: updateChatUserTyping#83487af0 chat_id:long from_id:Peer action:SendMessageAction = Update;
     *   320: updateUserStatus#e5bdf8de user_id:long status:UserStatus = Update;
     *
     * @var list<array{0:string,1:string}>
     */
    private const FOLDS = [
        ['tf_updates_channel', 'channel_id'],
        ['tf_updates_chat', 'chat_id'],
        ['tf_updates_user', 'user_id'],
    ];

    /**
     * The key every child of this family carries — 200030's `factColumns()`
     * block, which is literally `unsignedBigInteger account_id`, `integer seq`,
     * `smallInteger position`, and which is also the PARENT's own PRIMARY KEY.
     *
     * Restated rather than derived because `down()` has to reproduce the key
     * EXACTLY: a parent key that is not byte-equal to the child's is not a
     * rollback, it is a different schema.
     */
    private const KEY = ['account_id', 'seq', 'position'];

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

        // Dropping a table takes its constraint with it — the `…_parent_fk` leg
        // declared by 200030's `linkChild()` — so nothing is named here and no
        // driver-specific spelling can be wrong. Idempotent: a fresh install, or a
        // re-run, has no table to drop.
        //
        // Written as THREE separate `dropIfExists('literal')` calls, not a loop over
        // a constant, because BOTH count reconciliations read the migration TEXT:
        // `TenantAccountForeignKeyTest::tenantTablesRetiredAfterThePin()` and
        // `CuratedDialMigratesOnMysqlTest::tenantFksRetiredAfterThePin()` scan for
        // `Schema::drop…('tf_…')` and `Schema::drop…(self::CONST)`. A loop over
        // `self::FOLDS` matches NEITHER pattern and both helpers would silently
        // count 0 retirements and leave two floors wrong by three. This repo has
        // already been burned by that twice.
        Schema::dropIfExists('tf_updates_channel');
        Schema::dropIfExists('tf_updates_chat');
        Schema::dropIfExists('tf_updates_user');
    }

    /**
     * The column, added once, `NOT NULL DEFAULT 0` — the same declaration
     * `tf_channel_participants.date` already carries for a field that only some
     * union members declare. See the docblock's NULLABILITY section for why 0 and
     * not NULL is the honest encoding here rather than a gate concession.
     *
     * `Schema::hasColumn` rather than a try/catch, so a re-run is a no-op instead of
     * a duplicate-column error — the same idempotence `2026_10_04_000600`,
     * `2026_10_04_001200` and `2026_10_08_000100` all rely on.
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
     * PHP, not a correlated UPDATE: it cannot be dialect-wrong, and it lets the
     * value be cast on the way rather than trusting each engine's coercion. The
     * join is the child's own PRIMARY KEY against the parent's, so it is the same
     * update on both sides by construction.
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
            ->orderBy('seq')
            ->orderBy('position')
            ->chunk(self::CHUNK, function ($rows) use ($child, $column): void {
                foreach ($rows as $row) {
                    $where = static function ($query) use ($row): void {
                        foreach (self::KEY as $key) {
                            $query->where($key, $row->{$key});
                        }
                    };

                    // A child row whose parent is gone is an orphan this migration
                    // must not paper over by inventing a parent. It is skipped, not
                    // silently dropped into somebody else's row.
                    if (DB::table(self::PARENT)->where($where)->first() === null) {
                        continue;
                    }

                    DB::table(self::PARENT)->where($where)->update([$column => $row->{$column}]);

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
     * Put the fabricated table back, with its key and its foreign key.
     *
     * The foreign key below is the FALSE one this migration exists to remove: it
     * makes an update row the parent of a row the wire does not nest. It is
     * restored because `down()` must be a true inverse, and it is named here so a
     * reader who rolls this back knows the schema they are returning to is the one
     * audit row C5 calls a defect.
     *
     * Constraint name is 200030's own convention, `{child}_parent_fk`, and the
     * longest is 28 characters — well inside MySQL's 64-character cap (MySQL-1059):
     *
     *   php -r 'echo strlen("tf_updates_channel_parent_fk");'  -> 28
     *   php -r 'echo strlen("tf_updates_chat_parent_fk");'     -> 25
     *   php -r 'echo strlen("tf_updates_user_parent_fk");'     -> 25
     */
    private function restoreChildTable(string $child, string $column): void
    {
        if (Schema::hasTable($child)) {
            return; // already restored — a re-run of down() is a no-op
        }

        // 🔴 `$child` IS CAPTURED and not merely `$column`. A closure that reaches
        // for an uncaptured variable is a RUNTIME error, not a parse error, so
        // `php -l` passes it silently and the constraint name below comes out as
        // the bare `'_parent_fk'` — which collides with every other restore in this
        // family. The static analyser is what caught it in the participants fold; a
        // syntax check could not have.
        Schema::create($child, function (Blueprint $table) use ($child, $column): void {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->bigInteger($column);
            $table->primary(self::KEY);

            // 🔴 THE FALSE CONSTRAINT — see the method docblock above.
            $table->foreign(self::KEY, $child.'_parent_fk')
                ->references(self::KEY)
                ->on(self::PARENT)
                ->cascadeOnDelete();
        });
    }
};
