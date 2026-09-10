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
        Schema::create('tl_user_user', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
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
            $table->bigInteger('flags2')->nullable();
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
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('username')->nullable();
            $table->text('phone')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_4afcba88510fa66504933906');
            $table->bigInteger('status')->nullable();
            $table->index('status', 'ix_21d33b4abe86fc2b9cbb5026');
            $table->integer('bot_info_version')->nullable();
            $table->text('bot_inline_placeholder')->nullable();
            $table->text('lang_code')->nullable();
            $table->bigInteger('emoji_status')->nullable();
            $table->index('emoji_status', 'ix_dcdcda67333f55f6dea61f56');
            $table->bigInteger('stories_max_id')->nullable();
            $table->index('stories_max_id', 'ix_12508b98c1e1164f336fcc60');
            $table->bigInteger('color')->nullable();
            $table->index('color', 'ix_391155ccc8c67bf337f4c0fc');
            $table->bigInteger('profile_color')->nullable();
            $table->index('profile_color', 'ix_b66017f99a754d6bc4faca6c');
            $table->integer('bot_active_users')->nullable();
            $table->bigInteger('bot_verification_icon')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_201888545f0f4912c1b96d0d');
            $table->index('account_id', 'ix_7396b862d74b8a2d74f74676');
        });
        Schema::create('tl_user_user__restriction_reason', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_eeedb109b8cdf8bbc57a775f')->references('id')->on('tl_user_user')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5eadffaad6686c7e1a0f');
            $table->index('account_id', 'ix_2070a81786fc75a5547df11a');
        });
        Schema::create('tl_user_user__usernames', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_dd1ced71e252d8b1ff23f168')->references('id')->on('tl_user_user')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e4899ecc287c00863477');
            $table->index('account_id', 'ix_9fe740cb631070eb0eada112');
        });
        Schema::create('tl_user_user_empty', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b4fd38da25c985dff8572ad4');
            $table->index('account_id', 'ix_c1fc9bc13dadd99746ab5d08');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_user_empty');
        Schema::dropIfExists('tl_user_user__usernames');
        Schema::dropIfExists('tl_user_user__restriction_reason');
        Schema::dropIfExists('tl_user_user');
    }
};
