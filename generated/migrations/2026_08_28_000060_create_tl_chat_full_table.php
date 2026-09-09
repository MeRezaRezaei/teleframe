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
            $table->index('constructor_id', 'ix_30a5023f49cc7c06becfc519');
            $table->index('account_id', 'ix_5f3626b80290c96052e061be');
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
            $table->index('chat_photo', 'ix_7d1bc11e8fd75dc23fe5f235');
            $table->uuid('notify_settings');
            $table->index('notify_settings', 'ix_7b0c125b6f6cba13445d7933');
            $table->uuid('exported_invite')->nullable();
            $table->index('exported_invite', 'ix_0d867e9a4ae1679a8c76eafa');
            $table->bigInteger('migrated_from_chat_id')->nullable();
            $table->index('migrated_from_chat_id', 'ix_4cbd13f303c3d1d46d952718');
            $table->integer('migrated_from_max_id')->nullable();
            $table->integer('pinned_msg_id')->nullable();
            $table->uuid('stickerset')->nullable();
            $table->index('stickerset', 'ix_5f5819d482aea3db7d0ac173');
            $table->integer('available_min_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->bigInteger('linked_chat_id')->nullable();
            $table->index('linked_chat_id', 'ix_d98b12a1dd0296f6b8669e9d');
            $table->uuid('location')->nullable();
            $table->index('location', 'ix_2276ae42f013ebf44ae76ae8');
            $table->integer('slowmode_seconds')->nullable();
            $table->integer('slowmode_next_send_date')->nullable();
            $table->integer('stats_dc')->nullable();
            $table->integer('pts');
            $table->uuid('call')->nullable();
            $table->index('call', 'ix_a8fb6c5655bb9e1667d9c556');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('groupcall_default_join_as')->nullable();
            $table->index('groupcall_default_join_as', 'ix_73c833089a651ae093103df0');
            $table->text('theme_emoticon')->nullable();
            $table->integer('requests_pending')->nullable();
            $table->bigInteger('default_send_as')->nullable();
            $table->index('default_send_as', 'ix_e01766f629854fba01fb38cd');
            $table->uuid('available_reactions')->nullable();
            $table->index('available_reactions', 'ix_e7f96f18fa61f8493e806591');
            $table->integer('reactions_limit')->nullable();
            $table->uuid('stories')->nullable();
            $table->index('stories', 'ix_dce43be9bf21b381b1f31efe');
            $table->uuid('wallpaper')->nullable();
            $table->index('wallpaper', 'ix_fa9989ec84f65ddf6207d384');
            $table->integer('boosts_applied')->nullable();
            $table->integer('boosts_unrestrict')->nullable();
            $table->uuid('emojiset')->nullable();
            $table->index('emojiset', 'ix_015358d40313de1afa048963');
            $table->uuid('bot_verification')->nullable();
            $table->index('bot_verification', 'ix_625f0125a29947f3d5467617');
            $table->integer('stargifts_count')->nullable();
            $table->bigInteger('send_paid_messages_stars')->nullable();
            $table->uuid('main_tab')->nullable();
            $table->index('main_tab', 'ix_f40055ae969d750b4be8d45b');
            $table->bigInteger('guard_bot_id')->nullable();
            $table->index('guard_bot_id', 'ix_d8718104f15adbf55b910998');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de2259e1454a61247665886b');
            $table->unique(['account_id', 'default_send_as', 'tl_id'], 'ux_1c6355630cffb18c3a08');
        });
        Schema::create('tl_chat_full_channel_full__bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d288b72146138596654');
            $table->index('account_id', 'ix_1ba0839579d49df14e3cd605');
        });
        Schema::create('tl_chat_full_channel_full__pending_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d2087932aa555e7c6ed0');
            $table->index('account_id', 'ix_b5552dbddd22b32494fbe746');
        });
        Schema::create('tl_chat_full_channel_full__recent_requesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_channel_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9cb8e77dc17fb4cb9eae');
            $table->index('account_id', 'ix_fde2ebda3d384288efd36b12');
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
            $table->index('participants', 'ix_ba77904b6d164312dfa016c0');
            $table->uuid('chat_photo')->nullable();
            $table->index('chat_photo', 'ix_39fa60bf8c161a801c40aa6c');
            $table->uuid('notify_settings');
            $table->index('notify_settings', 'ix_26b6516eee96433908d75528');
            $table->uuid('exported_invite')->nullable();
            $table->index('exported_invite', 'ix_508a0382626b051d50153a66');
            $table->integer('pinned_msg_id')->nullable();
            $table->integer('folder_id')->nullable();
            $table->uuid('call')->nullable();
            $table->index('call', 'ix_ae26a71d108881c5e0054b70');
            $table->integer('ttl_period')->nullable();
            $table->bigInteger('groupcall_default_join_as')->nullable();
            $table->index('groupcall_default_join_as', 'ix_9206d6b5968196948128edb7');
            $table->text('theme_emoticon')->nullable();
            $table->integer('requests_pending')->nullable();
            $table->uuid('available_reactions')->nullable();
            $table->index('available_reactions', 'ix_40bbae2a374cbfd5656ffdd6');
            $table->integer('reactions_limit')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d7e244cfdcfaab21e2390b75');
            $table->unique(['account_id', 'groupcall_default_join_as', 'tl_id'], 'ux_77385bcf064c3293b617');
        });
        Schema::create('tl_chat_full_chat_full__bot_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e6f7aec53f3ed8ffb9c4');
            $table->index('account_id', 'ix_e3e7f8ad452a6abab241daa1');
        });
        Schema::create('tl_chat_full_chat_full__recent_requesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3d36b7c57e445e04b7ad');
            $table->index('account_id', 'ix_ad6212f8d489d80a2ba9964b');
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
