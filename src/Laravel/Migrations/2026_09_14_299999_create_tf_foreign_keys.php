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
        Schema::table('tf_channel_participants', function (Blueprint $table) {
            $table->dropForeign(['account_id', 'channel_id']);
        });

        Schema::table('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
            $table->dropForeign('tf_reply_markup_buttons_row_fk');
        });

        Schema::table('tf_messages_entities', function (Blueprint $table) {
            $table->dropForeign('tf_messages_entities_message_fk');
        });

        Schema::table('tf_messages_media', function (Blueprint $table) {
            $table->dropForeign('tf_messages_media_message_fk');
        });
    }
};
