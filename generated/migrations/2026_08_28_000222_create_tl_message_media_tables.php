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
        Schema::create('tl_message_media_message_media_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone_number')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('vcard')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_83bbd79b6dcdaa2d94379ef5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2de30ebbd4495a2eb6801407');
            $table->index('account_id', 'ix_dbac7cc104717730826f243f');
        });
        Schema::create('tl_message_media_message_media_dice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('tl_value')->nullable();
            $table->text('emoticon')->nullable();
            $table->bigInteger('game_outcome')->nullable();
            $table->index('game_outcome', 'ix_5ad4e5744d6b5ccd4644be16');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d847cd407279e616b456550d');
            $table->index('account_id', 'ix_03538b07d7399bba1653ea01');
        });
        Schema::create('tl_message_media_message_media_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('nopremium')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->boolean('video')->default(false);
            $table->boolean('round')->default(false);
            $table->boolean('voice')->default(false);
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_625a3679c88bfb84ccc671eb');
            $table->bigInteger('video_cover')->nullable();
            $table->index('video_cover', 'ix_d1aff8f950ebdffda6167654');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_943b680765fa14f1b7192313');
            $table->index('account_id', 'ix_363b01cd06e679f5c090b144');
        });
        Schema::create('tl_message_media_message_media_document__alt_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_document', 'id', 'fk_d5124c02e16ace5f6c181a87')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e984594709e86379b68a');
            $table->index('account_id', 'ix_ad4866692e071c64126d2295');
        });
        Schema::create('tl_message_media_message_media_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_479f6486a6c1fa141de8b1bb');
            $table->index('account_id', 'ix_3bee878981bde9c8d2df4376');
        });
        Schema::create('tl_message_media_message_media_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('game')->nullable();
            $table->index('game', 'ix_f8156a2065316b26955c69a8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c90330262727bdf6af901805');
            $table->index('account_id', 'ix_bb6a39435c3d8c8e26fdfadb');
        });
        Schema::create('tl_message_media_message_media_geo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_93388ce8f90589d68e3ddce2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4f42572323bcc73a2ff76897');
            $table->index('account_id', 'ix_54c70a2dd3c0b7983d8452ed');
        });
        Schema::create('tl_message_media_message_media_geo_live', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_361cad774d35d1be960a9c40');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_addf5e456627763d2242e314');
            $table->index('account_id', 'ix_2231a1c23aa19250d712836e');
        });
        Schema::create('tl_message_media_message_media_giveaway', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('winners_are_visible')->default(false);
            $table->text('prize_description')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('months')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->integer('until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f7f4127b7973989f14cdff9c');
            $table->index('account_id', 'ix_13082ea42a0813e8925ef8c1');
        });
        Schema::create('tl_message_media_message_media_giveaway__channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_giveaway', 'id', 'fk_c18e49124b1c34eb1857f35b')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_02020231a163fbf22daf');
            $table->index('account_id', 'ix_b2698b0406efa56e472e7165');
        });
        Schema::create('tl_message_media_message_media_giveaway__countries_iso2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_giveaway', 'id', 'fk_a279df1e8015d163345923c2')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a810a8221c794936e57b');
            $table->index('account_id', 'ix_85ac745440c55e4079531dfb');
        });
        Schema::create('tl_message_media_message_media_giveaway_results', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('only_new_subscribers')->default(false);
            $table->boolean('refunded')->default(false);
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_ead10a47dbae4eb0006e1b26');
            $table->integer('additional_peers_count')->nullable();
            $table->integer('launch_msg_id')->nullable();
            $table->integer('winners_count')->nullable();
            $table->integer('unclaimed_count')->nullable();
            $table->integer('months')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->text('prize_description')->nullable();
            $table->integer('until_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1e5443aadf24c95add9757c0');
            $table->index('account_id', 'ix_d621aa266028f2c63f1af58c');
        });
        Schema::create('tl_message_media_message_media_giveaway_results__winners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_giveaway_results', 'id', 'fk_1c202c6465e066ba44f4b21d')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c59bf43698f6a5238a40');
            $table->index('account_id', 'ix_2526fe41341bef954f6abb7a');
        });
        Schema::create('tl_message_media_message_media_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('shipping_address_requested')->default(false);
            $table->boolean('test')->default(false);
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_7a184d313e9be865850d79ff');
            $table->integer('receipt_msg_id')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('total_amount')->nullable();
            $table->text('start_param')->nullable();
            $table->bigInteger('extended_media')->nullable();
            $table->index('extended_media', 'ix_158085a18d97588ae3f09a12');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7fa4840ad3a51320d7a957ef');
            $table->index('account_id', 'ix_fdf5100dbaee7c02512329b0');
        });
        Schema::create('tl_message_media_message_media_paid_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stars_amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_98b72e4e6fdc0b9fc5737a33');
            $table->index('account_id', 'ix_058cddea944a556dc5e86b83');
        });
        Schema::create('tl_message_media_message_media_paid_media__extended_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_paid_media', 'id', 'fk_5cf583b491ccfd6b3e743867')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f210dafeb9d53d71b7c6');
            $table->index('account_id', 'ix_7aab8a05785e8dafa9f9a2fe');
        });
        Schema::create('tl_message_media_message_media_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_6e1ff9c6fbde5f1b6a7ba169');
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('video')->nullable();
            $table->index('video', 'ix_705bf8142e4f3c09ff292d28');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dc6fbb2453bc2c26349b6d53');
            $table->index('account_id', 'ix_8e8fb001ec6d2cf45a8af31a');
        });
        Schema::create('tl_message_media_message_media_poll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('poll')->nullable();
            $table->index('poll', 'ix_5b33ad738c9d27041d4ea628');
            $table->bigInteger('results')->nullable();
            $table->index('results', 'ix_0c5dd6323218a5ebf33378ce');
            $table->bigInteger('attached_media')->nullable();
            $table->index('attached_media', 'ix_7ec7901735ea463e61f3beb0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_696bb60714719acc35008206');
            $table->index('account_id', 'ix_4e024a7c1b2eb4cde628866c');
        });
        Schema::create('tl_message_media_message_media_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('via_mention')->default(false);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_1b9eccec8e4a70aeccdd03b8');
            $table->integer('tl_id')->nullable();
            $table->bigInteger('story')->nullable();
            $table->index('story', 'ix_73683d0593a15b6411216801');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ace6e1496347cdd15e76314a');
            $table->index('account_id', 'ix_26ededdb836316359e346337');
            $table->unique(['peer', 'account_id'], 'ux_3ba71b91a66f7d3a21eb');
        });
        Schema::create('tl_message_media_message_media_to_do', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('todo')->nullable();
            $table->index('todo', 'ix_827690c81210730fa168475a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_54bebc267592d1e093d41d17');
            $table->index('account_id', 'ix_fc6f48136f6fb8b908174458');
        });
        Schema::create('tl_message_media_message_media_to_do__completions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_message_media_message_media_to_do', 'id', 'fk_2c945a922c020f8808235f0a')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9c6eab3e3c4e0dbadbe1');
            $table->index('account_id', 'ix_6ea245c653ee361fc69bd1c7');
        });
        Schema::create('tl_message_media_message_media_unsupported', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ceaf6bc79b173bb67dae1077');
            $table->index('account_id', 'ix_614cd7b7f73c1d3b63f51ea6');
        });
        Schema::create('tl_message_media_message_media_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_308695144409d82f0b4c70f9');
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->text('venue_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c447b72936c32042b85eff30');
            $table->index('account_id', 'ix_4ac58d4730696c7633ae327f');
        });
        Schema::create('tl_message_media_message_media_video_stream', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('rtmp_stream')->default(false);
            $table->bigInteger('call')->nullable();
            $table->index('call', 'ix_65b606aa087d413b30f3a37b');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c255675f47fdba3342d5cf0a');
            $table->index('account_id', 'ix_0878a0275613f7c490fc3204');
        });
        Schema::create('tl_message_media_message_media_web_page', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('manual')->default(false);
            $table->boolean('safe')->default(false);
            $table->bigInteger('webpage')->nullable();
            $table->index('webpage', 'ix_47b9e1e66f252c70e51a2260');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ca09d25789c186aa58fea771');
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
    }
};
