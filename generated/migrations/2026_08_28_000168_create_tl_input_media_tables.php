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
        Schema::create('tl_input_media_input_media_contact', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone_number')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('vcard')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c679ef02a68e95af4783a490');
            $table->index('account_id', 'ix_fb2af9c7094c577c368db3ab');
        });
        Schema::create('tl_input_media_input_media_dice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8af4880e2f08a63fb253a9cf');
            $table->index('account_id', 'ix_412a8cecf6d9db7af24c88ef');
        });
        Schema::create('tl_input_media_input_media_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_b17a056db9d81ed1103fd21d');
            $table->bigInteger('video_cover')->nullable();
            $table->index('video_cover', 'ix_691fbede6fce006bc13e2433');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->text('query')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d9bfcd55caaa97952539c4de');
            $table->index('account_id', 'ix_d97cc0fe3edc73c0fb7d00c4');
        });
        Schema::create('tl_input_media_input_media_document_external', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('video_cover')->nullable();
            $table->index('video_cover', 'ix_7fe9c90fca2242432725ac4c');
            $table->integer('video_timestamp')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cf055b3cb10faef14e316d73');
            $table->index('account_id', 'ix_a2176f57fd5ae90baa2a81ac');
        });
        Schema::create('tl_input_media_input_media_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2ad7ddd0806b74be1622ecbd');
            $table->index('account_id', 'ix_faf817a0e88828f9563b9618');
        });
        Schema::create('tl_input_media_input_media_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_6f85bc9cb5006a8fb44fc44c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0ad2f512f0da2c61dab8a023');
            $table->index('account_id', 'ix_20699571d8d510da8643c541');
        });
        Schema::create('tl_input_media_input_media_geo_live', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('stopped')->default(false);
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_896cf51db485763ec49bc963');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e329137c6f9f44936e7b18cf');
            $table->index('account_id', 'ix_97b323a53a8cfca2583db9f9');
        });
        Schema::create('tl_input_media_input_media_geo_point', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_c87d3596ba9ff18ad7a30069');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_101a234c7bb57d02879a383c');
            $table->index('account_id', 'ix_83c68c071e3957f07f81fb62');
        });
        Schema::create('tl_input_media_input_media_invoice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_7f92cf5da616a2cde0c542ec');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_8e9daf9659c5893bed56f433');
            $table->binary('payload')->nullable();
            $table->text('provider')->nullable();
            $table->bigInteger('provider_data')->nullable();
            $table->index('provider_data', 'ix_a8725153e5456aa965760c57');
            $table->text('start_param')->nullable();
            $table->bigInteger('extended_media')->nullable();
            $table->index('extended_media', 'ix_9ba69f73d28dae4af37e58b8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e3e6bda5b1e4551b16d5f043');
            $table->index('account_id', 'ix_1a409068eda4252133db6ee0');
        });
        Schema::create('tl_input_media_input_media_paid_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('stars_amount')->nullable();
            $table->text('payload')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f8c2c4425e5f63c8df97bbd5');
            $table->index('account_id', 'ix_83ccb26808735a653b13c4f9');
        });
        Schema::create('tl_input_media_input_media_paid_media__extended_media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9bb03206db7e799750703db4')->references('id')->on('tl_input_media_input_media_paid_media')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ed7f8d09328ef8ba3879');
            $table->index('account_id', 'ix_df231dfa1932870dba7acb36');
        });
        Schema::create('tl_input_media_input_media_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->bigInteger('tl_id')->nullable();
            $table->index('tl_id', 'ix_b1a04ac78479bf2853190f46');
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('video')->nullable();
            $table->index('video', 'ix_9c4916abb20fda48dc382beb');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e44c3c59f494f04cc151411');
            $table->index('account_id', 'ix_e06a5e22a3784ed1b7128330');
        });
        Schema::create('tl_input_media_input_media_photo_external', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->text('url')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a3ea2b8f89175ba6e797e531');
            $table->index('account_id', 'ix_2b473a43ad96e3f3940ba9d3');
        });
        Schema::create('tl_input_media_input_media_poll', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('poll')->nullable();
            $table->index('poll', 'ix_de7d02f0afb854d1947ec10c');
            $table->bigInteger('attached_media')->nullable();
            $table->index('attached_media', 'ix_161ce7846c54a064491ad7eb');
            $table->text('solution')->nullable();
            $table->bigInteger('solution_media')->nullable();
            $table->index('solution_media', 'ix_e2f9bd6b462c9054571e4dcc');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_479f693e898b6ac02fa674e4');
            $table->index('account_id', 'ix_c2e421f13f5bd678a4258327');
        });
        Schema::create('tl_input_media_input_media_poll__correct_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f0ec6603468caa10cd34b1d6')->references('id')->on('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_78cba706c48a3dfe8d3c');
            $table->index('account_id', 'ix_0c32dd8acd5cd37b113dbdc1');
        });
        Schema::create('tl_input_media_input_media_poll__solution_entities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a571b054474079759bbd126c')->references('id')->on('tl_input_media_input_media_poll')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_188ba232fcc4a28f576a');
            $table->index('account_id', 'ix_121733e8fa697c76accc7e12');
        });
        Schema::create('tl_input_media_input_media_stake_dice', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('game_hash')->nullable();
            $table->bigInteger('ton_amount')->nullable();
            $table->binary('client_seed')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_91caaeca0d4fee65ba729665');
            $table->index('account_id', 'ix_6605f900906f1d444b893b3b');
        });
        Schema::create('tl_input_media_input_media_story', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_f471f2d3f28c1aeb113ff9a3');
            $table->integer('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_696b004091036caa27eb72be');
            $table->index('account_id', 'ix_50f5bcc6fbd38e2ebd0bd0db');
        });
        Schema::create('tl_input_media_input_media_todo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('todo')->nullable();
            $table->index('todo', 'ix_b7156e378e99dc202364cb33');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d5c32efcb1a25e2003bb6855');
            $table->index('account_id', 'ix_671bccfe01f8ae5306b20383');
        });
        Schema::create('tl_input_media_input_media_uploaded_document', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('nosound_video')->default(false);
            $table->boolean('force_file')->default(false);
            $table->boolean('spoiler')->default(false);
            $table->bigInteger('file')->nullable();
            $table->index('file', 'ix_5c5c90f6391405cf63e95724');
            $table->bigInteger('thumb')->nullable();
            $table->index('thumb', 'ix_e06f13cac9fcdaa7d00f02d6');
            $table->text('mime_type')->nullable();
            $table->bigInteger('video_cover')->nullable();
            $table->index('video_cover', 'ix_3d779b73f94b4dbb623a2813');
            $table->integer('video_timestamp')->nullable();
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_be200a05e4bd23e1832f9c15');
            $table->index('account_id', 'ix_48beff2910cc05b11e03f1aa');
        });
        Schema::create('tl_input_media_input_media_uploaded_document__attributes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c40d48c040e2cb0af702a862')->references('id')->on('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_477e84726d520584c1bb');
            $table->index('account_id', 'ix_9d62e217508ac3dd2c87aaea');
        });
        Schema::create('tl_input_media_input_media_uploaded_document__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c94039d4cf2605229fee96e1')->references('id')->on('tl_input_media_input_media_uploaded_document')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2d772bbcfebccc32b5b1');
            $table->index('account_id', 'ix_8d4a15bdf053720ed6cafe79');
        });
        Schema::create('tl_input_media_input_media_uploaded_photo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('spoiler')->default(false);
            $table->boolean('live_photo')->default(false);
            $table->bigInteger('file')->nullable();
            $table->index('file', 'ix_e76b2cc9e18bf9cfe2f64965');
            $table->integer('ttl_seconds')->nullable();
            $table->bigInteger('video')->nullable();
            $table->index('video', 'ix_f50bc9327bd3c1dbd07f3403');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e0fa97a08565ac2a5ea072a4');
            $table->index('account_id', 'ix_65e34f9d8db0718181f58274');
        });
        Schema::create('tl_input_media_input_media_uploaded_photo__stickers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_bee3e6100ea0ecd17b89cc29')->references('id')->on('tl_input_media_input_media_uploaded_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ee563364a426bb5de181');
            $table->index('account_id', 'ix_bcf897996b653fa54a21c15e');
        });
        Schema::create('tl_input_media_input_media_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_255176dec69204a4d29cc510');
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->text('venue_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c241d2d5f79f276f73bfc02');
            $table->index('account_id', 'ix_54ec28f2b7ba72d1a03bb46f');
        });
        Schema::create('tl_input_media_input_media_web_page', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('optional')->default(false);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9b10141366053960d70b6e73');
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
    }
};
