<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — messages domain (curated dial, Task 3 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl (Message union) + the ingest
 * write-path contract (MirrorFactDecomposer: parent rows carry account_id +
 * constructor; children are tf_{parent}_{fact} keyed (account_id, id[,
 * position])).
 *
 * - Content messages ↔ service messages split into tf_messages subject-row
 *   (messageEmpty / message) and tf_messages_service (messageService), the
 *   two-column-identical pair MessagesUnion relies on.
 * - Peer shapes are inline {field}_type / {field}_id pairs (1=user, 2=chat,
 *   3=channel per PeerShapeTool). Signed BIGINT for peer ids: telegram chat /
 *   channel ids are negative — the old generated mirror stored them UNSIGNED
 *   (data corruption for < 0 ids); this surface fixes that.
 * - flags.?true → BOOLEAN NOT NULL DEFAULT FALSE. No nullable, no json (bytes
 *   → lowercase hex TEXT), no auto-increment. Composite PK (account_id, id).
 * - messageEmpty has no payload beyond id, so peer/date/message carry type
 *   defaults; row existence = fact existence.
 * - FK constraints are NOT declared here — cross-domain wiring (account →
 *   telegram_accounts, peer_id → tf_users/tf_chats/tf_channels, media →
 *   tf_documents/tf_photos) is the Task-8 299999 migration after the
 *   worktrees merge.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createMessages();
        $this->createMessageChildren();
        $this->createServiceMessages();
        $this->createServiceMessageChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages_service_ttl_period');
        Schema::dropIfExists('tf_messages_service_reactions');
        Schema::dropIfExists('tf_messages_service_reply_to');
        Schema::dropIfExists('tf_messages_service_saved_peer_id');
        Schema::dropIfExists('tf_messages_service_from_id');
        Schema::dropIfExists('tf_messages_service');
        Schema::dropIfExists('tf_messages_reply_markup_rows_buttons');
        Schema::dropIfExists('tf_messages_reply_markup_rows');
        Schema::dropIfExists('tf_messages_reply_markup');
        Schema::dropIfExists('tf_messages_suggested_post');
        Schema::dropIfExists('tf_messages_factcheck');
        Schema::dropIfExists('tf_messages_reactions');
        Schema::dropIfExists('tf_messages_restriction_reason');
        Schema::dropIfExists('tf_messages_summary_from_language');
        Schema::dropIfExists('tf_messages_schedule_repeat_period');
        Schema::dropIfExists('tf_messages_paid_message_stars');
        Schema::dropIfExists('tf_messages_report_delivery_until_date');
        Schema::dropIfExists('tf_messages_effect');
        Schema::dropIfExists('tf_messages_quick_reply_shortcut_id');
        Schema::dropIfExists('tf_messages_ttl_period');
        Schema::dropIfExists('tf_messages_post_author');
        Schema::dropIfExists('tf_messages_edit_date');
        Schema::dropIfExists('tf_messages_grouped_id');
        Schema::dropIfExists('tf_messages_replies');
        Schema::dropIfExists('tf_messages_forwards');
        Schema::dropIfExists('tf_messages_views');
        Schema::dropIfExists('tf_messages_reply_to');
        Schema::dropIfExists('tf_messages_guestchat_via_from');
        Schema::dropIfExists('tf_messages_via_business_bot_id');
        Schema::dropIfExists('tf_messages_via_bot_id');
        Schema::dropIfExists('tf_messages_fwd_from');
        Schema::dropIfExists('tf_messages_saved_peer_id');
        Schema::dropIfExists('tf_messages_from_rank');
        Schema::dropIfExists('tf_messages_from_boosts_applied');
        Schema::dropIfExists('tf_messages_from_id');
        Schema::dropIfExists('tf_messages');
    }

    private function createMessages(): void
    {
        Schema::create('tf_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->integer('date')->default(0);
            $table->text('message')->default('');
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('from_scheduled')->default(false);
            $table->boolean('legacy')->default(false);
            $table->boolean('edit_hide')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->boolean('offline')->default(false);
            $table->boolean('video_processing_pending')->default(false);
            $table->boolean('paid_suggested_post_stars')->default(false);
            $table->boolean('paid_suggested_post_ton')->default(false);

            $table->primary(['account_id', 'id']);
            $table->index(['account_id', 'peer_type', 'peer_id']);
        });
    }

    private function createMessageChildren(): void
    {
        // from_id — inline peer pair child (Peer payload field).
        Schema::create('tf_messages_from_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_from_boosts_applied', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('from_boosts_applied');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_from_rank', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('from_rank');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_saved_peer_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('saved_peer_id_type')->default(0);
            $table->bigInteger('saved_peer_id_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // fwd_from — single-ctor MessageFwdHeader flattened 1:1.
        Schema::create('tf_messages_fwd_from', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('imported')->default(false);
            $table->boolean('saved_out')->default(false);
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->text('from_name')->default('');
            $table->integer('date');
            $table->integer('channel_post')->default(0);
            $table->text('post_author')->default('');
            $table->tinyInteger('saved_from_peer_type')->default(0);
            $table->bigInteger('saved_from_peer_id')->default(0);
            $table->integer('saved_from_msg_id')->default(0);
            $table->tinyInteger('saved_from_id_type')->default(0);
            $table->bigInteger('saved_from_id_id')->default(0);
            $table->text('saved_from_name')->default('');
            $table->integer('saved_date')->default(0);
            $table->text('psa_type')->default('');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_via_bot_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->bigInteger('via_bot_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_via_business_bot_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->bigInteger('via_business_bot_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_guestchat_via_from', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('guestchat_via_from_type')->default(0);
            $table->bigInteger('guestchat_via_from_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // reply_to — messageReplyHeader / messageReplyStoryHeader union,
        // constructor-discriminated, flattened 1:1. Nested reply_from /
        // reply_media / quote_entities cannot nest under the flat decomposer
        // write path (child columns are flat by construction) → deferred.
        Schema::create('tf_messages_reply_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->default(0);
            $table->tinyInteger('reply_to_peer_id_type')->default(0);
            $table->bigInteger('reply_to_peer_id_id')->default(0);
            $table->integer('reply_to_top_id')->default(0);
            $table->text('quote_text')->default('');
            $table->integer('quote_offset')->default(0);
            $table->integer('todo_item_id')->default(0);
            $table->text('poll_option')->default('');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('story_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_views', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('views');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_forwards', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('forwards');
            $table->primary(['account_id', 'id']);
        });

        // replies — single-ctor messageReplies flattened. recent_repliers
        // vector is unkeyable under the flat write path → deferred.
        Schema::create('tf_messages_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('comments')->default(false);
            $table->integer('replies');
            $table->integer('replies_pts');
            $table->bigInteger('channel_id')->default(0);
            $table->integer('max_id')->default(0);
            $table->integer('read_max_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_edit_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('edit_date');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_post_author', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('post_author');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_grouped_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->bigInteger('grouped_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_ttl_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('ttl_period');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_quick_reply_shortcut_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('quick_reply_shortcut_id');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_effect', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->bigInteger('effect');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_report_delivery_until_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('report_delivery_until_date');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_paid_message_stars', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->bigInteger('paid_message_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_schedule_repeat_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('schedule_repeat_period');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_summary_from_language', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('summary_from_language');
            $table->primary(['account_id', 'id']);
        });

        // restriction_reason — Vector<RestrictionReason> (1:N, positioned).
        Schema::create('tf_messages_restriction_reason', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('position');
            $table->text('platform');
            $table->text('reason');
            $table->text('text');
            $table->primary(['account_id', 'id', 'position']);
        });

        // reactions — single-ctor messageReactions raised to presence table;
        // results / recent_reactions / top_reactors are vectors of nested
        // objects → deferred to the flat write path's next cycle.
        Schema::create('tf_messages_reactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->primary(['account_id', 'id']);
        });

        // factcheck — single-ctor factCheck flattened.
        Schema::create('tf_messages_factcheck', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('need_check')->default(false);
            $table->text('country')->default('');
            $table->text('text')->default('');
            $table->bigInteger('hash');
            $table->primary(['account_id', 'id']);
        });

        // suggested_post — single-ctor suggestedPost; price StarsAmount
        // flattened to amount/nanos.
        Schema::create('tf_messages_suggested_post', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('accepted')->default(false);
            $table->boolean('rejected')->default(false);
            $table->integer('schedule_date')->default(0);
            $table->bigInteger('price_amount')->default(0);
            $table->integer('price_nanos')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // reply_markup — ReplyMarkup union (3 ctors) + positioned rows +
        // positioned buttons (flat KeyboardButton union, immediate fields).
        Schema::create('tf_messages_reply_markup', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('resize')->default(false);
            $table->boolean('single_use')->default(false);
            $table->boolean('selective')->default(false);
            $table->boolean('persistent')->default(false);
            $table->text('placeholder')->default('');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_reply_markup_rows', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('position');
            $table->text('constructor');
            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_messages_reply_markup_rows_buttons', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('position');
            $table->integer('parent_position');
            $table->text('constructor');
            $table->boolean('requires_password')->default(false);
            $table->boolean('same_peer')->default(false);
            $table->boolean('quiz')->default(false);
            $table->text('text')->default('');
            $table->text('url')->default('');
            $table->text('data')->default('');
            $table->text('query')->default('');
            $table->text('fwd_text')->default('');
            $table->integer('button_id')->default(0);
            $table->bigInteger('user_id')->default(0);
            $table->integer('max_quantity')->default(0);
            $table->text('copy_text')->default('');
            $table->primary(['account_id', 'id', 'position']);
        });
    }

    private function createServiceMessages(): void
    {
        Schema::create('tf_messages_service', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->text('constructor');
            $table->integer('date')->default(0);
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('reactions_are_possible')->default(false);
            $table->boolean('silent')->default(false);
            $table->boolean('post')->default(false);
            $table->boolean('legacy')->default(false);

            $table->primary(['account_id', 'id']);
            $table->index(['account_id', 'peer_type', 'peer_id']);
        });
    }

    private function createServiceMessageChildren(): void
    {
        Schema::create('tf_messages_service_from_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_saved_peer_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->tinyInteger('saved_peer_id_type')->default(0);
            $table->bigInteger('saved_peer_id_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_reply_to', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');
            $table->boolean('reply_to_scheduled')->default(false);
            $table->boolean('forum_topic')->default(false);
            $table->boolean('quote')->default(false);
            $table->boolean('reply_to_ephemeral')->default(false);
            $table->integer('reply_to_msg_id')->default(0);
            $table->tinyInteger('reply_to_peer_id_type')->default(0);
            $table->bigInteger('reply_to_peer_id_id')->default(0);
            $table->integer('reply_to_top_id')->default(0);
            $table->text('quote_text')->default('');
            $table->integer('quote_offset')->default(0);
            $table->integer('todo_item_id')->default(0);
            $table->text('poll_option')->default('');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->integer('story_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_reactions', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->boolean('min')->default(false);
            $table->boolean('can_see_list')->default(false);
            $table->boolean('reactions_as_tags')->default(false);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_ttl_period', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->integer('ttl_period');
            $table->primary(['account_id', 'id']);
        });
    }
};
