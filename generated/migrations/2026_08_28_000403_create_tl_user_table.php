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
        Schema::create('tl_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_user_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user')->cascadeOnDelete();
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
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('username')->nullable();
            $table->text('phone')->nullable();
            $table->uuid('photo')->nullable();
            $table->uuid('status')->nullable();
            $table->integer('bot_info_version')->nullable();
            $table->text('bot_inline_placeholder')->nullable();
            $table->text('lang_code')->nullable();
            $table->uuid('emoji_status')->nullable();
            $table->uuid('stories_max_id')->nullable();
            $table->uuid('color')->nullable();
            $table->uuid('profile_color')->nullable();
            $table->integer('bot_active_users')->nullable();
            $table->bigInteger('bot_verification_icon')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_user_user__restriction_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_user_user')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5eadffaad6686c7e1a0f');
        });
        Schema::create('tl_user_user__usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_user_user')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e4899ecc287c00863477');
        });
        Schema::create('tl_user_user_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_user')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_user_user_empty');
        Schema::dropIfExists('tl_user_user__usernames');
        Schema::dropIfExists('tl_user_user__restriction_reason');
        Schema::dropIfExists('tl_user_user');
        Schema::dropIfExists('tl_user');
    }
};
