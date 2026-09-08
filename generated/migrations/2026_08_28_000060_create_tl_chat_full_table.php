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
        Schema::create('tl_chat_full', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_full_channel_full', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_full')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_view_participants')->default(false);
            $table->boolean('can_set_username')->default(false);
            $table->boolean('can_set_stickers')->default(false);
            $table->boolean('hidden_prehistory')->default(false);
            $table->boolean('can_set_location')->default(false);
            $table->boolean('has_scheduled')->default(false);
            $table->boolean('can_view_stats')->default(false);
            $table->boolean('blocked')->default(false);
            $table->bigInteger('flags2')->nullable();
            $table->boolean('can_delete_channel')->default(false);
            $table->boolean('antispam')->default(false);
            $table->boolean('participants_hidden')->default(false);
            $table->boolean('translations_disabled')->default(false);
            $table->boolean('stories_pinned_available')->default(false);
            $table->boolean('view_forum_as_messages')->default(false);
            $table->boolean('restricted_sponsored')->default(false);
            $table->boolean('can_view_revenue')->default(false);
            $table->boolean('paid_media_allowed')->default(false);
            $table->boolean('can_view_stars_revenue')->default(false);
            $table->boolean('paid_reactions_available')->default(false);
            $table->boolean('stargifts_available')->default(false);
            $table->boolean('paid_messages_available')->default(false);
            $table->bigInteger('tl_id');
            $table->text('about');
            $table->integer('participants_count')->nullable();
            $table->integer('admins_count')->nullable();
            $table->integer('kicked_count')->nullable();
            $table->integer('banned_count')->nullable();
            $table->integer('online_count')->nullable();
            $table->integer('read_inbox_max_id');
            $table->integer('read_outbox_max_id');
            $table->integer('unread_count');
            $table->uuid('chat_photo');
            $table->uuid('notify_settings');
            $table->uuid('exported_invite')->nullable();
            $table->bigInteger('migrated_from_chat_id')->nullable();
            $table->integer('migrated_from_max_id')->nullable();
            $table->integer('pinned_msg_id')->nullable();
            $table->uuid('stickerset')->nullable();
            $table->integer('available_min_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->bigInteger('linked_chat_id')->nullable();
            $table->uuid('location')->nullable();
            $table->integer('slowmode_seconds')->nullable();
            $table->integer('slowmode_next_send_date')->nullable();
            $table->integer('stats_dc')->nullable();
            $table->integer('pts');
            $table->uuid('call')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->uuid('groupcall_default_join_as')->nullable();
            $table->text('theme_emoticon')->nullable();
            $table->integer('requests_pending')->nullable();
            $table->uuid('default_send_as')->nullable();
            $table->uuid('available_reactions')->nullable();
            $table->integer('reactions_limit')->nullable();
            $table->uuid('stories')->nullable();
            $table->uuid('wallpaper')->nullable();
            $table->integer('boosts_applied')->nullable();
            $table->integer('boosts_unrestrict')->nullable();
            $table->uuid('emojiset')->nullable();
            $table->uuid('bot_verification')->nullable();
            $table->integer('stargifts_count')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->uuid('main_tab')->nullable();
            $table->bigInteger('guard_bot_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_full_channel_full__bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6d288b72146138596654');
        });
        Schema::create('tl_chat_full_channel_full__pending_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d2087932aa555e7c6ed0');
        });
        Schema::create('tl_chat_full_channel_full__recent_requesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9cb8e77dc17fb4cb9eae');
        });
        Schema::create('tl_chat_full_chat_full', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_full')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('can_set_username')->default(false);
            $table->boolean('has_scheduled')->default(false);
            $table->boolean('translations_disabled')->default(false);
            $table->bigInteger('tl_id');
            $table->text('about');
            $table->uuid('participants');
            $table->uuid('chat_photo')->nullable();
            $table->uuid('notify_settings');
            $table->uuid('exported_invite')->nullable();
            $table->integer('pinned_msg_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->uuid('call')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->uuid('groupcall_default_join_as')->nullable();
            $table->text('theme_emoticon')->nullable();
            $table->integer('requests_pending')->nullable();
            $table->uuid('available_reactions')->nullable();
            $table->integer('reactions_limit')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chat_full_chat_full__bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e6f7aec53f3ed8ffb9c4');
        });
        Schema::create('tl_chat_full_chat_full__recent_requesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3d36b7c57e445e04b7ad');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_full_chat_full__recent_requesters');
        Schema::dropIfExists('tl_chat_full_chat_full__bot_info');
        Schema::dropIfExists('tl_chat_full_chat_full');
        Schema::dropIfExists('tl_chat_full_channel_full__recent_requesters');
        Schema::dropIfExists('tl_chat_full_channel_full__pending_suggestions');
        Schema::dropIfExists('tl_chat_full_channel_full__bot_info');
        Schema::dropIfExists('tl_chat_full_channel_full');
        Schema::dropIfExists('tl_chat_full');
    }
};
