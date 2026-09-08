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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_game', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__04c37cd8c27e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__93d9b4b179e4')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1b042b3dbc98b5766579');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1cad71cb92f3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('phone_number');
            $table->text('first_name');
            $table->text('last_name');
            $table->text('vcard');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a1361e727854', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo_point');
            $table->integer('heading')->nullable();
            $table->integer('period')->nullable();
            $table->integer('proximity_notification_radius')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__13ed224796c5', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->binary('payload');
            $table->text('provider');
            $table->uuid('provider_data');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__0989e669c58b', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('geo_point');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
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
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__a63d595c8be2', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline__b2383747ff31')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4ce0ecfe5aaf3cdd8342');
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__1df9ccfd35a3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('reply_markup')->nullable();
            $table->uuid('rich_message');
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline_message_text', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_bot_inline_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('no_webpage')->default(false);
            $table->boolean('invert_media')->default(false);
            $table->text('message');
            $table->uuid('reply_markup')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_bot_inline_message_input_bot_inline__24c36bb1c4b3', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_bot_inline_message_input_bot_inline_message_text')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ce8bc63cc5778ba09a65');
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
