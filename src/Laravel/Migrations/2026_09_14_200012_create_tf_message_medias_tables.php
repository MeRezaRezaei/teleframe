<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NF5 mirror — messages domain: MessageMedia union (19 ctors) and MessageAction
 * union (67 ctors) 1:1 children.
 *
 * Both are constructor-discriminated flattened unions: every ctor-specific
 * optional field is type-defaulted so partial writes place (a messageMediaEmpty
 * row carries only account_id + id + constructor). bytes → lowercase hex TEXT.
 * Nested objects (Photo/Document/GeoPoint/Poll/… and PaymentCharge/StarGift/
 * RequestedPeer/…) cannot nest under the flat decomposer write path — they are
 * deferred; the discriminator + immediate fields are the mirror truth here.
 * messageMediaStory.id collides with the parent FK column id (plain-name
 * collision under the flat path) → dropped, documented defer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tf_messages_media', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');

            // messageMediaPhoto / messageMediaDocument / remaining union flags.
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->boolean('nopremium')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('round')->default(false);
            $table->boolean('voice')->default(false);
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->boolean('via_mention')->default(false);
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('rtmp_stream')->default(false);

            // Scalar residue of each ctor (nested objects deferred).
            $table->integer('ttl_seconds')->default(0);
            $table->integer('video_timestamp')->default(0);
            $table->text('phone_number')->default('');
            $table->text('first_name')->default('');
            $table->text('last_name')->default('');
            $table->text('vcard')->default('');
            $table->bigInteger('user_id')->default(0);
            $table->text('title')->default('');
            $table->text('address')->default('');
            $table->text('provider')->default('');
            $table->text('venue_id')->default('');
            $table->text('venue_type')->default('');
            $table->text('description')->default('');
            $table->integer('receipt_msg_id')->default(0);
            $table->text('currency')->default('');
            $table->bigInteger('total_amount')->default(0);
            $table->text('start_param')->default('');
            $table->integer('heading')->default(0);
            $table->integer('period')->default(0);
            $table->integer('proximity_notification_radius')->default(0);
            $table->integer('value')->default(0);
            $table->text('emoticon')->default('');
            $table->tinyInteger('peer_type')->default(0);
            $table->bigInteger('peer_id')->default(0);
            $table->bigInteger('channel_id')->default(0);
            $table->integer('additional_peers_count')->default(0);
            $table->integer('launch_msg_id')->default(0);
            $table->integer('winners_count')->default(0);
            $table->integer('unclaimed_count')->default(0);
            $table->text('prize_description')->default('');
            $table->integer('quantity')->default(0);
            $table->integer('months')->default(0);
            $table->bigInteger('stars')->default(0);
            $table->integer('until_date')->default(0);
            $table->bigInteger('stars_amount')->default(0);

            $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_messages_service_action', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->text('constructor');

            // Recurring shared bools across the union (flags.?true nodes).
            $table->boolean('recurring_init')->default(false);
            $table->boolean('recurring_used')->default(false);
            $table->boolean('attach_menu')->default(false);
            $table->boolean('from_request')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('title_missing')->default(false);
            $table->boolean('same')->default(false);
            $table->boolean('for_both')->default(false);
            $table->boolean('via_giveaway')->default(false);
            $table->boolean('unclaimed')->default(false);
            $table->boolean('name_hidden')->default(false);
            $table->boolean('saved')->default(false);
            $table->boolean('converted')->default(false);
            $table->boolean('upgraded')->default(false);
            $table->boolean('refunded')->default(false);
            $table->boolean('can_upgrade')->default(false);
            $table->boolean('prepaid_upgrade')->default(false);
            $table->boolean('upgrade_separate')->default(false);
            $table->boolean('auction_acquired')->default(false);
            $table->boolean('transferred')->default(false);
            $table->boolean('assigned')->default(false);
            $table->boolean('from_offer')->default(false);
            $table->boolean('craft')->default(false);
            $table->boolean('closed')->default(false);
            $table->boolean('hidden')->default(false);
            $table->boolean('rejected')->default(false);
            $table->boolean('balance_too_low')->default(false);
            $table->boolean('payer_initiated')->default(false);
            $table->boolean('expired')->default(false);
            $table->boolean('missed')->default(false);
            $table->boolean('active')->default(false);
            $table->boolean('broadcast_messages_allowed')->default(false);
            $table->boolean('accepted')->default(false);
            $table->boolean('declined')->default(false);
            $table->boolean('prev_value')->default(false);
            $table->boolean('new_value')->default(false);

            // Scalar residue across all 67 ctors (nested objects deferred).
            $table->text('title')->default('');
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('inviter_id')->default(0);
            $table->bigInteger('channel_id')->default(0);
            $table->bigInteger('chat_id')->default(0);
            $table->bigInteger('game_id')->default(0);
            $table->integer('score')->default(0);
            $table->text('currency')->default('');
            $table->bigInteger('total_amount')->default(0);
            $table->text('payload')->default('');
            $table->text('shipping_option_id')->default('');
            $table->integer('subscription_until_date')->default(0);
            $table->text('invoice_slug')->default('');
            $table->bigInteger('call_id')->default(0);
            $table->text('reason')->default('');
            $table->integer('duration')->default(0);
            $table->text('message')->default('');
            $table->text('domain')->default('');
            $table->tinyInteger('from_id_type')->default(0);
            $table->bigInteger('from_id_id')->default(0);
            $table->tinyInteger('to_id_type')->default(0);
            $table->bigInteger('to_id_id')->default(0);
            $table->integer('distance')->default(0);
            $table->integer('period')->default(0);
            $table->bigInteger('auto_setting_from')->default(0);
            $table->integer('schedule_date')->default(0);
            $table->text('text')->default('');
            $table->text('data')->default('');
            $table->integer('days')->default(0);
            $table->text('crypto_currency')->default('');
            $table->bigInteger('crypto_amount')->default(0);
            $table->integer('icon_color')->default(0);
            $table->bigInteger('icon_emoji_id')->default(0);
            $table->integer('button_id')->default(0);
            $table->text('slug')->default('');
            $table->bigInteger('stars')->default(0);
            $table->tinyInteger('boost_peer_type')->default(0);
            $table->bigInteger('boost_peer_id')->default(0);
            $table->integer('winners_count')->default(0);
            $table->integer('unclaimed_count')->default(0);
            $table->integer('boosts')->default(0);
            $table->text('transaction_id')->default('');
            $table->bigInteger('convert_stars')->default(0);
            $table->integer('upgrade_msg_id')->default(0);
            $table->bigInteger('upgrade_stars')->default(0);
            $table->text('prepaid_upgrade_hash')->default('');
            $table->integer('gift_msg_id')->default(0);
            $table->integer('gift_num')->default(0);
            $table->integer('can_export_at')->default(0);
            $table->bigInteger('transfer_stars')->default(0);
            $table->integer('can_transfer_at')->default(0);
            $table->integer('can_resell_at')->default(0);
            $table->bigInteger('drop_original_details_stars')->default(0);
            $table->integer('can_craft_at')->default(0);
            $table->integer('count')->default(0);
            $table->text('reject_comment')->default('');
            $table->integer('expires_at')->default(0);
            $table->bigInteger('new_creator_id')->default(0);
            $table->bigInteger('bot_id')->default(0);
            $table->integer('months')->default(0);

            $table->primary(['account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tf_messages_service_action');
        Schema::dropIfExists('tf_messages_media');
    }
};
