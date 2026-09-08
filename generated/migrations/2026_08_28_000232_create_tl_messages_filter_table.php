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
        Schema::create('tl_messages_filter', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_filter_input_messages_filter_chat_photos', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_contacts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_document', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_geo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_gif', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_music', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_my_mentions', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_phone_calls', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('missed')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_photo_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_photos', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_pinned', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_poll', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_round_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_round_voice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_filter_input_messages_filter_voice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_filter')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_voice');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_url');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_round_voice');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_round_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_poll');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_pinned');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_photos');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_photo_video');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_phone_calls');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_my_mentions');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_music');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_gif');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_geo');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_empty');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_document');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_contacts');
        Schema::dropIfExists('tl_messages_filter_input_messages_filter_chat_photos');
        Schema::dropIfExists('tl_messages_filter');
    }
};
