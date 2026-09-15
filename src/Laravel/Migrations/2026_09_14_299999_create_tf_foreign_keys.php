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
 *  - tf_messages_media (account_id, id)     → tf_messages  CASCADE —
 *      message 1:1 media child. The parent message key is carried INLINE as
 *      (account_id, id) — there is no separate message_id column (the child
 *      id IS the parent message id, mirroring the share-model layout).
 *  - tf_messages_entities (account_id, id)  → tf_messages  CASCADE —
 *      message 1:N entities vector child, same inline key shape.
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
        Schema::table('tf_messages_media', function (Blueprint $table) {
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_messages')
                ->cascadeOnDelete();
        });

        Schema::table('tf_messages_entities', function (Blueprint $table) {
            $table->foreign(['account_id', 'id'])
                ->references(['account_id', 'id'])
                ->on('tf_messages')
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

        Schema::table('tf_messages_entities', function (Blueprint $table) {
            $table->dropForeign(['account_id', 'id']);
        });

        Schema::table('tf_messages_media', function (Blueprint $table) {
            $table->dropForeign(['account_id', 'id']);
        });
    }
};
