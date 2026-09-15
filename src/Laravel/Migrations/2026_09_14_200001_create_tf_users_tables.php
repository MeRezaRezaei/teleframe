<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — users domain (Task 2 of the reverse-engineering plan).
 *
 * Hand-authored against TL_telegram_v227.tl (User union: userEmpty / user).
 *
 * - tf_users is the peer FK target for peer_type 1 (PeerShapeTool::PEER_USER).
 * - Multi-ctor parent: constructor discriminator 'userEmpty' / 'user'.
 * - id is a signed BIGINT (telegram user ids are positive, but the peer
 *   table must not restrict sign — user ids never go negative, chat/channel
 *   ids do, and all peer target tables share the same BIGINT column shape).
 * - Flags.?true → BOOLEAN NOT NULL DEFAULT FALSE (catalog "bools" list).
 * - Every other user fact is flag-gated → 1:1 child table keyed
 *   (account_id, id); row existence = fact existence. Optional object
 *   children (photo / status / emoji_status / color / profile_color /
 *   stories_max_id) are unions → constructor-discriminated, flattened with
 *   wire-false sentinels (no nullable, no json; bytes → lowercase hex TEXT;
 *   nested vectors inside PeerColor/EmojiStatus are deferred per the flat
 *   decomposer write path).
 * - Vector facts (restriction_reason, usernames) → 1:N positioned children.
 * - NO FK constraints here — cross-domain wiring is the Task-8 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createUsers();
        $this->createUserChildren();
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_users_send_paid_messages_stars');
        Schema::dropIfExists('tf_users_bot_verification_icon');
        Schema::dropIfExists('tf_users_bot_active_users');
        Schema::dropIfExists('tf_users_profile_color');
        Schema::dropIfExists('tf_users_color');
        Schema::dropIfExists('tf_users_stories_max_id');
        Schema::dropIfExists('tf_users_usernames');
        Schema::dropIfExists('tf_users_emoji_status');
        Schema::dropIfExists('tf_users_lang_code');
        Schema::dropIfExists('tf_users_bot_inline_placeholder');
        Schema::dropIfExists('tf_users_restriction_reason');
        Schema::dropIfExists('tf_users_bot_info_version');
        Schema::dropIfExists('tf_users_status');
        Schema::dropIfExists('tf_users_photo');
        Schema::dropIfExists('tf_users_phone');
        Schema::dropIfExists('tf_users_username');
        Schema::dropIfExists('tf_users_last_name');
        Schema::dropIfExists('tf_users_first_name');
        Schema::dropIfExists('tf_users_access_hash');
        Schema::dropIfExists('tf_users');
    }

    private function createUsers(): void
    {
        Schema::create('tf_users', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('self')->default(false);
            $table->boolean('contact')->default(false);
            $table->boolean('mutual_contact')->default(false);
            $table->boolean('deleted')->default(false);
            $table->boolean('bot')->default(false);
            $table->boolean('bot_chat_history')->default(false);
            $table->boolean('bot_nochats')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('restricted')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('bot_inline_geo')->default(false);
            $table->boolean('support')->default(false);
            $table->boolean('scam')->default(false);
            $table->boolean('apply_min_photo')->default(false);
            $table->boolean('fake')->default(false);
            $table->boolean('bot_attach_menu')->default(false);
            $table->boolean('premium')->default(false);
            $table->boolean('attach_menu_enabled')->default(false);
            $table->boolean('bot_can_edit')->default(false);
            $table->boolean('close_friend')->default(false);
            $table->boolean('stories_hidden')->default(false);
            $table->boolean('stories_unavailable')->default(false);
            $table->boolean('contact_require_premium')->default(false);
            $table->boolean('bot_business')->default(false);
            $table->boolean('bot_has_main_app')->default(false);
            $table->boolean('bot_forum_view')->default(false);
            $table->boolean('bot_forum_can_manage_topics')->default(false);
            $table->boolean('bot_can_manage_bots')->default(false);
            $table->boolean('bot_guestchat')->default(false);
            $table->boolean('bot_guard')->default(false);

            $table->primary(['account_id', 'id']);
        });
    }

    private function createUserChildren(): void
    {
        Schema::create('tf_users_access_hash', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('access_hash');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_first_name', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('first_name');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_last_name', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('last_name');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_username', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('username');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_phone', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('phone');
            $table->primary(['account_id', 'id']);
        });

        // photo — UserProfilePhoto union (userProfilePhotoEmpty / userProfilePhoto).
        Schema::create('tf_users_photo', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('has_video')->default(false);
            $table->boolean('personal')->default(false);
            $table->bigInteger('photo_id')->default(0);
            $table->text('stripped_thumb')->default('');
            $table->integer('dc_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // status — UserStatus union (userStatusEmpty / userStatusOnline /
        // userStatusOffline / userStatusRecently / userStatusLastWeek /
        // userStatusLastMonth).
        Schema::create('tf_users_status', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->integer('expires')->default(0);
            $table->integer('was_online')->default(0);
            $table->boolean('by_me')->default(false);
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_bot_info_version', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('bot_info_version');
            $table->primary(['account_id', 'id']);
        });

        // restriction_reason — Vector<RestrictionReason> (1:N, positioned).
        Schema::create('tf_users_restriction_reason', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->text('platform');
            $table->text('reason');
            $table->text('text');
            $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_bot_inline_placeholder', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('bot_inline_placeholder');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_lang_code', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('lang_code');
            $table->primary(['account_id', 'id']);
        });

        // emoji_status — EmojiStatus union (emojiStatusEmpty / emojiStatus /
        // emojiStatusCollectible / inputEmojiStatusCollectible); internal
        // color/dark_colors vectors are deferred to the flat write path.
        Schema::create('tf_users_emoji_status', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->bigInteger('document_id')->default(0);
            $table->integer('until')->default(0);
            $table->bigInteger('collectible_id')->default(0);
            $table->text('title')->default('');
            $table->text('slug')->default('');
            $table->bigInteger('pattern_document_id')->default(0);
            $table->integer('center_color')->default(0);
            $table->integer('edge_color')->default(0);
            $table->integer('pattern_color')->default(0);
            $table->integer('text_color')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // usernames — Vector<Username> (1:N, positioned).
        Schema::create('tf_users_usernames', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->smallInteger('position');
            $table->text('constructor');
            $table->boolean('editable')->default(false);
            $table->boolean('active')->default(false);
            $table->text('username')->default('');
            $table->primary(['account_id', 'id', 'position']);
        });

        // stories_max_id — RecentStory (single-ctor recentStory).
        Schema::create('tf_users_stories_max_id', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->text('constructor');
            $table->boolean('live')->default(false);
            $table->integer('max_id')->default(0);
            $table->primary(['account_id', 'id']);
        });

        // color / profile_color — PeerColor union (peerColor /
        // peerColorCollectible / inputPeerColorCollectible); colors /
        // dark_colors vectors deferred.
        Schema::create('tf_users_color', function (Blueprint $table) {
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

        Schema::create('tf_users_profile_color', function (Blueprint $table) {
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

        Schema::create('tf_users_bot_active_users', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->integer('bot_active_users');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_bot_verification_icon', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('bot_verification_icon');
            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_send_paid_messages_stars', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('id');
            $table->bigInteger('send_paid_messages_stars');
            $table->primary(['account_id', 'id']);
        });
    }
};
