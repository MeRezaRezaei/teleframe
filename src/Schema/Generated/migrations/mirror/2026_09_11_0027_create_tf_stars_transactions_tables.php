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
        Schema::create('tf_stars_transactions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->text('id');
        $table->integer('date')->unsigned();
        $table->boolean('refund')->default(false);
        $table->boolean('pending')->default(false);
        $table->boolean('failed')->default(false);
        $table->boolean('gift')->default(false);
        $table->boolean('reaction')->default(false);
        $table->boolean('stargift_upgrade')->default(false);
        $table->boolean('business_transfer')->default(false);
        $table->boolean('stargift_resale')->default(false);
        $table->boolean('posts_search')->default(false);
        $table->boolean('stargift_prepaid_upgrade')->default(false);
        $table->boolean('stargift_drop_original_details')->default(false);
        $table->boolean('phonegroup_message')->default(false);
        $table->boolean('stargift_auction_bid')->default(false);
        $table->boolean('offer')->default(false);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_amount', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_peer', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('title');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_description', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('description');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_photo_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->boolean('mask')->default(false);
        $table->text('alt');
        $table->boolean('round_message')->default(false);
        $table->boolean('supports_streaming')->default(false);
        $table->boolean('nosound')->default(false);
        $table->double('duration');
        $table->integer('preload_prefix_size')->unsigned();
        $table->double('video_start_ts');
        $table->text('video_codec');
        $table->boolean('voice')->default(false);
        $table->text('title');
        $table->text('performer');
        $table->text('waveform');
        $table->text('file_name');
        $table->boolean('free')->default(false);
        $table->boolean('text_color')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_transaction_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('transaction_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_transaction_url', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('transaction_url');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_bot_payload', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->string('bot_payload', 255);
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_msg_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('msg_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->boolean('spoiler')->default(false);
        $table->boolean('live_photo')->default(false);
        $table->integer('ttl_seconds')->unsigned();
        $table->text('phone_number');
        $table->text('first_name');
        $table->text('last_name');
        $table->text('vcard');
        $table->bigInteger('user_id')->unsigned();
        $table->boolean('nopremium')->default(false);
        $table->boolean('video')->default(false);
        $table->boolean('round')->default(false);
        $table->boolean('voice')->default(false);
        $table->integer('video_timestamp')->unsigned();
        $table->boolean('force_large_media')->default(false);
        $table->boolean('force_small_media')->default(false);
        $table->boolean('manual')->default(false);
        $table->boolean('safe')->default(false);
        $table->text('title');
        $table->text('address');
        $table->text('provider');
        $table->text('venue_id');
        $table->text('venue_type');
        $table->boolean('shipping_address_requested')->default(false);
        $table->boolean('test')->default(false);
        $table->text('description');
        $table->integer('receipt_msg_id')->unsigned();
        $table->text('currency');
        $table->bigInteger('total_amount')->unsigned();
        $table->text('start_param');
        $table->integer('heading')->unsigned();
        $table->integer('period')->unsigned();
        $table->integer('proximity_notification_radius')->unsigned();
        $table->integer('value')->unsigned();
        $table->text('emoticon');
        $table->boolean('via_mention')->default(false);
        $table->unsignedTinyInteger('peer_type');
        $table->bigInteger('peer_id')->unsigned();
        $table->boolean('only_new_subscribers')->default(false);
        $table->boolean('winners_are_visible')->default(false);
        $table->text('prize_description');
        $table->integer('quantity')->unsigned();
        $table->integer('months')->unsigned();
        $table->bigInteger('stars')->unsigned();
        $table->integer('until_date')->unsigned();
        $table->boolean('refunded')->default(false);
        $table->bigInteger('channel_id')->unsigned();
        $table->integer('additional_peers_count')->unsigned();
        $table->integer('launch_msg_id')->unsigned();
        $table->integer('winners_count')->unsigned();
        $table->integer('unclaimed_count')->unsigned();
        $table->bigInteger('stars_amount')->unsigned();
        $table->boolean('rtmp_stream')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_geo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->double('long');
        $table->double('lat');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('accuracy_radius')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('access_hash')->unsigned();
        $table->text('file_reference');
        $table->integer('date')->unsigned();
        $table->text('mime_type');
        $table->bigInteger('size')->unsigned();
        $table->integer('dc_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_thumbs_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_video_thumbs', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->double('video_start_ts');
        $table->bigInteger('emoji_id')->unsigned();
        $table->bigInteger('sticker_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->boolean('mask')->default(false);
        $table->text('alt');
        $table->boolean('round_message')->default(false);
        $table->boolean('supports_streaming')->default(false);
        $table->boolean('nosound')->default(false);
        $table->double('duration');
        $table->integer('preload_prefix_size')->unsigned();
        $table->double('video_start_ts');
        $table->text('video_codec');
        $table->boolean('voice')->default(false);
        $table->text('title');
        $table->text('performer');
        $table->text('waveform');
        $table->text('file_name');
        $table->boolean('free')->default(false);
        $table->boolean('text_color')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_web_pages', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_web_pages_url', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('url');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_game', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->bigInteger('access_hash')->unsigned();
        $table->text('short_name');
        $table->text('title');
        $table->text('description');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_photo', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('url');
        $table->bigInteger('access_hash')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('mime_type');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_photo_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->boolean('mask')->default(false);
        $table->text('alt');
        $table->boolean('round_message')->default(false);
        $table->boolean('supports_streaming')->default(false);
        $table->boolean('nosound')->default(false);
        $table->double('duration');
        $table->integer('preload_prefix_size')->unsigned();
        $table->double('video_start_ts');
        $table->text('video_codec');
        $table->boolean('voice')->default(false);
        $table->text('title');
        $table->text('performer');
        $table->text('waveform');
        $table->text('file_name');
        $table->boolean('free')->default(false);
        $table->boolean('text_color')->default(false);
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_photo_attributes_mask_coords', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('n')->unsigned();
        $table->double('x');
        $table->double('y');
        $table->double('zoom');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_extended_media', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('video_duration')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_extended_media_thumb', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->text('type');
        $table->integer('w')->unsigned();
        $table->integer('h')->unsigned();
        $table->integer('size')->unsigned();
        $table->text('bytes');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_extended_media_thumb_sizes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('closed')->default(false);
        $table->boolean('public_voters')->default(false);
        $table->boolean('multiple_choice')->default(false);
        $table->boolean('quiz')->default(false);
        $table->boolean('open_answers')->default(false);
        $table->boolean('revoting_disabled')->default(false);
        $table->boolean('shuffle_answers')->default(false);
        $table->boolean('hide_results_until_close')->default(false);
        $table->boolean('creator')->default(false);
        $table->boolean('subscribers_only')->default(false);
        $table->integer('close_period')->unsigned();
        $table->integer('close_date')->unsigned();
        $table->bigInteger('hash')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_question', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_question_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_answers', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('option');
        $table->unsignedTinyInteger('added_by_type');
        $table->bigInteger('added_by_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_answers_text', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_answers_text_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_poll_countries_iso2', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('value');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_results', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->boolean('min')->default(false);
        $table->boolean('has_unread_votes')->default(false);
        $table->boolean('can_view_stats')->default(false);
        $table->integer('total_voters')->unsigned();
        $table->unsignedTinyInteger('recent_voters_type');
        $table->bigInteger('recent_voters_id')->unsigned();
        $table->text('solution');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_results_results', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->boolean('chosen')->default(false);
        $table->boolean('correct')->default(false);
        $table->text('option');
        $table->integer('voters')->unsigned();
        $table->unsignedTinyInteger('recent_voters_type');
        $table->bigInteger('recent_voters_id')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_results_solution_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_game_outcome', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('seed');
        $table->bigInteger('stake_ton_amount')->unsigned();
        $table->bigInteger('ton_amount')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_story_items', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->integer('id')->unsigned();
        $table->text('constructor');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_extended_media_channels', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->bigInteger('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_countries_iso2', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('value');
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_winners', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->bigInteger('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_todo_lists', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
        $table->boolean('others_can_append')->default(false);
        $table->boolean('others_can_complete')->default(false);
        $table->primary(['account_id', 'todo_list_id']);
        });

        Schema::create('tf_todo_lists_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'todo_list_id']);
        });

        Schema::create('tf_todo_lists_title_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
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
        $table->primary(['account_id', 'todo_list_id', 'position']);
        });

        Schema::create('tf_todo_lists_list', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('id')->unsigned();
        $table->primary(['account_id', 'todo_list_id', 'position']);
        });

        Schema::create('tf_todo_lists_list_title', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'todo_list_id']);
        });

        Schema::create('tf_todo_lists_list_title_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('todo_list_id')->unsigned();
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
        $table->primary(['account_id', 'todo_list_id', 'position']);
        });

        Schema::create('tf_stars_transactions_extended_media_completions', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->unsignedTinyInteger('completed_by_type');
        $table->bigInteger('completed_by_id')->unsigned();
        $table->integer('date')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_subscription_period', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('subscription_period')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_giveaway_post_id', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('giveaway_post_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_stargift', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->boolean('limited')->default(false);
        $table->boolean('sold_out')->default(false);
        $table->boolean('birthday')->default(false);
        $table->boolean('require_premium')->default(false);
        $table->boolean('limited_per_user')->default(false);
        $table->boolean('peer_color_available')->default(false);
        $table->boolean('auction')->default(false);
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
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_stargift_background', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('center_color')->unsigned();
        $table->integer('edge_color')->unsigned();
        $table->integer('text_color')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_stargift_attributes', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_stargift_attributes_rarity', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->integer('permille')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_stargift_attributes_message', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('text');
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_stargift_attributes_message_entities', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
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
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_stargift_resell_amount', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_stargift_peer_color', function (Blueprint $table) {
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

        Schema::create('tf_stars_transactions_stargift_peer_color_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_stargift_peer_color_dark_colors', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('position');
        $table->integer('value')->unsigned();
        $table->primary(['account_id', 'id', 'position']);
        });

        Schema::create('tf_stars_transactions_floodskip_number', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('floodskip_number')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_commission_permille', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('starref_commission_permille')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_peer', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->unsignedTinyInteger('starref_peer_type');
        $table->bigInteger('starref_peer_id')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_starref_amount', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->text('constructor');
        $table->bigInteger('amount')->unsigned();
        $table->integer('nanos')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_paid_messages', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('paid_messages')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_premium_gift_months', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('premium_gift_months')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_ads_proceeds_from_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('ads_proceeds_from_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

        Schema::create('tf_stars_transactions_ads_proceeds_to_date', function (Blueprint $table) {
        $table->bigInteger('account_id')->unsigned();
        $table->bigInteger('id')->unsigned();
        $table->integer('ads_proceeds_to_date')->unsigned();
        $table->primary(['account_id', 'id']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('tf_stars_transactions');

        Schema::dropIfExists('tf_stars_transactions_amount');

        Schema::dropIfExists('tf_stars_transactions_peer');

        Schema::dropIfExists('tf_stars_transactions_title');

        Schema::dropIfExists('tf_stars_transactions_description');

        Schema::dropIfExists('tf_stars_transactions_photo');

        Schema::dropIfExists('tf_stars_transactions_photo_attributes');

        Schema::dropIfExists('tf_stars_transactions_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_transactions_transaction_date');

        Schema::dropIfExists('tf_stars_transactions_transaction_url');

        Schema::dropIfExists('tf_stars_transactions_bot_payload');

        Schema::dropIfExists('tf_stars_transactions_msg_id');

        Schema::dropIfExists('tf_stars_transactions_extended_media');

        Schema::dropIfExists('tf_stars_transactions_extended_media_geo');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_thumbs');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_thumbs_sizes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_video_thumbs');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_attributes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_web_pages');

        Schema::dropIfExists('tf_web_pages_url');

        Schema::dropIfExists('tf_stars_transactions_extended_media_game');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo_attributes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media_thumb');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media_thumb_sizes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_question');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_question_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers_text');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers_text_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_countries_iso2');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results_results');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results_solution_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_game_outcome');

        Schema::dropIfExists('tf_story_items');

        Schema::dropIfExists('tf_stars_transactions_extended_media_channels');

        Schema::dropIfExists('tf_stars_transactions_extended_media_countries_iso2');

        Schema::dropIfExists('tf_stars_transactions_extended_media_winners');

        Schema::dropIfExists('tf_todo_lists');

        Schema::dropIfExists('tf_todo_lists_title');

        Schema::dropIfExists('tf_todo_lists_title_entities');

        Schema::dropIfExists('tf_todo_lists_list');

        Schema::dropIfExists('tf_todo_lists_list_title');

        Schema::dropIfExists('tf_todo_lists_list_title_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_completions');

        Schema::dropIfExists('tf_stars_transactions_subscription_period');

        Schema::dropIfExists('tf_stars_transactions_giveaway_post_id');

        Schema::dropIfExists('tf_stars_transactions_stargift');

        Schema::dropIfExists('tf_stars_transactions_stargift_background');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_rarity');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_message');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_message_entities');

        Schema::dropIfExists('tf_stars_transactions_stargift_resell_amount');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color_colors');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color_dark_colors');

        Schema::dropIfExists('tf_stars_transactions_floodskip_number');

        Schema::dropIfExists('tf_stars_transactions_starref_commission_permille');

        Schema::dropIfExists('tf_stars_transactions_starref_peer');

        Schema::dropIfExists('tf_stars_transactions_starref_amount');

        Schema::dropIfExists('tf_stars_transactions_paid_messages');

        Schema::dropIfExists('tf_stars_transactions_premium_gift_months');

        Schema::dropIfExists('tf_stars_transactions_ads_proceeds_from_date');

        Schema::dropIfExists('tf_stars_transactions_ads_proceeds_to_date');

        Schema::dropIfExists('tf_stars_transactions_ads_proceeds_to_date');

        Schema::dropIfExists('tf_stars_transactions_ads_proceeds_from_date');

        Schema::dropIfExists('tf_stars_transactions_premium_gift_months');

        Schema::dropIfExists('tf_stars_transactions_paid_messages');

        Schema::dropIfExists('tf_stars_transactions_starref_amount');

        Schema::dropIfExists('tf_stars_transactions_starref_peer');

        Schema::dropIfExists('tf_stars_transactions_starref_commission_permille');

        Schema::dropIfExists('tf_stars_transactions_floodskip_number');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color_dark_colors');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color_colors');

        Schema::dropIfExists('tf_stars_transactions_stargift_peer_color');

        Schema::dropIfExists('tf_stars_transactions_stargift_resell_amount');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_message_entities');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_message');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes_rarity');

        Schema::dropIfExists('tf_stars_transactions_stargift_attributes');

        Schema::dropIfExists('tf_stars_transactions_stargift_background');

        Schema::dropIfExists('tf_stars_transactions_stargift');

        Schema::dropIfExists('tf_stars_transactions_giveaway_post_id');

        Schema::dropIfExists('tf_stars_transactions_subscription_period');

        Schema::dropIfExists('tf_stars_transactions_extended_media_completions');

        Schema::dropIfExists('tf_todo_lists_list_title_entities');

        Schema::dropIfExists('tf_todo_lists_list_title');

        Schema::dropIfExists('tf_todo_lists_list');

        Schema::dropIfExists('tf_todo_lists_title_entities');

        Schema::dropIfExists('tf_todo_lists_title');

        Schema::dropIfExists('tf_todo_lists');

        Schema::dropIfExists('tf_stars_transactions_extended_media_winners');

        Schema::dropIfExists('tf_stars_transactions_extended_media_countries_iso2');

        Schema::dropIfExists('tf_stars_transactions_extended_media_channels');

        Schema::dropIfExists('tf_story_items');

        Schema::dropIfExists('tf_stars_transactions_extended_media_game_outcome');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results_solution_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results_results');

        Schema::dropIfExists('tf_stars_transactions_extended_media_results');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_countries_iso2');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers_text_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers_text');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_answers');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_question_entities');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll_question');

        Schema::dropIfExists('tf_stars_transactions_extended_media_poll');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media_thumb_sizes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media_thumb');

        Schema::dropIfExists('tf_stars_transactions_extended_media_extended_media');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo_attributes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_photo');

        Schema::dropIfExists('tf_stars_transactions_extended_media_game');

        Schema::dropIfExists('tf_web_pages_url');

        Schema::dropIfExists('tf_web_pages');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_attributes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_video_thumbs_background_colors');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_video_thumbs');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_thumbs_sizes');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents_thumbs');

        Schema::dropIfExists('tf_stars_transactions_extended_media_alt_documents');

        Schema::dropIfExists('tf_stars_transactions_extended_media_geo');

        Schema::dropIfExists('tf_stars_transactions_extended_media');

        Schema::dropIfExists('tf_stars_transactions_msg_id');

        Schema::dropIfExists('tf_stars_transactions_bot_payload');

        Schema::dropIfExists('tf_stars_transactions_transaction_url');

        Schema::dropIfExists('tf_stars_transactions_transaction_date');

        Schema::dropIfExists('tf_stars_transactions_photo_attributes_mask_coords');

        Schema::dropIfExists('tf_stars_transactions_photo_attributes');

        Schema::dropIfExists('tf_stars_transactions_photo');

        Schema::dropIfExists('tf_stars_transactions_description');

        Schema::dropIfExists('tf_stars_transactions_title');

        Schema::dropIfExists('tf_stars_transactions_peer');

        Schema::dropIfExists('tf_stars_transactions_amount');

        Schema::dropIfExists('tf_stars_transactions');

    }
};
