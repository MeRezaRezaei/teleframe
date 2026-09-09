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
            $table->index('constructor_id', 'ix_eee516aa8f0c81227b3d4a40');
            $table->index('account_id', 'ix_8f24097d4502f72dfd5a2c1b');
        });
        Schema::create('tl_message_media_message_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_83bbd79b6dcdaa2d94379ef5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dbac7cc104717730826f243f');
        });
        Schema::create('tl_message_media_message_media_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_value');
            $table->text('emoticon');
            $table->uuid('game_outcome')->nullable();
            $table->index('game_outcome', 'ix_5ad4e5744d6b5ccd4644be16');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_03538b07d7399bba1653ea01');
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
            $table->index('document', 'ix_625a3679c88bfb84ccc671eb');
            $table->uuid('video_cover')->nullable();
            $table->index('video_cover', 'ix_d1aff8f950ebdffda6167654');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_363b01cd06e679f5c090b144');
        });
        Schema::create('tl_message_media_message_media_document__alt_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e984594709e86379b68a');
            $table->index('account_id', 'ix_ad4866692e071c64126d2295');
        });
        Schema::create('tl_message_media_message_media_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3bee878981bde9c8d2df4376');
        });
        Schema::create('tl_message_media_message_media_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('game');
            $table->index('game', 'ix_f8156a2065316b26955c69a8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bb6a39435c3d8c8e26fdfadb');
        });
        Schema::create('tl_message_media_message_media_geo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('geo');
            $table->index('geo', 'ix_93388ce8f90589d68e3ddce2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_54c70a2dd3c0b7983d8452ed');
        });
        Schema::create('tl_message_media_message_media_geo_live', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo');
            $table->index('geo', 'ix_361cad774d35d1be960a9c40');
            $table->integer('heading')->nullable();
            $table->integer('period');
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2231a1c23aa19250d712836e');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_13082ea42a0813e8925ef8c1');
        });
        Schema::create('tl_message_media_message_media_giveaway__channels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_02020231a163fbf22daf');
            $table->index('account_id', 'ix_b2698b0406efa56e472e7165');
        });
        Schema::create('tl_message_media_message_media_giveaway__countries_iso2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a810a8221c794936e57b');
            $table->index('account_id', 'ix_85ac745440c55e4079531dfb');
        });
        Schema::create('tl_message_media_message_media_giveaway_results', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('refunded')->default(false);
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_ead10a47dbae4eb0006e1b26');
            $table->integer('additional_peers_count')->nullable();
            $table->integer('launch_msg_id');
            $table->integer('winners_count');
            $table->integer('unclaimed_count');
            $table->integer('months')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->text('prize_description')->nullable();
            $table->integer('until_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d621aa266028f2c63f1af58c');
        });
        Schema::create('tl_message_media_message_media_giveaway_results__winners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_giveaway_results')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c59bf43698f6a5238a40');
            $table->index('account_id', 'ix_2526fe41341bef954f6abb7a');
        });
        Schema::create('tl_message_media_message_media_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_7a184d313e9be865850d79ff');
            $table->integer('receipt_msg_id')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('start_param');
            $table->uuid('extended_media')->nullable();
            $table->index('extended_media', 'ix_158085a18d97588ae3f09a12');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fdf5100dbaee7c02512329b0');
        });
        Schema::create('tl_message_media_message_media_paid_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('stars_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_058cddea944a556dc5e86b83');
        });
        Schema::create('tl_message_media_message_media_paid_media__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_paid_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f210dafeb9d53d71b7c6');
            $table->index('account_id', 'ix_7aab8a05785e8dafa9f9a2fe');
        });
        Schema::create('tl_message_media_message_media_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_6e1ff9c6fbde5f1b6a7ba169');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->index('video', 'ix_705bf8142e4f3c09ff292d28');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8e8fb001ec6d2cf45a8af31a');
        });
        Schema::create('tl_message_media_message_media_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('poll');
            $table->index('poll', 'ix_5b33ad738c9d27041d4ea628');
            $table->uuid('results');
            $table->index('results', 'ix_0c5dd6323218a5ebf33378ce');
            $table->uuid('attached_media')->nullable();
            $table->index('attached_media', 'ix_7ec7901735ea463e61f3beb0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4e024a7c1b2eb4cde628866c');
        });
        Schema::create('tl_message_media_message_media_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_mention')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_1b9eccec8e4a70aeccdd03b8');
            $table->integer('tl_id');
            $table->uuid('story')->nullable();
            $table->index('story', 'ix_73683d0593a15b6411216801');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_26ededdb836316359e346337');
            $table->unique(['account_id', 'peer', 'tl_id'], 'ux_3ba71b91a66f7d3a21eb');
        });
        Schema::create('tl_message_media_message_media_to_do', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('todo');
            $table->index('todo', 'ix_827690c81210730fa168475a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc6f48136f6fb8b908174458');
        });
        Schema::create('tl_message_media_message_media_to_do__completions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_message_media_message_media_to_do')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9c6eab3e3c4e0dbadbe1');
            $table->index('account_id', 'ix_6ea245c653ee361fc69bd1c7');
        });
        Schema::create('tl_message_media_message_media_unsupported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_614cd7b7f73c1d3b63f51ea6');
        });
        Schema::create('tl_message_media_message_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->uuid('geo');
            $table->index('geo', 'ix_308695144409d82f0b4c70f9');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4ac58d4730696c7633ae327f');
        });
        Schema::create('tl_message_media_message_media_video_stream', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtmp_stream')->default(false);
            $table->uuid('call');
            $table->index('call', 'ix_65b606aa087d413b30f3a37b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0878a0275613f7c490fc3204');
        });
        Schema::create('tl_message_media_message_media_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->uuid('webpage');
            $table->index('webpage', 'ix_47b9e1e66f252c70e51a2260');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_91129e2096ae353f865dff67');
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
