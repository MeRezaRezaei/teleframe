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
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_game', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_0ccb57647cf4c1b239458420');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b3eb0222e46f11d34be6e9d');
            $table->index('account_id', 'ix_b11dccc7a2c87854b468a724');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->text('message')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_7c23a0c8354aac05e9ba06e5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_30e3bfd20f06dd0f24198db3');
            $table->index('account_id', 'ix_7a29a07d34ad2e72c05d44ed');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__04c37cd8c27e', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4', 'id', 'fk_5cde0dec88a2e125449c6a1f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1b042b3dbc98b5766579');
            $table->index('account_id', 'ix_f124c927fe5fb0fd76d46d7c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1cad71cb92f3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('phone_number')->nullable();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('vcard')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_257ec508857bba0d09531b29');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bb754f14aa03f0e5f8f8847c');
            $table->index('account_id', 'ix_07943325ca781f5f7b3a773c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a1361e727854', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_da9ebb93297126ce0d7cd40e');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_297849aec9a72edc4a1affd3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_28c6d9f501cc9f4776e3f002');
            $table->index('account_id', 'ix_3c43cb12eb3fa4effb828e9c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__13ed224796c5', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_225b5caa2aea909d791afff1');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_075a2245e2bb7666325a325f');
            $table->binary('payload')->nullable();
            $table->text('provider')->nullable();
            $table->bigInteger('provider_data')->nullable();
            $table->index('provider_data', 'ix_ba241b3725039a4f710ea258');
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_b0a7e225b74bb752e49baa02');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4be332a4657da55fbfb03cd');
            $table->index('account_id', 'ix_beedd7363ebc006be4eec495');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__0989e669c58b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_391663359f08bb28069faa38');
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->text('venue_type')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_0e529897c56d1917b096080d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_91237f6151fe9c3d0d985622');
            $table->index('account_id', 'ix_4c53e26431caa60fc5c1e61e');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__b2383747ff31', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('optional')->default(false);
            $table->text('message')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_6a85d9974918435d230dfb5e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6bf9517f970033ff595e9c85');
            $table->index('account_id', 'ix_c0747ccfaace4180a42232af');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a63d595c8be2', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__b2383747ff31', 'id', 'fk_015e7ae5d02260e1f7926282')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4ce0ecfe5aaf3cdd8342');
            $table->index('account_id', 'ix_71154a4c53d3dcff0e49c72e');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1df9ccfd35a3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_9cd6a85980eac75f227887bf');
            $table->bigInteger('rich_message')->nullable();
            $table->index('rich_message', 'ix_3fdeacc8ff7d04bf1997f831');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_68c284a819049286a99b9653');
            $table->index('account_id', 'ix_de5d31a036c3c2f094065fa1');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_text', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message')->nullable();
            $table->bigInteger('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_b0608facd0e011aaf49a6095');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d45364e5f3f49f791e52534d');
            $table->index('account_id', 'ix_4ebbe984f1398dfe0bfd7de7');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline_message_text', 'id', 'fk_fea81f863ea2fa8f8b4805fd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ce8bc63cc5778ba09a65');
            $table->index('account_id', 'ix_9b515a5d274a007a269d891d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline_message_text');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__1df9ccfd35a3');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__a63d595c8be2');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__b2383747ff31');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__0989e669c58b');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__13ed224796c5');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__a1361e727854');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__1cad71cb92f3');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__04c37cd8c27e');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4');
        Schema::dropIfExists('tl_input_bot_inline_message_input_bot_inline_message_game');
    }
};
