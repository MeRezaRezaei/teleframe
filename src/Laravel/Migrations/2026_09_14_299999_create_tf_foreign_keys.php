<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hand-authored NF5 cross-domain FK wiring (plan Task 8) — the single place
 * where FKs that cross curated-domain boundaries live. This file ships last
 * in the curated dial (timestamp 2026_09_14_299999) after every reference
 * table exists, so the whole set applies in one migrate, Pg and sqlite alike.
 *
 * Declared (child key shape verified against the ACTUAL curated migrations):
 *  - tf_messages_media (account_id, message_peer_type, message_peer_id, id)
 *                                   → tf_messages  CASCADE —
 *      message 1:1 media child. The parent message key is carried INLINE as
 *      (account_id, peer_type, peer_id, id) — there is no separate message_id
 *      column (the child id IS the parent message id, share-model layout).
 *  - tf_messages_entities (account_id, peer_type, peer_id, id)
 *                                   → tf_messages  CASCADE —
 *      message 1:N entities vector child, same inline key shape.
 *  - tf_messages_reply_markup_rows_buttons (account_id, peer_type, peer_id, id,
 *      parent_position) → tf_messages_reply_markup_rows (account_id, peer_type,
 *      peer_id, id, position) CASCADE —
 *      keyboard-button child of a positioned markup row. The button carries
 *      the message key half INLINE (account_id, peer_type, peer_id, id), as
 *      the entities child does, and adds parent_position because a button's real
 *      parent is the ROW it hangs under: `parent_position` matches the row's
 *      `position`, while the button's own `position` is only its index WITHIN
 *      that row and must never be joined here — position alone is not unique
 *      within a message, which is why the id leg is pinned alongside it.
 *      CASCADE (not RESTRICT) for the same reason the entities child
 *      cascades: this is a POSITIONED VECTOR CHILD of the message fact, not a
 *      canonical peer ref. RESTRICT is reserved on this file for the peer case
 *      (participants → channels), where the child names an independently
 *      identified entity that must not be vanished out from under its rows;
 *      there is no such entity here — a button row has no life outside its
 *      markup row, and pruning that row must take its buttons with it, or a
 *      re-delivered message with a shorter keyboard would leave the dropped
 *      buttons orphaned under a re-placed row.
 *  - tf_channel_participants (account_id, channel_id) → tf_channels
 *      (account_id, id) RESTRICT — the participant's channel is a canonical
 *      peer ref; RESTRICT (not cascade) keeps a participant channel from
 *      being vanished out from under its rows.
 *
 * Deliberately NOT declared here:
 *  - the polymorphic peer pair peer_type/peer_id on tf_messages and
 *    tf_dialogs → tf_users / tf_chats / tf_channels. One composite FK cannot
 *    branch on peer_type, and the peer tables disagree in key column count
 *    (2 vs the triad's 3). Peer correctness is enforced in app code instead
 *    (MirrorFactWriter fkClues + PeerShapeTool), matching DeferredFkTest's
 *    legacy-track philosophy.
 *  - tf_messages_media → tf_documents / tf_photos. The curated media table
 *    has NO media_document_id / media_photo_id columns (nested Photo/Document
 *    objects were deferred by 2026_09_14_200012); their document/photo ids
 *    also ref ledger ids, not mirror ids. Revisit if that changes.
 *  - tf_updates_message / tf_updates_messages message_id and the stars
 *    msg_id refs — deliberate soft routing links (the row may be mirror-id
 *    or not yet mirrored); never FK'd.
 *
 * All FK columns are NOT NULL (NF5: no nullable FK columns).
 *
 * ## down() SPELLS EACH CONSTRAINT WHICHEVER WAY THE ENGINE UNDERSTANDS
 *
 * There is no single spelling of `dropForeign()` that works on both engines, and
 * choosing either one blindly breaks the other. MEASURED on both, on this dial:
 *
 *  - By NAME — `dropForeign('tf_reply_markup_buttons_row_fk')` compiles on MySQL
 *    to `alter table … drop foreign key tf_reply_markup_buttons_row_fk`. On
 *    sqlite it reaches `SQLiteGrammar::compileDropForeign()`, which refuses
 *    outright when the command carries no columns:
 *
 *      RuntimeException: This database driver does not support dropping foreign
 *      keys by name.
 *
 *    `Blueprint::dropIndexCommand()` populates `columns` ONLY for the array
 *    form, so the string form always arrives column-less and always throws.
 *    MEASURED here: `down()` threw on the SECOND of four constraints, after the
 *    first had already been dropped — a half-completed rollback with the
 *    migration row never deleted, which is the worst of both outcomes.
 *
 *  - By COLUMNS — `dropForeign(['account_id', 'channel_id'])` is the sqlite-safe
 *    spelling, and on MySQL it compiles to `drop foreign key` against a name
 *    DERIVED by `Blueprint::createIndexName()` as `{table}_{cols}_foreign`.
 *    Two of this file's four constraints have no such name, and the derived
 *    spellings are OVER MySQL's 64-character cap — MEASURED:
 *
 *      tf_channel_participants                    53   ok
 *      tf_messages_entities                      60   ok
 *      tf_messages_media                         73   ERROR 1059, too long
 *      tf_messages_reply_markup_rows_buttons     93   ERROR 1059, too long
 *
 *    (MySQL's limit, probed one character at a time on a scratch database, is
 *    exactly 64 accepted / 65 refused: `ERROR 1059 (42000): Identifier name '…'
 *    is too long`.) So porting the string form to columns is NOT the fix — it
 *    trades one engine's hard failure for the other's.
 *
 * The answer is the same shape the repo already settled twice, in
 * `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php` and
 * `2026_10_04_000200_retarget_tenant_foreign_keys.php`: READ the constraint back
 * off the live schema, drop BY NAME when the engine reports one, and fall back to
 * the column list only on an engine that reports none (sqlite, which records
 * neither constraint names nor any other provenance). `dropDeclaredForeign()`
 * below is that helper.
 *
 * ## WHY THE HELPER IS LOCAL AND NOT SHARED
 *
 * Deliberately private to this file, and it should not be lifted into a common
 * migration helper. sqlite achieves a column-form `dropForeign()` by REBUILDING
 * the table — disable FK enforcement, copy the rows, drop the original, recreate,
 * re-enable. A view that references the table is what makes that rebuild fail
 * hard: the rebuild DROPS the table first, and sqlite refuses the rename because
 * the view still points at the vanished name, leaving the install with neither
 * the table nor the view. MEASURED by an earlier lane on this dial. A shared
 * helper would carry that trap to every table it touched, including the many that
 * have no view referencing them at all. The four constraints here are the whole
 * blast radius, so the helper is written once, here, and not generalised.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The three message FKs below are PEER-SCOPED, and that is not a
        // stylistic choice: `tf_messages`' primary key is
        // (account_id, peer_type, peer_id, id) because Telegram numbers messages
        // per chat, so a foreign key on (account_id, id) no longer names a
        // UNIQUE index on the parent and the driver rejects it outright
        // (SQLite: "foreign key mismatch - tf_messages_media referencing
        // tf_messages"; MySQL 8.4 and Postgres 17 refuse the DDL). Widening the
        // parent without widening these would have turned the three curated
        // CASCADE constraints this file exists for into a migrate-time failure.
        //
        // `tf_messages_media` is the one place the scope columns need a
        // `message_` PREFIX: that table already owns `peer_type`/`peer_id` for
        // a peer carried INSIDE its own payload, one of the MessageMedia union
        // constructors. Column NAMES need not match across a foreign key — only
        // the referenced columns must be unique on the parent — so the prefixed
        // local columns reference the parent's unprefixed ones. See
        // 2026_09_14_200012_create_tf_message_medias_tables.php.
        Schema::table('tf_messages_media', function (Blueprint $table) {
            $table->foreign(['account_id', 'message_peer_type', 'message_peer_id', 'id'], 'tf_messages_media_message_fk')
                ->references(['account_id', 'peer_type', 'peer_id', 'id'])
                ->on('tf_messages')
                ->cascadeOnDelete();
        });

        Schema::table('tf_messages_entities', function (Blueprint $table) {
            $table->foreign(['account_id', 'peer_type', 'peer_id', 'id'], 'tf_messages_entities_message_fk')
                ->references(['account_id', 'peer_type', 'peer_id', 'id'])
                ->on('tf_messages')
                ->cascadeOnDelete();
        });

        Schema::table('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
            // Named explicitly for the same reason the buttons primary key is
            // named in 2026_09_14_200010: Laravel's generated name for this
            // composite would be
            // tf_messages_reply_markup_rows_buttons_account_id_id_parent_position_foreign
            // — 74 characters — and MySQL/Postgres silently truncate
            // identifiers at 64, which would mangle the constraint the very
            // RelationalIntegrityTest relies on to find it by name. dropForeign()
            // below must be handed this same string, because it cannot
            // regenerate the truncated default.
            $table->foreign(['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'], 'tf_reply_markup_buttons_row_fk')
                ->references(['account_id', 'peer_type', 'peer_id', 'id', 'position'])
                ->on('tf_messages_reply_markup_rows')
                ->cascadeOnDelete();
        });

        Schema::table('tf_channel_participants', function (Blueprint $table) {
            $table->foreign(['account_id', 'channel_id'])
                ->references(['account_id', 'id'])
                ->on('tf_channels')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $this->dropDeclaredForeign(
            'tf_channel_participants',
            'tf_channels',
            ['account_id', 'channel_id'],
        );

        $this->dropDeclaredForeign(
            'tf_messages_reply_markup_rows_buttons',
            'tf_messages_reply_markup_rows',
            ['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'],
        );

        $this->dropDeclaredForeign(
            'tf_messages_entities',
            'tf_messages',
            ['account_id', 'peer_type', 'peer_id', 'id'],
        );

        $this->dropDeclaredForeign(
            'tf_messages_media',
            'tf_messages',
            ['account_id', 'message_peer_type', 'message_peer_id', 'id'],
        );
    }

    /**
     * Drop the constraint `up()` created on this table, spelled for the engine
     * that is actually answering.
     *
     * The constraint is identified by its PARENT TABLE and its column list, and
     * both are read back off the live schema rather than assumed — which matters
     * because three of these four tables carry a second foreign key that leads
     * with the same `account_id` column: the `…_acc_tenant_fk` leg
     * `2026_10_04_000700` added. Matching on columns alone would be ambiguous;
     * matching on parent table and columns together is not, and a table whose
     * constraint is already gone is left alone rather than made to fail.
     *
     * A table with no such constraint is a no-op, so `down()` is re-runnable: an
     * install where a previous attempt half-completed now finishes instead of
     * raising on the part that already landed.
     *
     * @param  list<string>  $columns  the constraint's local columns, in order
     */
    private function dropDeclaredForeign(string $table, string $parent, array $columns): void
    {
        $declared = $this->declaredForeign($table, $parent, $columns);

        if ($declared === null) {
            return;
        }

        $name = $declared['name'] ?? null;

        Schema::table($table, function (Blueprint $blueprint) use ($name, $columns): void {
            // By NAME when the engine reports one (MySQL, Postgres). The names are
            // the ones `up()` wrote, read back rather than retyped, so a
            // constraint that was renamed by hand still drops.
            if (is_string($name) && $name !== '') {
                $blueprint->dropForeign($name);

                return;
            }

            // By COLUMNS on an engine that reports no name (sqlite) — the only
            // spelling that works there. Note this is what the DERIVED name would
            // be on MySQL for two of these four constraints, and both derived names
            // are over 64 characters; see the docblock. This branch is unreachable
            // on an engine that names its constraints, which is exactly why the
            // over-long name cannot bite.
            $blueprint->dropForeign($columns);
        });
    }

    /**
     * The constraint on `$table` whose parent is `$parent` and whose columns are
     * exactly `$columns` — or null when there is none.
     *
     * `getForeignKeys()` is the schema builder's own read, so it answers from the
     * engine under the migration rather than from anything this file believes.
     * Its row shape is fixed and non-nullable on `foreign_table` and `columns`
     * (`name` IS nullable, which is the whole reason `dropDeclaredForeign()`
     * branches), so nothing here is defensively retyped.
     *
     * @param  list<string>  $columns
     * @return array{name: string|null, columns: list<string>, foreign_table: string}|null
     */
    private function declaredForeign(string $table, string $parent, array $columns): ?array
    {
        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($table) as $foreign) {
            if ($foreign['foreign_table'] !== $parent) {
                continue;
            }

            $declared = array_map('strval', $foreign['columns']);

            if ($declared !== $columns) {
                continue;
            }

            return $foreign;
        }

        return null;
    }
};
