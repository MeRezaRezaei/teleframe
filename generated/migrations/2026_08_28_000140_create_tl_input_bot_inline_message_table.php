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
        Schema::create('tl_input_bot_inline_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9db6f7dc90624fa3621ce522');
            $table->index('account_id', 'ix_a75b5d63668081e136e3919b');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_0ccb57647cf4c1b239458420');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b11dccc7a2c87854b468a724');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_7c23a0c8354aac05e9ba06e5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7a29a07d34ad2e72c05d44ed');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__04c37cd8c27e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1b042b3dbc98b5766579');
            $table->index('account_id', 'ix_f124c927fe5fb0fd76d46d7c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1cad71cb92f3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_257ec508857bba0d09531b29');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_07943325ca781f5f7b3a773c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a1361e727854', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_da9ebb93297126ce0d7cd40e');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_297849aec9a72edc4a1affd3');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3c43cb12eb3fa4effb828e9c');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__13ed224796c5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_225b5caa2aea909d791afff1');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_075a2245e2bb7666325a325f');
            $table->binary('payload');
            $table->text('provider');
            $table->uuid('provider_data');
            $table->index('provider_data', 'ix_ba241b3725039a4f710ea258');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_b0a7e225b74bb752e49baa02');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_beedd7363ebc006be4eec495');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__0989e669c58b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_391663359f08bb28069faa38');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_0e529897c56d1917b096080d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4c53e26431caa60fc5c1e61e');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__b2383747ff31', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->boolean('force_large_media')->default(false);
            $table->boolean('force_small_media')->default(false);
            $table->boolean('optional')->default(false);
            $table->text('message');
            $table->text('url');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_6a85d9974918435d230dfb5e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c0747ccfaace4180a42232af');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a63d595c8be2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__b2383747ff31')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4ce0ecfe5aaf3cdd8342');
            $table->index('account_id', 'ix_71154a4c53d3dcff0e49c72e');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1df9ccfd35a3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_9cd6a85980eac75f227887bf');
            $table->uuid('rich_message');
            $table->index('rich_message', 'ix_3fdeacc8ff7d04bf1997f831');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de5d31a036c3c2f094065fa1');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->index('reply_markup', 'ix_b0608facd0e011aaf49a6095');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4ebbe984f1398dfe0bfd7de7');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline_message_text')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_input_bot_inline_message');
    }
};
