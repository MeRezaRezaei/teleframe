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
        Schema::create('tl_message_entity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_message_entity_input_message_entity_mention_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->uuid('user_id');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_bank_card', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_blockquote', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('collapsed')->default(false);
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_bold', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_bot_command', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_cashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->bigInteger('document_id');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_diff_delete', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_diff_insert', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_diff_replace', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->text('old_text');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_formatted_date', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('relative')->default(false);
            $table->boolean('short_time')->default(false);
            $table->boolean('long_time')->default(false);
            $table->boolean('short_date')->default(false);
            $table->boolean('long_date')->default(false);
            $table->boolean('day_of_week')->default(false);
            $table->integer('tl_offset');
            $table->integer('length');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_hashtag', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_italic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_mention', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_mention_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_pre', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->text('language');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_spoiler', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_strike', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_text_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_underline', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_unknown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_message_entity_message_entity_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_entity')->cascadeOnDelete();
            $table->integer('tl_offset');
            $table->integer('length');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_entity_message_entity_url');
        Schema::dropIfExists('tl_message_entity_message_entity_unknown');
        Schema::dropIfExists('tl_message_entity_message_entity_underline');
        Schema::dropIfExists('tl_message_entity_message_entity_text_url');
        Schema::dropIfExists('tl_message_entity_message_entity_strike');
        Schema::dropIfExists('tl_message_entity_message_entity_spoiler');
        Schema::dropIfExists('tl_message_entity_message_entity_pre');
        Schema::dropIfExists('tl_message_entity_message_entity_phone');
        Schema::dropIfExists('tl_message_entity_message_entity_mention_name');
        Schema::dropIfExists('tl_message_entity_message_entity_mention');
        Schema::dropIfExists('tl_message_entity_message_entity_italic');
        Schema::dropIfExists('tl_message_entity_message_entity_hashtag');
        Schema::dropIfExists('tl_message_entity_message_entity_formatted_date');
        Schema::dropIfExists('tl_message_entity_message_entity_email');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_replace');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_insert');
        Schema::dropIfExists('tl_message_entity_message_entity_diff_delete');
        Schema::dropIfExists('tl_message_entity_message_entity_custom_emoji');
        Schema::dropIfExists('tl_message_entity_message_entity_code');
        Schema::dropIfExists('tl_message_entity_message_entity_cashtag');
        Schema::dropIfExists('tl_message_entity_message_entity_bot_command');
        Schema::dropIfExists('tl_message_entity_message_entity_bold');
        Schema::dropIfExists('tl_message_entity_message_entity_blockquote');
        Schema::dropIfExists('tl_message_entity_message_entity_bank_card');
        Schema::dropIfExists('tl_message_entity_input_message_entity_mention_name');
        Schema::dropIfExists('tl_message_entity');
    }
};
