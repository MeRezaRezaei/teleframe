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
        Schema::create('tl_update', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_update_update_ai_compose_tones', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_attach_menu_bots', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_auto_save_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_business_connect', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('connection');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_callback_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->bigInteger('chat_instance');
            $table->binary('data')->nullable();
            $table->text('game_short_name')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_chat_boost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('boost');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_chat_invite_requester', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('date');
            $table->bigInteger('user_id');
            $table->text('about');
            $table->uuid('invite');
            $table->integer('qts');
            $table->bigInteger('query_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_commands', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->bigInteger('bot_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_commands__commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_commands')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ecd795dd925eeb96cf45');
        });
        Schema::create('tl_update_update_bot_delete_business_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->text('connection_id');
            $table->uuid('peer');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_delete_business_message__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_delete_business_message')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f3d08206c34d7c65db71');
        });
        Schema::create('tl_update_update_bot_edit_business_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('connection_id');
            $table->uuid('message');
            $table->uuid('reply_to_message')->nullable();
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_guest_chat_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->uuid('message');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_guest_chat_query__reference_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_guest_chat_query')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e852319c7f4754eb6462');
        });
        Schema::create('tl_update_update_bot_inline_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->text('query');
            $table->uuid('geo')->nullable();
            $table->uuid('peer_type')->nullable();
            $table->text('tl_offset');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_inline_send', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->text('query');
            $table->uuid('geo')->nullable();
            $table->text('tl_id');
            $table->uuid('msg_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_menu_button', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('bot_id');
            $table->uuid('button');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_message_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->integer('date');
            $table->uuid('actor');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_message_reaction__old_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_message_reaction')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5134f95c5bad08b25044');
        });
        Schema::create('tl_update_update_bot_message_reaction__new_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_message_reaction')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_383401fd82b29045d9a8');
        });
        Schema::create('tl_update_update_bot_message_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->integer('date');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_message_reactions__reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_bot_message_reactions')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_156dccda8f41d2c48859');
        });
        Schema::create('tl_update_update_bot_new_business_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('connection_id');
            $table->uuid('message');
            $table->uuid('reply_to_message')->nullable();
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_precheckout_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->binary('payload');
            $table->uuid('info')->nullable();
            $table->text('shipping_option_id')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_purchased_paid_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->text('payload');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_shipping_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->binary('payload');
            $table->uuid('shipping_address');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_stopped', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->integer('date');
            $table->uuid('stopped');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_webhook_j_s_o_n', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('data');
            $table->timestamps();
        });
        Schema::create('tl_update_update_bot_webhook_j_s_o_n_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('query_id');
            $table->uuid('data');
            $table->integer('timeout');
            $table->timestamps();
        });
        Schema::create('tl_update_update_business_bot_callback_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->text('connection_id');
            $table->uuid('message');
            $table->uuid('reply_to_message')->nullable();
            $table->bigInteger('chat_instance');
            $table->binary('data')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_available_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('available_min_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_message_forwards', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('tl_id');
            $table->integer('forwards');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_message_views', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('tl_id');
            $table->integer('views');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_chatlist')->default(false);
            $table->bigInteger('channel_id');
            $table->integer('date');
            $table->bigInteger('actor_id');
            $table->bigInteger('user_id');
            $table->uuid('prev_participant')->nullable();
            $table->uuid('new_participant')->nullable();
            $table->uuid('invite')->nullable();
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_read_messages_contents', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('saved_peer_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_read_messages_contents__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_channel_read_messages_contents')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6009a82644b9b5f93d41');
        });
        Schema::create('tl_update_update_channel_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->integer('pts')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_user_typing', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('from_id');
            $table->uuid('action');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_view_forum_as_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->uuid('enabled');
            $table->timestamps();
        });
        Schema::create('tl_update_update_channel_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->uuid('webpage');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_default_banned_rights', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('default_banned_rights');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('chat_id');
            $table->integer('date');
            $table->bigInteger('actor_id');
            $table->bigInteger('user_id');
            $table->uuid('prev_participant')->nullable();
            $table->uuid('new_participant')->nullable();
            $table->uuid('invite')->nullable();
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participant_add', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->bigInteger('user_id');
            $table->bigInteger('inviter_id');
            $table->integer('date');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participant_admin', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->bigInteger('user_id');
            $table->uuid('is_admin');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participant_delete', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->bigInteger('user_id');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participant_rank', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->bigInteger('user_id');
            $table->text('rank');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('participants');
            $table->timestamps();
        });
        Schema::create('tl_update_update_chat_user_typing', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->uuid('from_id');
            $table->uuid('action');
            $table->timestamps();
        });
        Schema::create('tl_update_update_config', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_contacts_reset', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_dc_options', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_dc_options__dc_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_dc_options')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_86780782b3f24def4744');
        });
        Schema::create('tl_update_update_delete_channel_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_channel_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2fb83d3317d7ace683dc');
        });
        Schema::create('tl_update_update_delete_group_call_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_group_call_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_group_call_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1e05cc704c944df0bbfc');
        });
        Schema::create('tl_update_update_delete_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_baec98f29922694c9424');
        });
        Schema::create('tl_update_update_delete_quick_reply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('shortcut_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_quick_reply_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('shortcut_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_quick_reply_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_quick_reply_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7471a3d3bdb3cdeecfd2');
        });
        Schema::create('tl_update_update_delete_scheduled_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_update_update_delete_scheduled_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_scheduled_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_170f2f2948b4b2a6c7ad');
        });
        Schema::create('tl_update_update_delete_scheduled_messages__sent_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_delete_scheduled_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b7a30fb9efb484d17072');
        });
        Schema::create('tl_update_update_dialog_filter', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_id');
            $table->uuid('filter')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_dialog_filter_order', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_dialog_filter_order__order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_dialog_filter_order')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_484395d508819f3a0675');
        });
        Schema::create('tl_update_update_dialog_filters', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_dialog_pinned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->integer('folder_id')->nullable();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_update_update_dialog_unread_mark', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unread')->default(false);
            $table->uuid('peer');
            $table->uuid('saved_peer_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_draft_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('saved_peer_id')->nullable();
            $table->uuid('draft');
            $table->timestamps();
        });
        Schema::create('tl_update_update_edit_channel_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_edit_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_emoji_game_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('info');
            $table->timestamps();
        });
        Schema::create('tl_update_update_encrypted_chat_typing', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_encrypted_messages_read', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('chat_id');
            $table->integer('max_date');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_update_update_encryption', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('chat');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_update_update_faved_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_folder_peers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_folder_peers__folder_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_folder_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_689439d4f7a4db784e06');
        });
        Schema::create('tl_update_update_geo_live_viewed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('live_story')->default(false);
            $table->uuid('peer')->nullable();
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_chain_blocks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('call');
            $table->integer('sub_chain_id');
            $table->integer('next_offset');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_chain_blocks__blocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_group_call_chain_blocks')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f7e741dddb16d3b91158');
        });
        Schema::create('tl_update_update_group_call_connection', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('presentation')->default(false);
            $table->uuid('params');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_encrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('call');
            $table->uuid('from_id');
            $table->binary('encrypted_message');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('call');
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_participants', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('call');
            $table->integer('version');
            $table->timestamps();
        });
        Schema::create('tl_update_update_group_call_participants__participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_group_call_participants')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_73b98c4db54010a7045b');
        });
        Schema::create('tl_update_update_inline_bot_callback_query', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('query_id');
            $table->bigInteger('user_id');
            $table->uuid('msg_id');
            $table->bigInteger('chat_instance');
            $table->binary('data')->nullable();
            $table->text('game_short_name')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_join_chat_web_view_decision', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->bigInteger('query_id');
            $table->uuid('result');
            $table->timestamps();
        });
        Schema::create('tl_update_update_lang_pack', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('difference');
            $table->timestamps();
        });
        Schema::create('tl_update_update_lang_pack_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->text('lang_code');
            $table->timestamps();
        });
        Schema::create('tl_update_update_login_token', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_managed_bot', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->bigInteger('bot_id');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_message_extended_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_message_extended_media__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_message_extended_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bf3bb5bf58e30b88f1e8');
        });
        Schema::create('tl_update_update_message_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('random_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_message_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer')->nullable();
            $table->integer('msg_id')->nullable();
            $table->integer('top_msg_id')->nullable();
            $table->bigInteger('poll_id');
            $table->uuid('poll')->nullable();
            $table->uuid('results');
            $table->timestamps();
        });
        Schema::create('tl_update_update_message_poll_vote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('poll_id');
            $table->uuid('peer');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_message_poll_vote__options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_message_poll_vote')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e9290c8be0ddbf7625c2');
        });
        Schema::create('tl_update_update_message_poll_vote__positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_message_poll_vote')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f646d45671f70c619a8f');
        });
        Schema::create('tl_update_update_message_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('saved_peer_id')->nullable();
            $table->uuid('reactions');
            $table->timestamps();
        });
        Schema::create('tl_update_update_mono_forum_no_paid_exception', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('exception')->default(false);
            $table->bigInteger('channel_id');
            $table->uuid('saved_peer_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_move_sticker_set_to_top', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('masks')->default(false);
            $table->boolean('emojis')->default(false);
            $table->bigInteger('stickerset');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_authorization', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unconfirmed')->default(false);
            $table->bigInteger('hash');
            $table->integer('date')->nullable();
            $table->text('device')->nullable();
            $table->text('location')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_bot_connection', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('confirmed')->default(false);
            $table->bigInteger('bot_id');
            $table->integer('date')->nullable();
            $table->text('device')->nullable();
            $table->text('location')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_channel_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_encrypted_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->integer('qts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_quick_reply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('quick_reply');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_scheduled_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_sticker_set', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('stickerset');
            $table->timestamps();
        });
        Schema::create('tl_update_update_new_story_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('story_id');
            $table->uuid('peer');
            $table->uuid('reaction');
            $table->timestamps();
        });
        Schema::create('tl_update_update_notify_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('notify_settings');
            $table->timestamps();
        });
        Schema::create('tl_update_update_paid_reaction_privacy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('private');
            $table->timestamps();
        });
        Schema::create('tl_update_update_peer_blocked', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('blocked_my_stories_from')->default(false);
            $table->uuid('peer_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_peer_history_t_t_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_peer_located', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_peer_located__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_peer_located')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2bc50a6afc8ed88dced5');
        });
        Schema::create('tl_update_update_peer_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('settings');
            $table->timestamps();
        });
        Schema::create('tl_update_update_peer_wallpaper', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('wallpaper_overridden')->default(false);
            $table->uuid('peer');
            $table->uuid('wallpaper')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_pending_join_requests', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('requests_pending');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pending_join_requests__recent_requesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pending_join_requests')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_45faa4045a730e13e9c2');
        });
        Schema::create('tl_update_update_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('phone_call');
            $table->timestamps();
        });
        Schema::create('tl_update_update_phone_call_signaling_data', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('phone_call_id');
            $table->binary('data');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_channel_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->bigInteger('channel_id');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_channel_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pinned_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_85cc0ed64944129433ba');
        });
        Schema::create('tl_update_update_pinned_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('folder_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_dialogs__order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pinned_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1865b1450e559a5dabd1');
        });
        Schema::create('tl_update_update_pinned_forum_topic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->uuid('peer');
            $table->integer('topic_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_forum_topics', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_forum_topics__order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pinned_forum_topics')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8d45ef01d86dd2ce0d1a');
        });
        Schema::create('tl_update_update_pinned_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->uuid('peer');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pinned_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_df81021b24bd4ca14086');
        });
        Schema::create('tl_update_update_pinned_saved_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_pinned_saved_dialogs__order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_pinned_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_176f5fb0285bfbe34999');
        });
        Schema::create('tl_update_update_privacy', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('tl_key');
            $table->timestamps();
        });
        Schema::create('tl_update_update_privacy__rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_privacy')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5cc69cca25ea949156c4');
        });
        Schema::create('tl_update_update_pts_changed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_quick_replies', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_quick_replies__quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_cfbbaebf6a70572ffdf0');
        });
        Schema::create('tl_update_update_quick_reply_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_channel_discussion_inbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->integer('top_msg_id');
            $table->integer('read_max_id');
            $table->bigInteger('broadcast_id')->nullable();
            $table->integer('broadcast_post')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_channel_discussion_outbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('top_msg_id');
            $table->integer('read_max_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_channel_inbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('folder_id')->nullable();
            $table->bigInteger('channel_id');
            $table->integer('max_id');
            $table->integer('still_unread_count');
            $table->integer('pts');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_channel_outbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->integer('max_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_featured_emoji_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_featured_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_history_inbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('folder_id')->nullable();
            $table->uuid('peer');
            $table->integer('top_msg_id')->nullable();
            $table->integer('max_id');
            $table->integer('still_unread_count');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_history_outbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('max_id');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_messages_contents', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_messages_contents__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_read_messages_contents')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_51018fd3f58049a798d0');
        });
        Schema::create('tl_update_update_read_mono_forum_inbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->uuid('saved_peer_id');
            $table->integer('read_max_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_mono_forum_outbox', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->uuid('saved_peer_id');
            $table->integer('read_max_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_read_stories', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('max_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_recent_emoji_statuses', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_recent_reactions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_recent_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_saved_dialog_pinned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_update_update_saved_gifs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_saved_reaction_tags', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_saved_ringtones', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_sent_phone_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('sent_code');
            $table->timestamps();
        });
        Schema::create('tl_update_update_sent_story_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('story_id');
            $table->uuid('reaction');
            $table->timestamps();
        });
        Schema::create('tl_update_update_service_notification', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('popup')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->integer('inbox_date')->nullable();
            $table->text('tl_type');
            $table->text('message');
            $table->uuid('media');
            $table->timestamps();
        });
        Schema::create('tl_update_update_service_notification__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_service_notification')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_18c8b7bb474773b27f0a');
        });
        Schema::create('tl_update_update_sms_job', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->text('job_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_star_gift_auction_state', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('gift_id');
            $table->uuid('state');
            $table->timestamps();
        });
        Schema::create('tl_update_update_star_gift_auction_user_state', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('gift_id');
            $table->uuid('user_state');
            $table->timestamps();
        });
        Schema::create('tl_update_update_star_gift_craft_fail', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_update_update_stars_balance', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('balance');
            $table->timestamps();
        });
        Schema::create('tl_update_update_stars_revenue_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('status');
            $table->timestamps();
        });
        Schema::create('tl_update_update_sticker_sets', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('masks')->default(false);
            $table->boolean('emojis')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_update_update_sticker_sets_order', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('masks')->default(false);
            $table->boolean('emojis')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_update_update_sticker_sets_order__order', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_sticker_sets_order')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c7f8fbe0321846f225ce');
        });
        Schema::create('tl_update_update_stories_stealth_mode', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('stealth_mode');
            $table->timestamps();
        });
        Schema::create('tl_update_update_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('story');
            $table->timestamps();
        });
        Schema::create('tl_update_update_story_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('random_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('theme');
            $table->timestamps();
        });
        Schema::create('tl_update_update_transcribed_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pending')->default(false);
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->bigInteger('transcription_id');
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user_emoji_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->uuid('emoji_status');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->text('first_name');
            $table->text('last_name');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user_name__usernames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_update_update_user_name')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_080cd1888e72406faf37');
        });
        Schema::create('tl_update_update_user_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->text('phone');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user_status', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->uuid('status');
            $table->timestamps();
        });
        Schema::create('tl_update_update_user_typing', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->integer('top_msg_id')->nullable();
            $table->uuid('action');
            $table->timestamps();
        });
        Schema::create('tl_update_update_web_browser_exception', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('delete')->default(false);
            $table->uuid('open_external_browser')->nullable();
            $table->uuid('exception');
            $table->timestamps();
        });
        Schema::create('tl_update_update_web_browser_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('open_external_browser')->default(false);
            $table->boolean('display_close_button')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_update_update_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->uuid('webpage');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->timestamps();
        });
        Schema::create('tl_update_update_web_view_result_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_update')->cascadeOnDelete();
            $table->bigInteger('query_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_update_update_web_view_result_sent');
        Schema::dropIfExists('tl_update_update_web_page');
        Schema::dropIfExists('tl_update_update_web_browser_settings');
        Schema::dropIfExists('tl_update_update_web_browser_exception');
        Schema::dropIfExists('tl_update_update_user_typing');
        Schema::dropIfExists('tl_update_update_user_status');
        Schema::dropIfExists('tl_update_update_user_phone');
        Schema::dropIfExists('tl_update_update_user_name__usernames');
        Schema::dropIfExists('tl_update_update_user_name');
        Schema::dropIfExists('tl_update_update_user_emoji_status');
        Schema::dropIfExists('tl_update_update_user');
        Schema::dropIfExists('tl_update_update_transcribed_audio');
        Schema::dropIfExists('tl_update_update_theme');
        Schema::dropIfExists('tl_update_update_story_i_d');
        Schema::dropIfExists('tl_update_update_story');
        Schema::dropIfExists('tl_update_update_stories_stealth_mode');
        Schema::dropIfExists('tl_update_update_sticker_sets_order__order');
        Schema::dropIfExists('tl_update_update_sticker_sets_order');
        Schema::dropIfExists('tl_update_update_sticker_sets');
        Schema::dropIfExists('tl_update_update_stars_revenue_status');
        Schema::dropIfExists('tl_update_update_stars_balance');
        Schema::dropIfExists('tl_update_update_star_gift_craft_fail');
        Schema::dropIfExists('tl_update_update_star_gift_auction_user_state');
        Schema::dropIfExists('tl_update_update_star_gift_auction_state');
        Schema::dropIfExists('tl_update_update_sms_job');
        Schema::dropIfExists('tl_update_update_service_notification__entities');
        Schema::dropIfExists('tl_update_update_service_notification');
        Schema::dropIfExists('tl_update_update_sent_story_reaction');
        Schema::dropIfExists('tl_update_update_sent_phone_code');
        Schema::dropIfExists('tl_update_update_saved_ringtones');
        Schema::dropIfExists('tl_update_update_saved_reaction_tags');
        Schema::dropIfExists('tl_update_update_saved_gifs');
        Schema::dropIfExists('tl_update_update_saved_dialog_pinned');
        Schema::dropIfExists('tl_update_update_recent_stickers');
        Schema::dropIfExists('tl_update_update_recent_reactions');
        Schema::dropIfExists('tl_update_update_recent_emoji_statuses');
        Schema::dropIfExists('tl_update_update_read_stories');
        Schema::dropIfExists('tl_update_update_read_mono_forum_outbox');
        Schema::dropIfExists('tl_update_update_read_mono_forum_inbox');
        Schema::dropIfExists('tl_update_update_read_messages_contents__messages');
        Schema::dropIfExists('tl_update_update_read_messages_contents');
        Schema::dropIfExists('tl_update_update_read_history_outbox');
        Schema::dropIfExists('tl_update_update_read_history_inbox');
        Schema::dropIfExists('tl_update_update_read_featured_stickers');
        Schema::dropIfExists('tl_update_update_read_featured_emoji_stickers');
        Schema::dropIfExists('tl_update_update_read_channel_outbox');
        Schema::dropIfExists('tl_update_update_read_channel_inbox');
        Schema::dropIfExists('tl_update_update_read_channel_discussion_outbox');
        Schema::dropIfExists('tl_update_update_read_channel_discussion_inbox');
        Schema::dropIfExists('tl_update_update_quick_reply_message');
        Schema::dropIfExists('tl_update_update_quick_replies__quick_replies');
        Schema::dropIfExists('tl_update_update_quick_replies');
        Schema::dropIfExists('tl_update_update_pts_changed');
        Schema::dropIfExists('tl_update_update_privacy__rules');
        Schema::dropIfExists('tl_update_update_privacy');
        Schema::dropIfExists('tl_update_update_pinned_saved_dialogs__order');
        Schema::dropIfExists('tl_update_update_pinned_saved_dialogs');
        Schema::dropIfExists('tl_update_update_pinned_messages__messages');
        Schema::dropIfExists('tl_update_update_pinned_messages');
        Schema::dropIfExists('tl_update_update_pinned_forum_topics__order');
        Schema::dropIfExists('tl_update_update_pinned_forum_topics');
        Schema::dropIfExists('tl_update_update_pinned_forum_topic');
        Schema::dropIfExists('tl_update_update_pinned_dialogs__order');
        Schema::dropIfExists('tl_update_update_pinned_dialogs');
        Schema::dropIfExists('tl_update_update_pinned_channel_messages__messages');
        Schema::dropIfExists('tl_update_update_pinned_channel_messages');
        Schema::dropIfExists('tl_update_update_phone_call_signaling_data');
        Schema::dropIfExists('tl_update_update_phone_call');
        Schema::dropIfExists('tl_update_update_pending_join_requests__recent_requesters');
        Schema::dropIfExists('tl_update_update_pending_join_requests');
        Schema::dropIfExists('tl_update_update_peer_wallpaper');
        Schema::dropIfExists('tl_update_update_peer_settings');
        Schema::dropIfExists('tl_update_update_peer_located__peers');
        Schema::dropIfExists('tl_update_update_peer_located');
        Schema::dropIfExists('tl_update_update_peer_history_t_t_l');
        Schema::dropIfExists('tl_update_update_peer_blocked');
        Schema::dropIfExists('tl_update_update_paid_reaction_privacy');
        Schema::dropIfExists('tl_update_update_notify_settings');
        Schema::dropIfExists('tl_update_update_new_story_reaction');
        Schema::dropIfExists('tl_update_update_new_sticker_set');
        Schema::dropIfExists('tl_update_update_new_scheduled_message');
        Schema::dropIfExists('tl_update_update_new_quick_reply');
        Schema::dropIfExists('tl_update_update_new_message');
        Schema::dropIfExists('tl_update_update_new_encrypted_message');
        Schema::dropIfExists('tl_update_update_new_channel_message');
        Schema::dropIfExists('tl_update_update_new_bot_connection');
        Schema::dropIfExists('tl_update_update_new_authorization');
        Schema::dropIfExists('tl_update_update_move_sticker_set_to_top');
        Schema::dropIfExists('tl_update_update_mono_forum_no_paid_exception');
        Schema::dropIfExists('tl_update_update_message_reactions');
        Schema::dropIfExists('tl_update_update_message_poll_vote__positions');
        Schema::dropIfExists('tl_update_update_message_poll_vote__options');
        Schema::dropIfExists('tl_update_update_message_poll_vote');
        Schema::dropIfExists('tl_update_update_message_poll');
        Schema::dropIfExists('tl_update_update_message_i_d');
        Schema::dropIfExists('tl_update_update_message_extended_media__extended_media');
        Schema::dropIfExists('tl_update_update_message_extended_media');
        Schema::dropIfExists('tl_update_update_managed_bot');
        Schema::dropIfExists('tl_update_update_login_token');
        Schema::dropIfExists('tl_update_update_lang_pack_too_long');
        Schema::dropIfExists('tl_update_update_lang_pack');
        Schema::dropIfExists('tl_update_update_join_chat_web_view_decision');
        Schema::dropIfExists('tl_update_update_inline_bot_callback_query');
        Schema::dropIfExists('tl_update_update_group_call_participants__participants');
        Schema::dropIfExists('tl_update_update_group_call_participants');
        Schema::dropIfExists('tl_update_update_group_call_message');
        Schema::dropIfExists('tl_update_update_group_call_encrypted_message');
        Schema::dropIfExists('tl_update_update_group_call_connection');
        Schema::dropIfExists('tl_update_update_group_call_chain_blocks__blocks');
        Schema::dropIfExists('tl_update_update_group_call_chain_blocks');
        Schema::dropIfExists('tl_update_update_group_call');
        Schema::dropIfExists('tl_update_update_geo_live_viewed');
        Schema::dropIfExists('tl_update_update_folder_peers__folder_peers');
        Schema::dropIfExists('tl_update_update_folder_peers');
        Schema::dropIfExists('tl_update_update_faved_stickers');
        Schema::dropIfExists('tl_update_update_encryption');
        Schema::dropIfExists('tl_update_update_encrypted_messages_read');
        Schema::dropIfExists('tl_update_update_encrypted_chat_typing');
        Schema::dropIfExists('tl_update_update_emoji_game_info');
        Schema::dropIfExists('tl_update_update_edit_message');
        Schema::dropIfExists('tl_update_update_edit_channel_message');
        Schema::dropIfExists('tl_update_update_draft_message');
        Schema::dropIfExists('tl_update_update_dialog_unread_mark');
        Schema::dropIfExists('tl_update_update_dialog_pinned');
        Schema::dropIfExists('tl_update_update_dialog_filters');
        Schema::dropIfExists('tl_update_update_dialog_filter_order__order');
        Schema::dropIfExists('tl_update_update_dialog_filter_order');
        Schema::dropIfExists('tl_update_update_dialog_filter');
        Schema::dropIfExists('tl_update_update_delete_scheduled_messages__sent_messages');
        Schema::dropIfExists('tl_update_update_delete_scheduled_messages__messages');
        Schema::dropIfExists('tl_update_update_delete_scheduled_messages');
        Schema::dropIfExists('tl_update_update_delete_quick_reply_messages__messages');
        Schema::dropIfExists('tl_update_update_delete_quick_reply_messages');
        Schema::dropIfExists('tl_update_update_delete_quick_reply');
        Schema::dropIfExists('tl_update_update_delete_messages__messages');
        Schema::dropIfExists('tl_update_update_delete_messages');
        Schema::dropIfExists('tl_update_update_delete_group_call_messages__messages');
        Schema::dropIfExists('tl_update_update_delete_group_call_messages');
        Schema::dropIfExists('tl_update_update_delete_channel_messages__messages');
        Schema::dropIfExists('tl_update_update_delete_channel_messages');
        Schema::dropIfExists('tl_update_update_dc_options__dc_options');
        Schema::dropIfExists('tl_update_update_dc_options');
        Schema::dropIfExists('tl_update_update_contacts_reset');
        Schema::dropIfExists('tl_update_update_config');
        Schema::dropIfExists('tl_update_update_chat_user_typing');
        Schema::dropIfExists('tl_update_update_chat_participants');
        Schema::dropIfExists('tl_update_update_chat_participant_rank');
        Schema::dropIfExists('tl_update_update_chat_participant_delete');
        Schema::dropIfExists('tl_update_update_chat_participant_admin');
        Schema::dropIfExists('tl_update_update_chat_participant_add');
        Schema::dropIfExists('tl_update_update_chat_participant');
        Schema::dropIfExists('tl_update_update_chat_default_banned_rights');
        Schema::dropIfExists('tl_update_update_chat');
        Schema::dropIfExists('tl_update_update_channel_web_page');
        Schema::dropIfExists('tl_update_update_channel_view_forum_as_messages');
        Schema::dropIfExists('tl_update_update_channel_user_typing');
        Schema::dropIfExists('tl_update_update_channel_too_long');
        Schema::dropIfExists('tl_update_update_channel_read_messages_contents__messages');
        Schema::dropIfExists('tl_update_update_channel_read_messages_contents');
        Schema::dropIfExists('tl_update_update_channel_participant');
        Schema::dropIfExists('tl_update_update_channel_message_views');
        Schema::dropIfExists('tl_update_update_channel_message_forwards');
        Schema::dropIfExists('tl_update_update_channel_available_messages');
        Schema::dropIfExists('tl_update_update_channel');
        Schema::dropIfExists('tl_update_update_business_bot_callback_query');
        Schema::dropIfExists('tl_update_update_bot_webhook_j_s_o_n_query');
        Schema::dropIfExists('tl_update_update_bot_webhook_j_s_o_n');
        Schema::dropIfExists('tl_update_update_bot_stopped');
        Schema::dropIfExists('tl_update_update_bot_shipping_query');
        Schema::dropIfExists('tl_update_update_bot_purchased_paid_media');
        Schema::dropIfExists('tl_update_update_bot_precheckout_query');
        Schema::dropIfExists('tl_update_update_bot_new_business_message');
        Schema::dropIfExists('tl_update_update_bot_message_reactions__reactions');
        Schema::dropIfExists('tl_update_update_bot_message_reactions');
        Schema::dropIfExists('tl_update_update_bot_message_reaction__new_reactions');
        Schema::dropIfExists('tl_update_update_bot_message_reaction__old_reactions');
        Schema::dropIfExists('tl_update_update_bot_message_reaction');
        Schema::dropIfExists('tl_update_update_bot_menu_button');
        Schema::dropIfExists('tl_update_update_bot_inline_send');
        Schema::dropIfExists('tl_update_update_bot_inline_query');
        Schema::dropIfExists('tl_update_update_bot_guest_chat_query__reference_messages');
        Schema::dropIfExists('tl_update_update_bot_guest_chat_query');
        Schema::dropIfExists('tl_update_update_bot_edit_business_message');
        Schema::dropIfExists('tl_update_update_bot_delete_business_message__messages');
        Schema::dropIfExists('tl_update_update_bot_delete_business_message');
        Schema::dropIfExists('tl_update_update_bot_commands__commands');
        Schema::dropIfExists('tl_update_update_bot_commands');
        Schema::dropIfExists('tl_update_update_bot_chat_invite_requester');
        Schema::dropIfExists('tl_update_update_bot_chat_boost');
        Schema::dropIfExists('tl_update_update_bot_callback_query');
        Schema::dropIfExists('tl_update_update_bot_business_connect');
        Schema::dropIfExists('tl_update_update_auto_save_settings');
        Schema::dropIfExists('tl_update_update_attach_menu_bots');
        Schema::dropIfExists('tl_update_update_ai_compose_tones');
        Schema::dropIfExists('tl_update');
    }
};
