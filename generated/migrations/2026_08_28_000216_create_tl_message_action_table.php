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
            $table->index('constructor_id', 'ix_a32a047a58d7b3852eba5a77');
            $table->index('account_id', 'ix_a1fbafc582c6127250f5facd');
        });
        Schema::create('tl_message_action_message_action_boost_apply', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('boosts');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8c401fbb80679a8a857aa832');
        });
        Schema::create('tl_message_action_message_action_bot_allowed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('attach_menu')->default(false);
            $table->boolean('from_request')->default(false);
            $table->text('domain')->nullable();
            $table->uuid('app')->nullable();
            $table->index('app', 'ix_a17a6d56de28747e2c8d6c6a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9258b32f65ce224df1ca09f9');
        });
        Schema::create('tl_message_action_message_action_change_creator', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('new_creator_id');
            $table->index('new_creator_id', 'ix_992ac30242429e7573a5e120');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d07620f2c62ee85cb68ffa4f');
        });
        Schema::create('tl_message_action_message_action_channel_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df29280df7a23167546fee18');
        });
        Schema::create('tl_message_action_message_action_channel_migrate_from', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('chat_id');
            $table->index('chat_id', 'ix_b3b5d4fd24a447030ea1b2ac');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7a3ef37a4d146168f482fe70');
        });
        Schema::create('tl_message_action_message_action_chat_add_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8a17c7b5865bc6fd82bb5273');
        });
        Schema::create('tl_message_action_message_action_chat_add_user__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_chat_add_user')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d0fb90d961d355daa425');
            $table->index('account_id', 'ix_6c8b4bdfb61f27c47d298e57');
        });
        Schema::create('tl_message_action_message_action_chat_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9fb95d6faa49a64270534ea5');
        });
        Schema::create('tl_message_action_message_action_chat_create__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_chat_create')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_64050c7ec8a932d953c2');
            $table->index('account_id', 'ix_011cfe6ec437fb7375ae0f7a');
        });
        Schema::create('tl_message_action_message_action_chat_delete_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4d96465ce9d544a23b621ac2');
        });
        Schema::create('tl_message_action_message_action_chat_delete_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_ea4a20322002de49183515d8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0a3976354044f60da3eede89');
        });
        Schema::create('tl_message_action_message_action_chat_edit_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('photo');
            $table->index('photo', 'ix_4e1221baab68400a8d462c53');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ade90f04d33fb06087026642');
        });
        Schema::create('tl_message_action_message_action_chat_edit_title', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_84e821410e3e23934c965f59');
        });
        Schema::create('tl_message_action_message_action_chat_joined_by_link', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('inviter_id');
            $table->index('inviter_id', 'ix_1a490c82d77135156112e1a1');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_28863e75f45139c22fbcb9f0');
        });
        Schema::create('tl_message_action_message_action_chat_joined_by_request', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ba8aa9950ce5b82e98f4672d');
        });
        Schema::create('tl_message_action_message_action_chat_migrate_to', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_363ce91c759c96fc64bf01e2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c769f57fc8d0c6e0a2f2e19d');
        });
        Schema::create('tl_message_action_message_action_conference_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('missed')->default(false);
            $table->boolean('active')->default(false);
            $table->boolean('video')->default(false);
            $table->bigInteger('call_id');
            $table->index('call_id', 'ix_89914b5f066f9ff6bfe1931b');
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_87cf4bbde0ac54e1f40783c5');
        });
        Schema::create('tl_message_action_message_action_conference_c_94dec57429e4', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_conference_call')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_17e49fb49c52cb8aeb97');
            $table->index('account_id', 'ix_8c2fbc40e16e11156ba1c548');
        });
        Schema::create('tl_message_action_message_action_contact_sign_up', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3e2d20c6b04e811cf0332e45');
        });
        Schema::create('tl_message_action_message_action_custom_action', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('message');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8be3ab04dcf00c295cbad151');
        });
        Schema::create('tl_message_action_message_action_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cec899870e013d23bb71ec60');
        });
        Schema::create('tl_message_action_message_action_game_score', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('game_id');
            $table->index('game_id', 'ix_f99791c5c9f3aa55010ea2ad');
            $table->integer('score');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ce56dd29a80f37752f7751ae');
        });
        Schema::create('tl_message_action_message_action_geo_proximity_reached', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('from_id');
            $table->index('from_id', 'ix_8ec508e62b27a5c90619d24e');
            $table->bigInteger('to_id');
            $table->index('to_id', 'ix_b1233fe6e9a3eba54357360f');
            $table->integer('distance');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a9156754d72abc16be78cabb');
        });
        Schema::create('tl_message_action_message_action_gift_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_giveaway')->default(false);
            $table->boolean('unclaimed')->default(false);
            $table->bigInteger('boost_peer')->nullable();
            $table->index('boost_peer', 'ix_a53cebb528b724713278578f');
            $table->integer('days');
            $table->text('slug');
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->text('crypto_currency')->nullable();
            $table->bigInteger('crypto_amount')->nullable();
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_c5c0d07f9f65a2027b8015c1');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_18f01fe9dc3c0549cc98a6a1');
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
            $table->index('message', 'ix_ef4218c32b1cd9ca3d0af2d2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bfe69c6e7e3ce5584c29669c');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_85fddba88f6b88d8d4a55727');
        });
        Schema::create('tl_message_action_message_action_gift_ton', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('currency');
            $table->bigInteger('amount');
            $table->text('crypto_currency');
            $table->bigInteger('crypto_amount');
            $table->text('transaction_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4994a4e05ddfdf2a330c77e1');
        });
        Schema::create('tl_message_action_message_action_giveaway_launch', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1c338efa6d7f62f5822c1a02');
        });
        Schema::create('tl_message_action_message_action_giveaway_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('stars')->default(false);
            $table->integer('winners_count');
            $table->integer('unclaimed_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_203d3b78e33dfe414e71bda3');
        });
        Schema::create('tl_message_action_message_action_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('call');
            $table->index('call', 'ix_92558a85246e6f5fa35eb4b2');
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cbf1ad4495b82a5bac0ff2d6');
        });
        Schema::create('tl_message_action_message_action_group_call_scheduled', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->index('call', 'ix_b24278f3900c03cbae924d3e');
            $table->integer('schedule_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1760dee1af8521fc60918df1');
        });
        Schema::create('tl_message_action_message_action_history_clear', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fe10931064fb4a92135d75d2');
        });
        Schema::create('tl_message_action_message_action_invite_to_group_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('call');
            $table->index('call', 'ix_8695334bdc73a9d3f6ae1b46');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4a375b8242b812025c4f6db4');
        });
        Schema::create('tl_message_action_message_action_invite_to_gr_2ecee64f63f9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_invite_to_group_call')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_78f72b40215e49923bf9');
            $table->index('account_id', 'ix_4de3adc4668fef176e26ee58');
        });
        Schema::create('tl_message_action_message_action_managed_bot_created', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_bcb457a301143c57a6009143');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b7e3102862a56cf3564f36a0');
        });
        Schema::create('tl_message_action_message_action_new_creator_pending', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('new_creator_id');
            $table->index('new_creator_id', 'ix_335b079013ba613f04ce9cbd');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d5d875ae59cebd4f69c81564');
        });
        Schema::create('tl_message_action_message_action_no_forwards_request', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('expired')->default(false);
            $table->uuid('prev_value');
            $table->index('prev_value', 'ix_5553dbf9565c0a1799e922e4');
            $table->uuid('new_value');
            $table->index('new_value', 'ix_8400bcafd6732067ea302040');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_31eb4283827a529c9396714e');
        });
        Schema::create('tl_message_action_message_action_no_forwards_toggle', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('prev_value');
            $table->index('prev_value', 'ix_e83b554e0d72c962fb89d7e3');
            $table->uuid('new_value');
            $table->index('new_value', 'ix_36417f03bfbb652c9c2f7773');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d286b551fc5372f29174fc2d');
        });
        Schema::create('tl_message_action_message_action_paid_messages_price', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('broadcast_messages_allowed')->default(false);
            $table->bigInteger('stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_24a8046a4817ee847a4c5ea7');
        });
        Schema::create('tl_message_action_message_action_paid_messages_refunded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('stars');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a8a86de8b570568daa061cfc');
        });
        Schema::create('tl_message_action_message_action_payment_refunded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_f3be002f2f15eb61b791de9b');
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->binary('payload')->nullable();
            $table->uuid('charge');
            $table->index('charge', 'ix_f49e5ac47960e0f948c0fe9a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1472fc6008785a3c697b8e58');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_76c7a1c4130fbc0eece3296e');
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
            $table->index('info', 'ix_8de12fc3e514ad751e7146d9');
            $table->text('shipping_option_id')->nullable();
            $table->uuid('charge');
            $table->index('charge', 'ix_e05546857a0dc7cedb896d05');
            $table->integer('subscription_until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b73d23dca0399599cb51b267');
        });
        Schema::create('tl_message_action_message_action_phone_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('video')->default(false);
            $table->bigInteger('call_id');
            $table->index('call_id', 'ix_c93f49968be0a3b6bd47c443');
            $table->uuid('reason')->nullable();
            $table->index('reason', 'ix_c133518fb10e0759ab46a757');
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dd0f2c7e5d0056af72fccc7b');
        });
        Schema::create('tl_message_action_message_action_pin_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_13f097450e6698ab40180549');
        });
        Schema::create('tl_message_action_message_action_poll_append_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('answer');
            $table->index('answer', 'ix_e1e73bc74f7d41a7f2ad3b84');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7e47b5d0c6976dda0ab8b596');
        });
        Schema::create('tl_message_action_message_action_poll_delete_answer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('answer');
            $table->index('answer', 'ix_eeaccc25a873fd4e7763ed94');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_60b8e16325d48a4aeaaf2a76');
        });
        Schema::create('tl_message_action_message_action_prize_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('unclaimed')->default(false);
            $table->bigInteger('stars');
            $table->text('transaction_id');
            $table->bigInteger('boost_peer');
            $table->index('boost_peer', 'ix_7883fb8b646d681d186286ef');
            $table->integer('giveaway_msg_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ba65ec69a11320cf3d10c460');
        });
        Schema::create('tl_message_action_message_action_requested_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('button_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6150bd4dc2bc2e583aeecb78');
        });
        Schema::create('tl_message_action_message_action_requested_peer__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_requested_peer')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_16ec6b3ef2f92a8fb133');
            $table->index('account_id', 'ix_0e5520f263cef03175b0128e');
        });
        Schema::create('tl_message_action_message_action_requested_peer_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->integer('button_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d883258215bab1e50c1dfb60');
        });
        Schema::create('tl_message_action_message_action_requested_pe_3c798a902d42', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_requested_peer_sent_me')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a966204a65de61914db0');
            $table->index('account_id', 'ix_f56b37b7754a0c06962300dc');
        });
        Schema::create('tl_message_action_message_action_screenshot_taken', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2e4ab4b47796ef817698a568');
        });
        Schema::create('tl_message_action_message_action_secure_values_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cb5674ac8031a583239623fd');
        });
        Schema::create('tl_message_action_message_action_secure_values_sent__types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_secure_values_sent')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8af043dd2b5de89a1e3e');
            $table->index('account_id', 'ix_53cad9a3badcb74d11345405');
        });
        Schema::create('tl_message_action_message_action_secure_values_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('credentials');
            $table->index('credentials', 'ix_ea963bfef2a8d18c487ffd32');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_50f41245b6345887eec1a3a7');
        });
        Schema::create('tl_message_action_message_action_secure_value_135a50e48e86', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_secure_values_sent_me')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6ef55a5ffb4cc3fc7f9b');
            $table->index('account_id', 'ix_b4afa4c5b84e55090ca14fbd');
        });
        Schema::create('tl_message_action_message_action_set_chat_theme', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('theme');
            $table->index('theme', 'ix_bf63cae74c25a3647801e8ed');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc0d89a13ff815b1b4b6716f');
        });
        Schema::create('tl_message_action_message_action_set_chat_wall_paper', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('same')->default(false);
            $table->boolean('for_both')->default(false);
            $table->uuid('wallpaper');
            $table->index('wallpaper', 'ix_1a29477743fe3bf78345bc9b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1e9edf268ee653822f51f451');
        });
        Schema::create('tl_message_action_message_action_set_messages_t_t_l', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('period');
            $table->bigInteger('auto_setting_from')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8cd6a7deb1f7399ba74e8441');
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
            $table->index('gift', 'ix_a36c1de5b3671f42f7a76c20');
            $table->uuid('message')->nullable();
            $table->index('message', 'ix_84195e2b39fef7f02ec37592');
            $table->bigInteger('convert_stars')->nullable();
            $table->integer('upgrade_msg_id')->nullable();
            $table->bigInteger('upgrade_stars')->nullable();
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_b6d4c40a3847eb6086985880');
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_71d5f9d3525cf2f637961d7b');
            $table->bigInteger('saved_id')->nullable();
            $table->index('saved_id', 'ix_97d9afd5830165f432bcafd0');
            $table->text('prepaid_upgrade_hash')->nullable();
            $table->integer('gift_msg_id')->nullable();
            $table->bigInteger('to_id')->nullable();
            $table->index('to_id', 'ix_4d2f0053f70d56862d3b82ae');
            $table->integer('gift_num')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8e609ca1340de2afbeff32e1');
        });
        Schema::create('tl_message_action_message_action_star_gift_purchase_offer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('declined')->default(false);
            $table->uuid('gift');
            $table->index('gift', 'ix_38d0b68d04c5e3b4697205f7');
            $table->uuid('price');
            $table->index('price', 'ix_7b254bdfc216c426a544680b');
            $table->integer('expires_at');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70313dce0c54c4cdae041615');
        });
        Schema::create('tl_message_action_message_action_star_gift_pu_8c254ffbf72a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('expired')->default(false);
            $table->uuid('gift');
            $table->index('gift', 'ix_d96fad0cbfe531ce426687a4');
            $table->uuid('price');
            $table->index('price', 'ix_1978c660e922596b2fba2cc5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b6a8bbe44601a3c088b6d2e3');
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
            $table->index('gift', 'ix_0c4f9862abc052ea283efd8b');
            $table->integer('can_export_at')->nullable();
            $table->bigInteger('transfer_stars')->nullable();
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_19415e8c3c006a7862b371a4');
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_bca8d1d65bd2eb538ec61cd2');
            $table->bigInteger('saved_id')->nullable();
            $table->index('saved_id', 'ix_239336c976b4ec4e3c0335ce');
            $table->uuid('resale_amount')->nullable();
            $table->index('resale_amount', 'ix_86137d8ad58878fd614ffd3a');
            $table->integer('can_transfer_at')->nullable();
            $table->integer('can_resell_at')->nullable();
            $table->bigInteger('drop_original_details_stars')->nullable();
            $table->integer('can_craft_at')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bd8cdff49d07d0b431619ca4');
        });
        Schema::create('tl_message_action_message_action_suggest_birthday', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('birthday');
            $table->index('birthday', 'ix_6f0dbfaaffdf042f32a797f2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bc972c844443b7fa7ef74d68');
        });
        Schema::create('tl_message_action_message_action_suggest_profile_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('photo');
            $table->index('photo', 'ix_f12306bb0839e8000c5a99b0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6d7da42451e4f4bc9d56173b');
        });
        Schema::create('tl_message_action_message_action_suggested_post_approval', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rejected')->default(false);
            $table->boolean('balance_too_low')->default(false);
            $table->text('reject_comment')->nullable();
            $table->integer('schedule_date')->nullable();
            $table->uuid('price')->nullable();
            $table->index('price', 'ix_6a1fb7aa50be260de6ec1154');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb6e31c7a9edd11090bf4852');
        });
        Schema::create('tl_message_action_message_action_suggested_post_refund', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('payer_initiated')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5a9841de1ae73ce97de2de63');
        });
        Schema::create('tl_message_action_message_action_suggested_post_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->uuid('price');
            $table->index('price', 'ix_a330fd350170b371f90d4de6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4da8bcd85844885fe5220f8e');
        });
        Schema::create('tl_message_action_message_action_todo_append_tasks', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_27a2e3cfbe40683a50606acf');
        });
        Schema::create('tl_message_action_message_action_todo_append_tasks__list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_append_tasks')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_016c42ae08dc2b482ecd');
            $table->index('account_id', 'ix_4e4a5ba70e4c7645667f32de');
        });
        Schema::create('tl_message_action_message_action_todo_completions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_20cfecf79d75b887641da6e7');
        });
        Schema::create('tl_message_action_message_action_todo_complet_c19fe03faa93', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_completions')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8bb77794b2e4585c172e');
            $table->index('account_id', 'ix_4d3fa3a9eef05001cc0507ca');
        });
        Schema::create('tl_message_action_message_action_todo_complet_433c02fd34cb', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_action_message_action_todo_completions')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9a583df9e3c60aeb763e');
            $table->index('account_id', 'ix_77204cd27c3089773e50d65c');
        });
        Schema::create('tl_message_action_message_action_topic_create', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('title_missing')->default(false);
            $table->text('title');
            $table->integer('icon_color');
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_e6ba6dd179204b08fd36f98e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_895063e232ec6f7329cf7af8');
        });
        Schema::create('tl_message_action_message_action_topic_edit', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_f3d31d61b91daec8802e09f7');
            $table->uuid('closed')->nullable();
            $table->index('closed', 'ix_71336ae34f7d4a1367c0834a');
            $table->uuid('hidden')->nullable();
            $table->index('hidden', 'ix_a5d752d2c9404b0cd49748cb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a70f825d4ab5139d78440bd3');
        });
        Schema::create('tl_message_action_message_action_web_view_data_sent', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c36ea3c3e9bc6bfcb7b6632a');
        });
        Schema::create('tl_message_action_message_action_web_view_data_sent_me', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_action')->cascadeOnDelete();
            $table->text('text');
            $table->text('data');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e5545c4212991eff6e5c8a7d');
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
