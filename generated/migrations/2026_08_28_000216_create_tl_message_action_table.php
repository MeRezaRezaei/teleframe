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
        Schema::create('tl_message_action', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_action_message_action_boost_apply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('boosts');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_bot_allowed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('attach_menu')->default(false);
            $table->boolean('from_request')->default(false);
            $table->text('domain')->nullable();
            $table->uuid('app')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_change_creator', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('new_creator_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_channel_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_channel_migrate_from', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_add_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_add_user__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_chat_add_user')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d0fb90d961d355daa425');
        });
        Schema::create('tl_message_action_message_action_chat_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_create__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_chat_create')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_64050c7ec8a932d953c2');
        });
        Schema::create('tl_message_action_message_action_chat_delete_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_delete_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_edit_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('photo');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_edit_title', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_joined_by_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('inviter_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_joined_by_request', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_chat_migrate_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_conference_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('missed')->default(false);
            $table->boolean('active')->default(false);
            $table->boolean('video')->default(false);
            $table->bigInteger('call_id');
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_conference_c_94dec57429e4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_conference_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_17e49fb49c52cb8aeb97');
        });
        Schema::create('tl_message_action_message_action_contact_sign_up', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_custom_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_game_score', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('game_id');
            $table->integer('score');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_geo_proximity_reached', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('from_id');
            $table->uuid('to_id');
            $table->integer('distance');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_giveaway')->default(false);
            $table->boolean('unclaimed')->default(false);
            $table->uuid('boost_peer')->nullable();
            $table->integer('days');
            $table->text('slug');
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->text('crypto_currency')->nullable();
            $table->bigInteger('crypto_amount')->nullable();
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_gift_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->integer('days');
            $table->text('crypto_currency')->nullable();
            $table->bigInteger('crypto_amount')->nullable();
            $table->uuid('message')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_gift_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->bigInteger('stars');
            $table->text('crypto_currency')->nullable();
            $table->bigInteger('crypto_amount')->nullable();
            $table->text('transaction_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_gift_ton', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->text('crypto_currency');
            $table->bigInteger('crypto_amount');
            $table->text('transaction_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_giveaway_launch', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_giveaway_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('stars')->default(false);
            $table->integer('winners_count');
            $table->integer('unclaimed_count');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('call');
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_group_call_scheduled', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->integer('schedule_date');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_history_clear', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_invite_to_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_invite_to_gr_2ecee64f63f9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_invite_to_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_78f72b40215e49923bf9');
        });
        Schema::create('tl_message_action_message_action_managed_bot_created', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('bot_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_new_creator_pending', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('new_creator_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_no_forwards_request', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('expired')->default(false);
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_no_forwards_toggle', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->uuid('new_value');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_paid_messages_price', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('broadcast_messages_allowed')->default(false);
            $table->bigInteger('stars');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_paid_messages_refunded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('stars');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_payment_refunded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('peer');
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->binary('payload')->nullable();
            $table->uuid('charge');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_payment_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('recurring_init')->default(false);
            $table->boolean('recurring_used')->default(false);
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('invoice_slug')->nullable();
            $table->integer('subscription_until_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_payment_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('recurring_init')->default(false);
            $table->boolean('recurring_used')->default(false);
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->binary('payload');
            $table->uuid('info')->nullable();
            $table->text('shipping_option_id')->nullable();
            $table->uuid('charge');
            $table->integer('subscription_until_date')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('call_id');
            $table->uuid('reason')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_pin_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_poll_append_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('answer');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_poll_delete_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('answer');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_prize_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unclaimed')->default(false);
            $table->bigInteger('stars');
            $table->text('transaction_id');
            $table->uuid('boost_peer');
            $table->integer('giveaway_msg_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_requested_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('button_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_requested_peer__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_requested_peer')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_16ec6b3ef2f92a8fb133');
        });
        Schema::create('tl_message_action_message_action_requested_peer_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('button_id');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_requested_pe_3c798a902d42', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_requested_peer_sent_me')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a966204a65de61914db0');
        });
        Schema::create('tl_message_action_message_action_screenshot_taken', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_secure_values_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_secure_values_sent__types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_secure_values_sent')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8af043dd2b5de89a1e3e');
        });
        Schema::create('tl_message_action_message_action_secure_values_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('credentials');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_secure_value_135a50e48e86', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_secure_values_sent_me')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6ef55a5ffb4cc3fc7f9b');
        });
        Schema::create('tl_message_action_message_action_set_chat_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('theme');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_set_chat_wall_paper', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('same')->default(false);
            $table->boolean('for_both')->default(false);
            $table->uuid('wallpaper');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_set_messages_t_t_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('period');
            $table->bigInteger('auto_setting_from')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_hidden')->default(false);
            $table->boolean('saved')->default(false);
            $table->boolean('converted')->default(false);
            $table->boolean('upgraded')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('can_upgrade')->default(false);
            $table->boolean('prepaid_upgrade')->default(false);
            $table->boolean('upgrade_separate')->default(false);
            $table->boolean('auction_acquired')->default(false);
            $table->uuid('gift');
            $table->uuid('message')->nullable();
            $table->bigInteger('convert_stars')->nullable();
            $table->integer('upgrade_msg_id')->nullable();
            $table->bigInteger('upgrade_stars')->nullable();
            $table->uuid('from_id')->nullable();
            $table->uuid('peer')->nullable();
            $table->bigInteger('saved_id')->nullable();
            $table->text('prepaid_upgrade_hash')->nullable();
            $table->integer('gift_msg_id')->nullable();
            $table->uuid('to_id')->nullable();
            $table->integer('gift_num')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_star_gift_purchase_offer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('declined')->default(false);
            $table->uuid('gift');
            $table->uuid('price');
            $table->integer('expires_at');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_star_gift_pu_8c254ffbf72a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('expired')->default(false);
            $table->uuid('gift');
            $table->uuid('price');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_star_gift_unique', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('upgrade')->default(false);
            $table->boolean('transferred')->default(false);
            $table->boolean('saved')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('prepaid_upgrade')->default(false);
            $table->boolean('assigned')->default(false);
            $table->boolean('from_offer')->default(false);
            $table->boolean('craft')->default(false);
            $table->uuid('gift');
            $table->integer('can_export_at')->nullable();
            $table->bigInteger('transfer_stars')->nullable();
            $table->uuid('from_id')->nullable();
            $table->uuid('peer')->nullable();
            $table->bigInteger('saved_id')->nullable();
            $table->uuid('resale_amount')->nullable();
            $table->integer('can_transfer_at')->nullable();
            $table->integer('can_resell_at')->nullable();
            $table->bigInteger('drop_original_details_stars')->nullable();
            $table->integer('can_craft_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_suggest_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('birthday');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_suggest_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('photo');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_suggested_post_approval', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rejected')->default(false);
            $table->boolean('balance_too_low')->default(false);
            $table->text('reject_comment')->nullable();
            $table->integer('schedule_date')->nullable();
            $table->uuid('price')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_suggested_post_refund', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('payer_initiated')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_suggested_post_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('price');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_todo_append_tasks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_todo_append_tasks__list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_append_tasks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_016c42ae08dc2b482ecd');
        });
        Schema::create('tl_message_action_message_action_todo_completions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_todo_complet_c19fe03faa93', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_completions')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8bb77794b2e4585c172e');
        });
        Schema::create('tl_message_action_message_action_todo_complet_433c02fd34cb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_completions')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9a583df9e3c60aeb763e');
        });
        Schema::create('tl_message_action_message_action_topic_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('title_missing')->default(false);
            $table->text('title');
            $table->integer('icon_color');
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_topic_edit', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->uuid('closed')->nullable();
            $table->uuid('hidden')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_web_view_data_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_message_action_message_action_web_view_data_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('text');
            $table->text('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_action_message_action_web_view_data_sent_me');
        Schema::dropIfExists('tl_message_action_message_action_web_view_data_sent');
        Schema::dropIfExists('tl_message_action_message_action_topic_edit');
        Schema::dropIfExists('tl_message_action_message_action_topic_create');
        Schema::dropIfExists('tl_message_action_message_action_todo_complet_433c02fd34cb');
        Schema::dropIfExists('tl_message_action_message_action_todo_complet_c19fe03faa93');
        Schema::dropIfExists('tl_message_action_message_action_todo_completions');
        Schema::dropIfExists('tl_message_action_message_action_todo_append_tasks__list');
        Schema::dropIfExists('tl_message_action_message_action_todo_append_tasks');
        Schema::dropIfExists('tl_message_action_message_action_suggested_post_success');
        Schema::dropIfExists('tl_message_action_message_action_suggested_post_refund');
        Schema::dropIfExists('tl_message_action_message_action_suggested_post_approval');
        Schema::dropIfExists('tl_message_action_message_action_suggest_profile_photo');
        Schema::dropIfExists('tl_message_action_message_action_suggest_birthday');
        Schema::dropIfExists('tl_message_action_message_action_star_gift_unique');
        Schema::dropIfExists('tl_message_action_message_action_star_gift_pu_8c254ffbf72a');
        Schema::dropIfExists('tl_message_action_message_action_star_gift_purchase_offer');
        Schema::dropIfExists('tl_message_action_message_action_star_gift');
        Schema::dropIfExists('tl_message_action_message_action_set_messages_t_t_l');
        Schema::dropIfExists('tl_message_action_message_action_set_chat_wall_paper');
        Schema::dropIfExists('tl_message_action_message_action_set_chat_theme');
        Schema::dropIfExists('tl_message_action_message_action_secure_value_135a50e48e86');
        Schema::dropIfExists('tl_message_action_message_action_secure_values_sent_me');
        Schema::dropIfExists('tl_message_action_message_action_secure_values_sent__types');
        Schema::dropIfExists('tl_message_action_message_action_secure_values_sent');
        Schema::dropIfExists('tl_message_action_message_action_screenshot_taken');
        Schema::dropIfExists('tl_message_action_message_action_requested_pe_3c798a902d42');
        Schema::dropIfExists('tl_message_action_message_action_requested_peer_sent_me');
        Schema::dropIfExists('tl_message_action_message_action_requested_peer__peers');
        Schema::dropIfExists('tl_message_action_message_action_requested_peer');
        Schema::dropIfExists('tl_message_action_message_action_prize_stars');
        Schema::dropIfExists('tl_message_action_message_action_poll_delete_answer');
        Schema::dropIfExists('tl_message_action_message_action_poll_append_answer');
        Schema::dropIfExists('tl_message_action_message_action_pin_message');
        Schema::dropIfExists('tl_message_action_message_action_phone_call');
        Schema::dropIfExists('tl_message_action_message_action_payment_sent_me');
        Schema::dropIfExists('tl_message_action_message_action_payment_sent');
        Schema::dropIfExists('tl_message_action_message_action_payment_refunded');
        Schema::dropIfExists('tl_message_action_message_action_paid_messages_refunded');
        Schema::dropIfExists('tl_message_action_message_action_paid_messages_price');
        Schema::dropIfExists('tl_message_action_message_action_no_forwards_toggle');
        Schema::dropIfExists('tl_message_action_message_action_no_forwards_request');
        Schema::dropIfExists('tl_message_action_message_action_new_creator_pending');
        Schema::dropIfExists('tl_message_action_message_action_managed_bot_created');
        Schema::dropIfExists('tl_message_action_message_action_invite_to_gr_2ecee64f63f9');
        Schema::dropIfExists('tl_message_action_message_action_invite_to_group_call');
        Schema::dropIfExists('tl_message_action_message_action_history_clear');
        Schema::dropIfExists('tl_message_action_message_action_group_call_scheduled');
        Schema::dropIfExists('tl_message_action_message_action_group_call');
        Schema::dropIfExists('tl_message_action_message_action_giveaway_results');
        Schema::dropIfExists('tl_message_action_message_action_giveaway_launch');
        Schema::dropIfExists('tl_message_action_message_action_gift_ton');
        Schema::dropIfExists('tl_message_action_message_action_gift_stars');
        Schema::dropIfExists('tl_message_action_message_action_gift_premium');
        Schema::dropIfExists('tl_message_action_message_action_gift_code');
        Schema::dropIfExists('tl_message_action_message_action_geo_proximity_reached');
        Schema::dropIfExists('tl_message_action_message_action_game_score');
        Schema::dropIfExists('tl_message_action_message_action_empty');
        Schema::dropIfExists('tl_message_action_message_action_custom_action');
        Schema::dropIfExists('tl_message_action_message_action_contact_sign_up');
        Schema::dropIfExists('tl_message_action_message_action_conference_c_94dec57429e4');
        Schema::dropIfExists('tl_message_action_message_action_conference_call');
        Schema::dropIfExists('tl_message_action_message_action_chat_migrate_to');
        Schema::dropIfExists('tl_message_action_message_action_chat_joined_by_request');
        Schema::dropIfExists('tl_message_action_message_action_chat_joined_by_link');
        Schema::dropIfExists('tl_message_action_message_action_chat_edit_title');
        Schema::dropIfExists('tl_message_action_message_action_chat_edit_photo');
        Schema::dropIfExists('tl_message_action_message_action_chat_delete_user');
        Schema::dropIfExists('tl_message_action_message_action_chat_delete_photo');
        Schema::dropIfExists('tl_message_action_message_action_chat_create__users');
        Schema::dropIfExists('tl_message_action_message_action_chat_create');
        Schema::dropIfExists('tl_message_action_message_action_chat_add_user__users');
        Schema::dropIfExists('tl_message_action_message_action_chat_add_user');
        Schema::dropIfExists('tl_message_action_message_action_channel_migrate_from');
        Schema::dropIfExists('tl_message_action_message_action_channel_create');
        Schema::dropIfExists('tl_message_action_message_action_change_creator');
        Schema::dropIfExists('tl_message_action_message_action_bot_allowed');
        Schema::dropIfExists('tl_message_action_message_action_boost_apply');
        Schema::dropIfExists('tl_message_action');
    }
};
