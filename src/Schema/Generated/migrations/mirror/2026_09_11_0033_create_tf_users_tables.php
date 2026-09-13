<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_users', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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

        Schema::create('tf_users_access_hash', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_first_name', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('first_name');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_last_name', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('last_name');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_username', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('username');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_phone', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('phone');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('has_video')->default(false);
        $table->boolean('personal')->default(false);
        $table->bigInteger('photo_id')->unsigned();
        $table->text('stripped_thumb');
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_status', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->integer('expires')->unsigned();
        $table->integer('was_online')->unsigned();
        $table->boolean('by_me')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_bot_info_version', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('bot_info_version')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_restriction_reason', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('platform');
        $table->text('reason');
        $table->text('text');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_bot_inline_placeholder', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('bot_inline_placeholder');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_lang_code', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('lang_code');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_emoji_status', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('document_id')->unsigned();
        $table->integer('until')->unsigned();
        $table->bigInteger('collectible_id')->unsigned();
        $table->text('title');
        $table->text('slug');
        $table->bigInteger('pattern_document_id')->unsigned();
        $table->integer('center_color')->unsigned();
        $table->integer('edge_color')->unsigned();
        $table->integer('pattern_color')->unsigned();
        $table->integer('text_color')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_usernames', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('editable')->default(false);
        $table->boolean('active')->default(false);
        $table->text('username');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_stories_max_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('live')->default(false);
        $table->integer('max_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_color', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->integer('color')->unsigned();
        $table->bigInteger('background_emoji_id')->unsigned();
        $table->bigInteger('collectible_id')->unsigned();
        $table->bigInteger('gift_emoji_id')->unsigned();
        $table->integer('accent_color')->unsigned();
        $table->integer('dark_accent_color')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_color_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_color_dark_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_profile_color', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->integer('color')->unsigned();
        $table->bigInteger('background_emoji_id')->unsigned();
        $table->bigInteger('collectible_id')->unsigned();
        $table->bigInteger('gift_emoji_id')->unsigned();
        $table->integer('accent_color')->unsigned();
        $table->integer('dark_accent_color')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_profile_color_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_profile_color_dark_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_users_bot_active_users', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('bot_active_users')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_bot_verification_icon', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('bot_verification_icon')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_users_send_paid_messages_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('send_paid_messages_stars')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_users');

        Schema::dropIfExists('tf_users_access_hash');

        Schema::dropIfExists('tf_users_first_name');

        Schema::dropIfExists('tf_users_last_name');

        Schema::dropIfExists('tf_users_username');

        Schema::dropIfExists('tf_users_phone');

        Schema::dropIfExists('tf_users_photo');

        Schema::dropIfExists('tf_users_status');

        Schema::dropIfExists('tf_users_bot_info_version');

        Schema::dropIfExists('tf_users_restriction_reason');

        Schema::dropIfExists('tf_users_bot_inline_placeholder');

        Schema::dropIfExists('tf_users_lang_code');

        Schema::dropIfExists('tf_users_emoji_status');

        Schema::dropIfExists('tf_users_usernames');

        Schema::dropIfExists('tf_users_stories_max_id');

        Schema::dropIfExists('tf_users_color');

        Schema::dropIfExists('tf_users_color_colors');

        Schema::dropIfExists('tf_users_color_dark_colors');

        Schema::dropIfExists('tf_users_profile_color');

        Schema::dropIfExists('tf_users_profile_color_colors');

        Schema::dropIfExists('tf_users_profile_color_dark_colors');

        Schema::dropIfExists('tf_users_bot_active_users');

        Schema::dropIfExists('tf_users_bot_verification_icon');

        Schema::dropIfExists('tf_users_send_paid_messages_stars');

        Schema::dropIfExists('tf_users_send_paid_messages_stars');

        Schema::dropIfExists('tf_users_bot_verification_icon');

        Schema::dropIfExists('tf_users_bot_active_users');

        Schema::dropIfExists('tf_users_profile_color_dark_colors');

        Schema::dropIfExists('tf_users_profile_color_colors');

        Schema::dropIfExists('tf_users_profile_color');

        Schema::dropIfExists('tf_users_color_dark_colors');

        Schema::dropIfExists('tf_users_color_colors');

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
};
