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
        Schema::create('tl_rich_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_rich_text_text_anchor', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->text('name');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_auto_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_auto_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_auto_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_bank_card', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_bold', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_bot_command', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_cashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_concat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_concat__texts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_rich_text_text_concat')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_aafca14d0b43526f10ca');
        });
        Schema::create('tl_rich_text_text_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->text('alt');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->uuid('text');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->text('email');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_fixed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_hashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_image', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->bigInteger('document_id');
            $table->integer('w');
            $table->integer('h');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_italic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_marked', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_math', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->text('source');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_mention', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_mention_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->text('phone');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_plain', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->text('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_spoiler', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_strike', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_subscript', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_superscript', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_underline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->timestamps();
        });
        Schema::create('tl_rich_text_text_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_rich_text')->cascadeOnDelete();
            $table->uuid('text');
            $table->text('url');
            $table->bigInteger('webpage_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_rich_text_text_url');
        Schema::dropIfExists('tl_rich_text_text_underline');
        Schema::dropIfExists('tl_rich_text_text_superscript');
        Schema::dropIfExists('tl_rich_text_text_subscript');
        Schema::dropIfExists('tl_rich_text_text_strike');
        Schema::dropIfExists('tl_rich_text_text_spoiler');
        Schema::dropIfExists('tl_rich_text_text_plain');
        Schema::dropIfExists('tl_rich_text_text_phone');
        Schema::dropIfExists('tl_rich_text_text_mention_name');
        Schema::dropIfExists('tl_rich_text_text_mention');
        Schema::dropIfExists('tl_rich_text_text_math');
        Schema::dropIfExists('tl_rich_text_text_marked');
        Schema::dropIfExists('tl_rich_text_text_italic');
        Schema::dropIfExists('tl_rich_text_text_image');
        Schema::dropIfExists('tl_rich_text_text_hashtag');
        Schema::dropIfExists('tl_rich_text_text_fixed');
        Schema::dropIfExists('tl_rich_text_text_empty');
        Schema::dropIfExists('tl_rich_text_text_email');
        Schema::dropIfExists('tl_rich_text_text_date');
        Schema::dropIfExists('tl_rich_text_text_custom_emoji');
        Schema::dropIfExists('tl_rich_text_text_concat__texts');
        Schema::dropIfExists('tl_rich_text_text_concat');
        Schema::dropIfExists('tl_rich_text_text_cashtag');
        Schema::dropIfExists('tl_rich_text_text_bot_command');
        Schema::dropIfExists('tl_rich_text_text_bold');
        Schema::dropIfExists('tl_rich_text_text_bank_card');
        Schema::dropIfExists('tl_rich_text_text_auto_url');
        Schema::dropIfExists('tl_rich_text_text_auto_phone');
        Schema::dropIfExists('tl_rich_text_text_auto_email');
        Schema::dropIfExists('tl_rich_text_text_anchor');
        Schema::dropIfExists('tl_rich_text');
    }
};
