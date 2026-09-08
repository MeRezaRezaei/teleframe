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
        Schema::create('tl_document_attribute', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_document_attribute_document_attribute_animated', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('voice')->default(false);
            $table->integer('duration');
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->binary('waveform')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('free')->default(false);
            $table->boolean('text_color')->default(false);
            $table->text('alt');
            $table->uuid('stickerset');
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_filename', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->text('file_name');
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_has_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_image_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->integer('w');
            $table->integer('h');
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_sticker', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('mask')->default(false);
            $table->text('alt');
            $table->uuid('stickerset');
            $table->uuid('mask_coords')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_document_attribute_document_attribute_video', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('round_message')->default(false);
            $table->boolean('supports_streaming')->default(false);
            $table->boolean('nosound')->default(false);
            $table->double('duration');
            $table->integer('w');
            $table->integer('h');
            $table->integer('preload_prefix_size')->nullable();
            $table->double('video_start_ts')->nullable();
            $table->text('video_codec')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_document_attribute_document_attribute_video');
        Schema::dropIfExists('tl_document_attribute_document_attribute_sticker');
        Schema::dropIfExists('tl_document_attribute_document_attribute_image_size');
        Schema::dropIfExists('tl_document_attribute_document_attribute_has_stickers');
        Schema::dropIfExists('tl_document_attribute_document_attribute_filename');
        Schema::dropIfExists('tl_document_attribute_document_attribute_custom_emoji');
        Schema::dropIfExists('tl_document_attribute_document_attribute_audio');
        Schema::dropIfExists('tl_document_attribute_document_attribute_animated');
        Schema::dropIfExists('tl_document_attribute');
    }
};
