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
        Schema::create('tl_message_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_media_message_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_value');
            $table->text('emoticon');
            $table->uuid('game_outcome')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('nopremium')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('round')->default(false);
            $table->boolean('voice')->default(false);
            $table->uuid('document')->nullable();
            $table->uuid('video_cover')->nullable();
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_document__alt_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e984594709e86379b68a');
        });
        Schema::create('tl_message_media_message_media_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('game');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_geo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('geo');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_geo_live', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo');
            $table->integer('heading')->nullable();
            $table->integer('period');
            $table->integer('proximity_notification_radius')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_giveaway', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->text('prize_description')->nullable();
            $table->integer('quantity');
            $table->integer('months')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->integer('until_date');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_giveaway__channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_02020231a163fbf22daf');
        });
        Schema::create('tl_message_media_message_media_giveaway__countries_iso2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a810a8221c794936e57b');
        });
        Schema::create('tl_message_media_message_media_giveaway_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('refunded')->default(false);
            $table->bigInteger('channel_id');
            $table->integer('additional_peers_count')->nullable();
            $table->integer('launch_msg_id');
            $table->integer('winners_count');
            $table->integer('unclaimed_count');
            $table->integer('months')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->text('prize_description')->nullable();
            $table->integer('until_date');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_giveaway_results__winners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway_results')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c59bf43698f6a5238a40');
        });
        Schema::create('tl_message_media_message_media_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->integer('receipt_msg_id')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('start_param');
            $table->uuid('extended_media')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_paid_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('stars_amount');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_paid_media__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_paid_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f210dafeb9d53d71b7c6');
        });
        Schema::create('tl_message_media_message_media_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('photo')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('poll');
            $table->uuid('results');
            $table->uuid('attached_media')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_mention')->default(false);
            $table->uuid('peer');
            $table->integer('tl_id');
            $table->uuid('story')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_to_do', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('todo');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_to_do__completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_to_do')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9c6eab3e3c4e0dbadbe1');
        });
        Schema::create('tl_message_media_message_media_unsupported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('geo');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_video_stream', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtmp_stream')->default(false);
            $table->uuid('call');
            $table->timestamps();
        });
        Schema::create('tl_message_media_message_media_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->uuid('webpage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_media_message_media_web_page');
        Schema::dropIfExists('tl_message_media_message_media_video_stream');
        Schema::dropIfExists('tl_message_media_message_media_venue');
        Schema::dropIfExists('tl_message_media_message_media_unsupported');
        Schema::dropIfExists('tl_message_media_message_media_to_do__completions');
        Schema::dropIfExists('tl_message_media_message_media_to_do');
        Schema::dropIfExists('tl_message_media_message_media_story');
        Schema::dropIfExists('tl_message_media_message_media_poll');
        Schema::dropIfExists('tl_message_media_message_media_photo');
        Schema::dropIfExists('tl_message_media_message_media_paid_media__extended_media');
        Schema::dropIfExists('tl_message_media_message_media_paid_media');
        Schema::dropIfExists('tl_message_media_message_media_invoice');
        Schema::dropIfExists('tl_message_media_message_media_giveaway_results__winners');
        Schema::dropIfExists('tl_message_media_message_media_giveaway_results');
        Schema::dropIfExists('tl_message_media_message_media_giveaway__countries_iso2');
        Schema::dropIfExists('tl_message_media_message_media_giveaway__channels');
        Schema::dropIfExists('tl_message_media_message_media_giveaway');
        Schema::dropIfExists('tl_message_media_message_media_geo_live');
        Schema::dropIfExists('tl_message_media_message_media_geo');
        Schema::dropIfExists('tl_message_media_message_media_game');
        Schema::dropIfExists('tl_message_media_message_media_empty');
        Schema::dropIfExists('tl_message_media_message_media_document__alt_documents');
        Schema::dropIfExists('tl_message_media_message_media_document');
        Schema::dropIfExists('tl_message_media_message_media_dice');
        Schema::dropIfExists('tl_message_media_message_media_contact');
        Schema::dropIfExists('tl_message_media');
    }
};
