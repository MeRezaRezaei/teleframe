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
        Schema::create('tl_input_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_434387d1c20f2f48c8232c1e');
            $table->index('account_id', 'ix_116d74b6da01fbb1c4d54da4');
        });
        Schema::create('tl_input_media_input_media_contact', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fb2af9c7094c577c368db3ab');
        });
        Schema::create('tl_input_media_input_media_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('emoticon');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_412a8cecf6d9db7af24c88ef');
        });
        Schema::create('tl_input_media_input_media_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->uuid('tl_id');
            $table->index('tl_id', 'ix_b17a056db9d81ed1103fd21d');
            $table->uuid('video_cover')->nullable();
            $table->index('video_cover', 'ix_691fbede6fce006bc13e2433');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->text('query')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d97cc0fe3edc73c0fb7d00c4');
        });
        Schema::create('tl_input_media_input_media_document_external', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video_cover')->nullable();
            $table->index('video_cover', 'ix_7fe9c90fca2242432725ac4c');
            $table->integer('video_timestamp')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a2176f57fd5ae90baa2a81ac');
        });
        Schema::create('tl_input_media_input_media_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_faf817a0e88828f9563b9618');
        });
        Schema::create('tl_input_media_input_media_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('tl_id');
            $table->index('tl_id', 'ix_6f85bc9cb5006a8fb44fc44c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_20699571d8d510da8643c541');
        });
        Schema::create('tl_input_media_input_media_geo_live', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('stopped')->default(false);
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_896cf51db485763ec49bc963');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_97b323a53a8cfca2583db9f9');
        });
        Schema::create('tl_input_media_input_media_geo_point', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_c87d3596ba9ff18ad7a30069');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_83c68c071e3957f07f81fb62');
        });
        Schema::create('tl_input_media_input_media_invoice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_7f92cf5da616a2cde0c542ec');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_8e9daf9659c5893bed56f433');
            $table->binary('payload');
            $table->text('provider')->nullable();
            $table->uuid('provider_data');
            $table->index('provider_data', 'ix_a8725153e5456aa965760c57');
            $table->text('start_param')->nullable();
            $table->uuid('extended_media')->nullable();
            $table->index('extended_media', 'ix_9ba69f73d28dae4af37e58b8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a409068eda4252133db6ee0');
        });
        Schema::create('tl_input_media_input_media_paid_media', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars_amount');
            $table->text('payload')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_83ccb26808735a653b13c4f9');
        });
        Schema::create('tl_input_media_input_media_paid_media__extended_media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_paid_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ed7f8d09328ef8ba3879');
            $table->index('account_id', 'ix_df231dfa1932870dba7acb36');
        });
        Schema::create('tl_input_media_input_media_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('tl_id');
            $table->index('tl_id', 'ix_b1a04ac78479bf2853190f46');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->index('video', 'ix_9c4916abb20fda48dc382beb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e06a5e22a3784ed1b7128330');
        });
        Schema::create('tl_input_media_input_media_photo_external', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url');
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2b473a43ad96e3f3940ba9d3');
        });
        Schema::create('tl_input_media_input_media_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('poll');
            $table->index('poll', 'ix_de7d02f0afb854d1947ec10c');
            $table->uuid('attached_media')->nullable();
            $table->index('attached_media', 'ix_161ce7846c54a064491ad7eb');
            $table->text('solution')->nullable();
            $table->uuid('solution_media')->nullable();
            $table->index('solution_media', 'ix_e2f9bd6b462c9054571e4dcc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c2e421f13f5bd678a4258327');
        });
        Schema::create('tl_input_media_input_media_poll__correct_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_78cba706c48a3dfe8d3c');
            $table->index('account_id', 'ix_0c32dd8acd5cd37b113dbdc1');
        });
        Schema::create('tl_input_media_input_media_poll__solution_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_188ba232fcc4a28f576a');
            $table->index('account_id', 'ix_121733e8fa697c76accc7e12');
        });
        Schema::create('tl_input_media_input_media_stake_dice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->text('game_hash');
            $table->bigInteger('ton_amount');
            $table->binary('client_seed');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6605f900906f1d444b893b3b');
        });
        Schema::create('tl_input_media_input_media_story', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_f471f2d3f28c1aeb113ff9a3');
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_50f5bcc6fbd38e2ebd0bd0db');
            $table->unique(['account_id', 'peer', 'tl_id'], 'ux_5ef555e68bd42bd0da56');
        });
        Schema::create('tl_input_media_input_media_todo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('todo');
            $table->index('todo', 'ix_b7156e378e99dc202364cb33');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_671bccfe01f8ae5306b20383');
        });
        Schema::create('tl_input_media_input_media_uploaded_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('nosound_video')->default(false);
            $table->boolean('force_file')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->uuid('file');
            $table->index('file', 'ix_5c5c90f6391405cf63e95724');
            $table->uuid('thumb')->nullable();
            $table->index('thumb', 'ix_e06f13cac9fcdaa7d00f02d6');
            $table->text('mime_type');
            $table->uuid('video_cover')->nullable();
            $table->index('video_cover', 'ix_3d779b73f94b4dbb623a2813');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_48beff2910cc05b11e03f1aa');
        });
        Schema::create('tl_input_media_input_media_uploaded_document__attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_477e84726d520584c1bb');
            $table->index('account_id', 'ix_9d62e217508ac3dd2c87aaea');
        });
        Schema::create('tl_input_media_input_media_uploaded_document__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2d772bbcfebccc32b5b1');
            $table->index('account_id', 'ix_8d4a15bdf053720ed6cafe79');
        });
        Schema::create('tl_input_media_input_media_uploaded_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->uuid('file');
            $table->index('file', 'ix_e76b2cc9e18bf9cfe2f64965');
            $table->integer('ttl_seconds')->nullable();
            $table->uuid('video')->nullable();
            $table->index('video', 'ix_f50bc9327bd3c1dbd07f3403');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_65e34f9d8db0718181f58274');
        });
        Schema::create('tl_input_media_input_media_uploaded_photo__stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_media_input_media_uploaded_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ee563364a426bb5de181');
            $table->index('account_id', 'ix_bcf897996b653fa54a21c15e');
        });
        Schema::create('tl_input_media_input_media_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_255176dec69204a4d29cc510');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_54ec28f2b7ba72d1a03bb46f');
        });
        Schema::create('tl_input_media_input_media_web_page', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_media')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('optional')->default(false);
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eb484ac56cbdbe9e6f2e7cd4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_media_input_media_web_page');
        Schema::dropIfExists('tl_input_media_input_media_venue');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_photo__stickers');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_photo');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document__stickers');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document__attributes');
        Schema::dropIfExists('tl_input_media_input_media_uploaded_document');
        Schema::dropIfExists('tl_input_media_input_media_todo');
        Schema::dropIfExists('tl_input_media_input_media_story');
        Schema::dropIfExists('tl_input_media_input_media_stake_dice');
        Schema::dropIfExists('tl_input_media_input_media_poll__solution_entities');
        Schema::dropIfExists('tl_input_media_input_media_poll__correct_answers');
        Schema::dropIfExists('tl_input_media_input_media_poll');
        Schema::dropIfExists('tl_input_media_input_media_photo_external');
        Schema::dropIfExists('tl_input_media_input_media_photo');
        Schema::dropIfExists('tl_input_media_input_media_paid_media__extended_media');
        Schema::dropIfExists('tl_input_media_input_media_paid_media');
        Schema::dropIfExists('tl_input_media_input_media_invoice');
        Schema::dropIfExists('tl_input_media_input_media_geo_point');
        Schema::dropIfExists('tl_input_media_input_media_geo_live');
        Schema::dropIfExists('tl_input_media_input_media_game');
        Schema::dropIfExists('tl_input_media_input_media_empty');
        Schema::dropIfExists('tl_input_media_input_media_document_external');
        Schema::dropIfExists('tl_input_media_input_media_document');
        Schema::dropIfExists('tl_input_media_input_media_dice');
        Schema::dropIfExists('tl_input_media_input_media_contact');
        Schema::dropIfExists('tl_input_media');
    }
};
