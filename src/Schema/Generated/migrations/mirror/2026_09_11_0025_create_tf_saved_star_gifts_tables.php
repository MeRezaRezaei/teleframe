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
        Schema::create('tf_saved_star_gifts', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->boolean('name_hidden')->default(false);
        $table->boolean('unsaved')->default(false);
        $table->boolean('refunded')->default(false);
        $table->boolean('can_upgrade')->default(false);
        $table->boolean('pinned_to_top')->default(false);
        $table->boolean('upgrade_separate')->default(false);
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('constructor');
        $table->boolean('limited')->default(false);
        $table->boolean('sold_out')->default(false);
        $table->boolean('birthday')->default(false);
        $table->boolean('require_premium')->default(false);
        $table->boolean('limited_per_user')->default(false);
        $table->boolean('peer_color_available')->default(false);
        $table->boolean('auction')->default(false);
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('stars')->unsigned();
        $table->integer('availability_remains')->unsigned();
        $table->integer('availability_total')->unsigned();
        $table->bigInteger('availability_resale')->unsigned();
        $table->bigInteger('convert_stars')->unsigned();
        $table->integer('first_sale_date')->unsigned();
        $table->integer('last_sale_date')->unsigned();
        $table->bigInteger('upgrade_stars')->unsigned();
        $table->bigInteger('resell_min_stars')->unsigned();
        $table->text('title');
        $table->unsignedTinyInteger('released_by_type');
        $table->bigInteger('released_by_id')->unsigned();
        $table->integer('per_user_total')->unsigned();
        $table->integer('per_user_remains')->unsigned();
        $table->integer('locked_until_date')->unsigned();
        $table->text('auction_slug');
        $table->integer('gifts_per_round')->unsigned();
        $table->integer('auction_start_date')->unsigned();
        $table->integer('upgrade_variants')->unsigned();
        $table->boolean('resale_ton_only')->default(false);
        $table->boolean('theme_available')->default(false);
        $table->boolean('burned')->default(false);
        $table->boolean('crafted')->default(false);
        $table->bigInteger('gift_id')->unsigned();
        $table->text('slug');
        $table->integer('num')->unsigned();
        $table->unsignedTinyInteger('owner_id_type');
        $table->bigInteger('owner_id_id')->unsigned();
        $table->text('owner_name');
        $table->text('owner_address');
        $table->integer('availability_issued')->unsigned();
        $table->text('gift_address');
        $table->bigInteger('value_amount')->unsigned();
        $table->text('value_currency');
        $table->bigInteger('value_usd_amount')->unsigned();
        $table->unsignedTinyInteger('theme_peer_type');
        $table->bigInteger('theme_peer_id')->unsigned();
        $table->unsignedTinyInteger('host_id_type');
        $table->bigInteger('host_id_id')->unsigned();
        $table->integer('offer_min_stars')->unsigned();
        $table->integer('craft_chance_permille')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_background', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('center_color')->unsigned();
        $table->integer('edge_color')->unsigned();
        $table->integer('text_color')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('crafted')->default(false);
        $table->text('name');
        $table->integer('backdrop_id')->unsigned();
        $table->integer('center_color')->unsigned();
        $table->integer('edge_color')->unsigned();
        $table->integer('pattern_color')->unsigned();
        $table->integer('text_color')->unsigned();
        $table->unsignedTinyInteger('sender_id_type');
        $table->bigInteger('sender_id_id')->unsigned();
        $table->unsignedTinyInteger('recipient_id_type');
        $table->bigInteger('recipient_id_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_gift_attributes_rarity', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('constructor');
        $table->integer('permille')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_attributes_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_attributes_message_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_gift_resell_amount', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_gift_peer_color', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('constructor');
        $table->integer('color')->unsigned();
        $table->bigInteger('background_emoji_id')->unsigned();
        $table->bigInteger('collectible_id')->unsigned();
        $table->bigInteger('gift_emoji_id')->unsigned();
        $table->integer('accent_color')->unsigned();
        $table->integer('dark_accent_color')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_peer_color_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_gift_peer_color_dark_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_from_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('from_id_type');
        $table->bigInteger('from_id_id')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_message_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('offset')->unsigned();
        $table->integer('length')->unsigned();
        $table->text('language');
        $table->text('url');
        $table->bigInteger('user_id')->unsigned();
        $table->bigInteger('document_id')->unsigned();
        $table->boolean('collapsed')->default(false);
        $table->boolean('relative')->default(false);
        $table->boolean('short_time')->default(false);
        $table->boolean('long_time')->default(false);
        $table->boolean('short_date')->default(false);
        $table->boolean('long_date')->default(false);
        $table->boolean('day_of_week')->default(false);
        $table->integer('date')->unsigned();
        $table->text('old_text');
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_msg_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_saved_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->bigInteger('saved_id')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_convert_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->bigInteger('convert_stars')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_upgrade_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->bigInteger('upgrade_stars')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_can_export_at', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('can_export_at')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_transfer_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->bigInteger('transfer_stars')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_can_transfer_at', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('can_transfer_at')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_can_resell_at', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('can_resell_at')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_collection_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id', 'position']);
        });

        Schema::create('tf_saved_star_gifts_prepaid_upgrade_hash', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->text('prepaid_upgrade_hash');
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_drop_original_details_stars', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->bigInteger('drop_original_details_stars')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_gift_num', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('gift_num')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

        Schema::create('tf_saved_star_gifts_can_craft_at', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('saved_star_gift_id')->unsigned();
        $table->integer('can_craft_at')->unsigned();
        $table->primary(['account_id', 'saved_star_gift_id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_saved_star_gifts');

        Schema::dropIfExists('tf_saved_star_gifts_gift');

        Schema::dropIfExists('tf_saved_star_gifts_gift_background');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_rarity');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_message');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_message_entities');

        Schema::dropIfExists('tf_saved_star_gifts_gift_resell_amount');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color_colors');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color_dark_colors');

        Schema::dropIfExists('tf_saved_star_gifts_from_id');

        Schema::dropIfExists('tf_saved_star_gifts_message');

        Schema::dropIfExists('tf_saved_star_gifts_message_entities');

        Schema::dropIfExists('tf_saved_star_gifts_msg_id');

        Schema::dropIfExists('tf_saved_star_gifts_saved_id');

        Schema::dropIfExists('tf_saved_star_gifts_convert_stars');

        Schema::dropIfExists('tf_saved_star_gifts_upgrade_stars');

        Schema::dropIfExists('tf_saved_star_gifts_can_export_at');

        Schema::dropIfExists('tf_saved_star_gifts_transfer_stars');

        Schema::dropIfExists('tf_saved_star_gifts_can_transfer_at');

        Schema::dropIfExists('tf_saved_star_gifts_can_resell_at');

        Schema::dropIfExists('tf_saved_star_gifts_collection_id');

        Schema::dropIfExists('tf_saved_star_gifts_prepaid_upgrade_hash');

        Schema::dropIfExists('tf_saved_star_gifts_drop_original_details_stars');

        Schema::dropIfExists('tf_saved_star_gifts_gift_num');

        Schema::dropIfExists('tf_saved_star_gifts_can_craft_at');

        Schema::dropIfExists('tf_saved_star_gifts_can_craft_at');

        Schema::dropIfExists('tf_saved_star_gifts_gift_num');

        Schema::dropIfExists('tf_saved_star_gifts_drop_original_details_stars');

        Schema::dropIfExists('tf_saved_star_gifts_prepaid_upgrade_hash');

        Schema::dropIfExists('tf_saved_star_gifts_collection_id');

        Schema::dropIfExists('tf_saved_star_gifts_can_resell_at');

        Schema::dropIfExists('tf_saved_star_gifts_can_transfer_at');

        Schema::dropIfExists('tf_saved_star_gifts_transfer_stars');

        Schema::dropIfExists('tf_saved_star_gifts_can_export_at');

        Schema::dropIfExists('tf_saved_star_gifts_upgrade_stars');

        Schema::dropIfExists('tf_saved_star_gifts_convert_stars');

        Schema::dropIfExists('tf_saved_star_gifts_saved_id');

        Schema::dropIfExists('tf_saved_star_gifts_msg_id');

        Schema::dropIfExists('tf_saved_star_gifts_message_entities');

        Schema::dropIfExists('tf_saved_star_gifts_message');

        Schema::dropIfExists('tf_saved_star_gifts_from_id');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color_dark_colors');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color_colors');

        Schema::dropIfExists('tf_saved_star_gifts_gift_peer_color');

        Schema::dropIfExists('tf_saved_star_gifts_gift_resell_amount');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_message_entities');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_message');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes_rarity');

        Schema::dropIfExists('tf_saved_star_gifts_gift_attributes');

        Schema::dropIfExists('tf_saved_star_gifts_gift_background');

        Schema::dropIfExists('tf_saved_star_gifts_gift');

        Schema::dropIfExists('tf_saved_star_gifts');

    }
};
