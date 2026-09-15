<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * NF5 mirror — channels domain (Task 2 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl. The channel / channelForbidden
 * ctors declare `= Chat;` but are routed to their own table via
 * Naming::CTOR_DOMAIN_OVERRIDES ('channel' → channels) — tf_channels is the
 * peer FK target for peer_type 3 (PeerShapeTool::PEER_CHANNEL).
 *
 * - id is a signed BIGINT: telegram channel ids are negative.
 * - title + access_hash (required by channelForbidden, always present) are
 *   inline; date inline for ctor parity (channel). flags.?true → BOOLEAN
 *   NOT NULL DEFAULT FALSE, derived from the channel ctor flag list.
 * - Optional/flag-gated scalar/object/vector facts → 1:1 object children or
 *   1:N positioned children exactly like the tf_users surface (photo, rights
 *   unions, usernames vector, PeerColor color/profile_color, emoji_status ...).
 * - NO FK constraints here — cross-domain wiring is the Task-8 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createChannels();
        $this->createChannelChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_channels_until_date');
        Schema::dropIfExists('tf_channels_linked_monoforum_id');
        Schema::dropIfExists('tf_channels_send_paid_messages_stars');
        Schema::dropIfExists('tf_channels_bot_verification_icon');
        Schema::dropIfExists('tf_channels_subscription_until_date');
        Schema::dropIfExists('tf_channels_level');
        Schema::dropIfExists('tf_channels_emoji_status');
        Schema::dropIfExists('tf_channels_profile_color');
        Schema::dropIfExists('tf_channels_color');
        Schema::dropIfExists('tf_channels_stories_max_id');
        Schema::dropIfExists('tf_channels_usernames');
        Schema::dropIfExists('tf_channels_participants_count');
        Schema::dropIfExists('tf_channels_default_banned_rights');
        Schema::dropIfExists('tf_channels_banned_rights');
        Schema::dropIfExists('tf_channels_admin_rights');
        Schema::dropIfExists('tf_channels_restriction_reason');
        Schema::dropIfExists('tf_channels_photo');
        Schema::dropIfExists('tf_channels_username');
        Schema::dropIfExists('tf_channels');
    }

    private function createChannels(): void
    {
        Schema::create('tf_channels', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->text('title')->default(DB::raw("('')"));
            $table->bigInteger('access_hash')->default(0);
            $table->integer('date')->default(0);
            $table->boolean('creator')->default(false);
            $table->boolean('left')->default(false);
            $table->boolean('broadcast')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('megagroup')->default(false);
            $table->boolean('restricted')->default(false);
            $table->boolean('signatures')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('scam')->default(false);
            $table->boolean('has_link')->default(false);
            $table->boolean('has_geo')->default(false);
            $table->boolean('slowmode_enabled')->default(false);
            $table->boolean('call_active')->default(false);
            $table->boolean('call_not_empty')->default(false);
            $table->boolean('fake')->default(false);
            $table->boolean('gigagroup')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('join_to_send')->default(false);
            $table->boolean('join_request')->default(false);
            $table->boolean('forum')->default(false);
            $table->boolean('stories_hidden')->default(false);
            $table->boolean('stories_hidden_min')->default(false);
            $table->boolean('stories_unavailable')->default(false);
            $table->boolean('signature_profiles')->default(false);
            $table->boolean('autotranslation')->default(false);
            $table->boolean('broadcast_messages_allowed')->default(false);
            $table->boolean('monoforum')->default(false);
            $table->boolean('forum_tabs')->default(false);

            $table->primary(['account_id', 'id']);
        });
    }

    private function createChannelChildren(): void
    {
        // username — flags.6?string of the channel ctor (absent on channelForbidden).
        Schema::create('tf_channels_username', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('username');
            $table->primary(['account_id', 'id']);
        });

        // photo — ChatPhoto union (chatPhotoEmpty / chatPhoto).
        Schema::create('tf_channels_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('has_video')->default(false);
            $table->bigInteger('photo_id')->default(0);
            $table->text('stripped_thumb')->default(DB::raw("('')"));
            $table->integer('dc_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // restriction_reason — Vector<RestrictionReason> (1:N, positioned).
        Schema::create('tf_channels_restriction_reason', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->text('platform');
            $table->text('reason');
            $table->text('text');
            $table->primary(['account_id', 'id', 'position']);
        });

        // admin_rights / banned_rights / default_banned_rights — the chat
        // rights unions (ChatAdminRights / ChatBannedRights), flattened.
        Schema::create('tf_channels_admin_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('change_info')->default(false);
            $table->boolean('post_messages')->default(false);
            $table->boolean('edit_messages')->default(false);
            $table->boolean('delete_messages')->default(false);
            $table->boolean('ban_users')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('add_admins')->default(false);
            $table->boolean('anonymous')->default(false);
            $table->boolean('manage_call')->default(false);
            $table->boolean('other')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('post_stories')->default(false);
            $table->boolean('edit_stories')->default(false);
            $table->boolean('delete_stories')->default(false);
            $table->boolean('manage_direct_messages')->default(false);
            $table->boolean('manage_ranks')->default(false);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_banned_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('view_messages')->default(false);
            $table->boolean('send_messages')->default(false);
            $table->boolean('send_media')->default(false);
            $table->boolean('send_stickers')->default(false);
            $table->boolean('send_gifs')->default(false);
            $table->boolean('send_games')->default(false);
            $table->boolean('send_inline')->default(false);
            $table->boolean('embed_links')->default(false);
            $table->boolean('send_polls')->default(false);
            $table->boolean('change_info')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('send_photos')->default(false);
            $table->boolean('send_videos')->default(false);
            $table->boolean('send_roundvideos')->default(false);
            $table->boolean('send_audios')->default(false);
            $table->boolean('send_voices')->default(false);
            $table->boolean('send_docs')->default(false);
            $table->boolean('send_plain')->default(false);
            $table->boolean('edit_rank')->default(false);
            $table->boolean('send_reactions')->default(false);
            $table->integer('until_date')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_default_banned_rights', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('view_messages')->default(false);
            $table->boolean('send_messages')->default(false);
            $table->boolean('send_media')->default(false);
            $table->boolean('send_stickers')->default(false);
            $table->boolean('send_gifs')->default(false);
            $table->boolean('send_games')->default(false);
            $table->boolean('send_inline')->default(false);
            $table->boolean('embed_links')->default(false);
            $table->boolean('send_polls')->default(false);
            $table->boolean('change_info')->default(false);
            $table->boolean('invite_users')->default(false);
            $table->boolean('pin_messages')->default(false);
            $table->boolean('manage_topics')->default(false);
            $table->boolean('send_photos')->default(false);
            $table->boolean('send_videos')->default(false);
            $table->boolean('send_roundvideos')->default(false);
            $table->boolean('send_audios')->default(false);
            $table->boolean('send_voices')->default(false);
            $table->boolean('send_docs')->default(false);
            $table->boolean('send_plain')->default(false);
            $table->boolean('edit_rank')->default(false);
            $table->boolean('send_reactions')->default(false);
            $table->integer('until_date')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // participants_count — flags.17?int.
        Schema::create('tf_channels_participants_count', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('participants_count');
            $table->primary(['account_id', 'id']);
        });

        // usernames — Vector<Username> (1:N, positioned).
        Schema::create('tf_channels_usernames', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->text('constructor');
            $table->boolean('editable')->default(false);
            $table->boolean('active')->default(false);
            $table->text('username')->default(DB::raw("('')"));
            $table->primary(['account_id', 'id', 'position']);
        });

        // stories_max_id — RecentStory (single-ctor recentStory).
        Schema::create('tf_channels_stories_max_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('live')->default(false);
            $table->integer('max_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // color / profile_color — PeerColor union, flattened (vectors deferred).
        Schema::create('tf_channels_color', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->integer('color')->default(0);
            $table->bigInteger('background_emoji_id')->default(0);
            $table->bigInteger('collectible_id')->default(0);
            $table->bigInteger('gift_emoji_id')->default(0);
            $table->integer('accent_color')->default(0);
            $table->integer('dark_accent_color')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_profile_color', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->integer('color')->default(0);
            $table->bigInteger('background_emoji_id')->default(0);
            $table->bigInteger('collectible_id')->default(0);
            $table->bigInteger('gift_emoji_id')->default(0);
            $table->integer('accent_color')->default(0);
            $table->integer('dark_accent_color')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // emoji_status — EmojiStatus union, flattened (vectors deferred).
        Schema::create('tf_channels_emoji_status', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->bigInteger('document_id')->default(0);
            $table->integer('until')->default(0);
            $table->bigInteger('collectible_id')->default(0);
            $table->text('title')->default(DB::raw("('')"));
            $table->text('slug')->default(DB::raw("('')"));
            $table->bigInteger('pattern_document_id')->default(0);
            $table->integer('center_color')->default(0);
            $table->integer('edge_color')->default(0);
            $table->integer('pattern_color')->default(0);
            $table->integer('text_color')->default(0);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_level', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('level');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_subscription_until_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('subscription_until_date');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_bot_verification_icon', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('bot_verification_icon');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_send_paid_messages_stars', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('send_paid_messages_stars');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_channels_linked_monoforum_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('linked_monoforum_id');
            $table->primary(['account_id', 'id']);
        });

        // until_date — flags.16?int of the channelForbidden ctor.
        Schema::create('tf_channels_until_date', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('until_date');
            $table->primary(['account_id', 'id']);
        });
    }
};
