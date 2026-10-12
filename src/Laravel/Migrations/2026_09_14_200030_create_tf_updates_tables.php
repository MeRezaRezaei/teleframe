<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Hand-authored NF5 mirror for the UPDATES domain (owner verbatim ruling:
 * generation is banned; every update-surface fact is derived by hand from
 * schema/sources/TL_telegram_v227.tl).
 *
 * tf_updates            — Update* fact log. PK (account_id, seq, position):
 *                         account_id is the tenant/credential parent key,
 *                         seq is the delivering container's seq (0 for the
 *                         seq-less short dials: updateShortMessage & co.), and
 *                         position is the update's index within that
 *                         container (0 for a lone update). 'constructor'
 *                         discriminates the named TL ctor (updateNewMessage,
 *                         updateDeleteMessages, updateUserStatus, ...).
 *                         Optional flag-gated fields become 1:1 child tables
 *                         (zero nullable, zero JSON); Vector<int> message ids
 *                         (updateDeleteMessages) become the 1:N
 *                         tf_updates_messages.
 * tf_update_routing     — the routing/dial receipt mirror the owner named:
 *                         one row per delivered container (updates /
 *                         updatesCombined / updatesTooLong / updateShort /
 *                         updateShortMessage / updateShortChatMessage /
 *                         updateShortSentMessage). 'msg_id' is the transport
 *                         envelope message id (0 for non-pushed receipts) —
 *                         the quick-ack registry key the wire layer reads.
 * tf_update_state       — updates.State mirror (updates#a56c2a3e), the
 *                         updates.getState / getDifference seam: one row per
 *                         account carrying pts/qts/date/seq/unread_count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_updates', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->primary(['account_id', 'seq', 'position']);
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });

        $this->createUpdateChildren();
        $this->createUpdateRouting();
        $this->createUpdateState();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_update_state');
        foreach (['tf_update_routing_ack', 'tf_update_routing_seq_start', 'tf_update_routing_date'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_update_routing');
        foreach (self::CHILD_TABLES as $t) {
            Schema::dropIfExists($t);
        }
        Schema::dropIfExists('tf_updates');
    }

    private const CHILD_TABLES = [
        'tf_updates_pts',
        'tf_updates_pts_count',
        'tf_updates_qts',
        'tf_updates_date',
        'tf_updates_message',
        'tf_updates_messages',
        'tf_updates_channel',
        'tf_updates_chat',
        'tf_updates_user',
        'tf_updates_peer',
        'tf_updates_from',
        'tf_updates_max_id',
        'tf_updates_still_unread_count',
        'tf_updates_top_msg_id',
        'tf_updates_folder_id',
        'tf_updates_status',
        'tf_updates_action',
    ];

    private static function factColumns(Blueprint $table): void
    {
        $table->unsignedBigInteger('account_id');
        $table->integer('seq');
        $table->smallInteger('position');
    }

    private static function linkChild(Blueprint $table): void
    {
        $table->foreign(['account_id', 'seq', 'position'])
            ->references(['account_id', 'seq', 'position'])
            ->on('tf_updates')
            ->cascadeOnDelete();
    }

    private function createUpdateChildren(): void
    {
        // updateNewMessage / updateEditMessage ...: pts + pts_count, 1:1.
        Schema::create('tf_updates_pts', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('pts');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        Schema::create('tf_updates_pts_count', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('pts_count');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // Encrypted/message-media updates add qts.
        Schema::create('tf_updates_qts', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('qts');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // updateUserStatus / updateChatUserTyping / updateShortDate ...: date.
        Schema::create('tf_updates_date', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('date');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // Embedded message fact (updateNewMessage / updateEditMessage /
        // updateNewChannelMessage / updateEditChannelMessage): peer pair +
        // the native message id. The message's own domain facts live in the
        // messages mirror (sibling domain) — this child is the routing link.
        Schema::create('tf_updates_message', function (Blueprint $table) {
            self::factColumns($table);
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->bigInteger('message_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // updateDeleteMessages: Vector<int> message ids, 1:N by vector slot.
        Schema::create('tf_updates_messages', function (Blueprint $table) {
            self::factColumns($table);
            $table->smallInteger('message_position');
            $table->bigInteger('message_id');
            $table->primary(['account_id', 'seq', 'position', 'message_position']);
            self::linkChild($table);
        });
        // Channel-scoped updates (updateChannelTooLong / updateNewChannelMessage).
        Schema::create('tf_updates_channel', function (Blueprint $table) {
            self::factColumns($table);
            $table->bigInteger('channel_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // Group-chat-scoped updates (updateChatUserTyping).
        Schema::create('tf_updates_chat', function (Blueprint $table) {
            self::factColumns($table);
            $table->bigInteger('chat_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // User-scoped updates (updateUserStatus).
        Schema::create('tf_updates_user', function (Blueprint $table) {
            self::factColumns($table);
            $table->bigInteger('user_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // Peer-scoped updates (updateReadHistoryInbox/Outbox/ChannelInbox/
        // updateDialogUnreadMark ...).
        Schema::create('tf_updates_peer', function (Blueprint $table) {
            self::factColumns($table);
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // from_id peer (updateChatUserTyping / updateChatParticipant / ...).
        Schema::create('tf_updates_from', function (Blueprint $table) {
            self::factColumns($table);
            $table->tinyInteger('peer_type');
            $table->bigInteger('peer_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        Schema::create('tf_updates_max_id', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('max_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        Schema::create('tf_updates_still_unread_count', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('still_unread_count');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        Schema::create('tf_updates_top_msg_id', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('top_msg_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        Schema::create('tf_updates_folder_id', function (Blueprint $table) {
            self::factColumns($table);
            $table->integer('folder_id');
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // UserStatus union (updateUserStatus.status): the wire-false fields
        // fill with 0/FALSE sentinels — ctor + per-ctor optionals only.
        Schema::create('tf_updates_status', function (Blueprint $table) {
            self::factColumns($table);
            $table->string('constructor', 64);
            $table->boolean('by_me')->default(false);
            $table->integer('expires')->default(0);
            $table->integer('was_online')->default(0);
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
        // SendMessageAction union (updateChatUserTyping.action): scalar fields
        // only; composite facts (DataJSON, TextWithEntities, RichMessage)
        // deferred to the messaging/typing domain.
        Schema::create('tf_updates_action', function (Blueprint $table) {
            self::factColumns($table);
            $table->string('constructor', 64);
            $table->integer('progress')->default(0);
            $table->text('emoticon')->default(DB::raw("('')"));
            $table->integer('msg_id')->default(0);
            $table->bigInteger('random_id')->default(0);
            $table->primary(['account_id', 'seq', 'position']);
            self::linkChild($table);
        });
    }

    private function createUpdateRouting(): void
    {
        Schema::create('tf_update_routing', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->string('constructor', 64);
            $table->bigInteger('msg_id');
            $table->primary(['account_id', 'seq', 'position']);
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });

        Schema::create('tf_update_routing_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->integer('date');
            $table->primary(['account_id', 'seq', 'position']);
            $table->foreign(['account_id', 'seq', 'position'])
                ->references(['account_id', 'seq', 'position'])
                ->on('tf_update_routing')
                ->cascadeOnDelete();
        });
        // updatesCombined only: the batch's seq_start.
        Schema::create('tf_update_routing_seq_start', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->integer('seq_start');
            $table->primary(['account_id', 'seq', 'position']);
            $table->foreign(['account_id', 'seq', 'position'])
                ->references(['account_id', 'seq', 'position'])
                ->on('tf_update_routing')
                ->cascadeOnDelete();
        });
        // Quick-ack disposition: the wire layer flips 'acked' once msg_ack is
        // emitted for the receipt's msg_id.
        Schema::create('tf_update_routing_ack', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('seq');
            $table->smallInteger('position');
            $table->boolean('acked')->default(false);
            $table->integer('acked_at')->default(0);
            $table->primary(['account_id', 'seq', 'position']);
            $table->foreign(['account_id', 'seq', 'position'])
                ->references(['account_id', 'seq', 'position'])
                ->on('tf_update_routing')
                ->cascadeOnDelete();
        });
    }

    private function createUpdateState(): void
    {
        // updates.State (updates#a56c2a3e): the getState/getDifference seam.
        Schema::create('tf_update_state', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('pts');
            $table->integer('qts');
            $table->integer('date');
            $table->integer('seq');
            $table->integer('unread_count');
            $table->primary('account_id');
            $table->foreign('account_id')->references('id')->on('telegram_accounts')->cascadeOnDelete();
        });
    }
};
