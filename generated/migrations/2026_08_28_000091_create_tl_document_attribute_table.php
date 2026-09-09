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
            $table->index('constructor_id', 'ix_7e5ac3a2e0efd535b9cf1f0c');
            $table->index('account_id', 'ix_6bc01eb6cbe82348eee99432');
        });
        Schema::create('tl_document_attribute_document_attribute_animated', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1c8f0a660b638a2ef62c864f');
        });
        Schema::create('tl_document_attribute_document_attribute_audio', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('voice')->default(false);
            $table->integer('duration');
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->binary('waveform')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0e8e0b59688602d0603ffc17');
        });
        Schema::create('tl_document_attribute_document_attribute_custom_emoji', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('free')->default(false);
            $table->boolean('text_color')->default(false);
            $table->text('alt');
            $table->uuid('stickerset');
            $table->index('stickerset', 'ix_a9149f0a620255b856f3c3db');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ee23e81892b05592c5efc8c0');
        });
        Schema::create('tl_document_attribute_document_attribute_filename', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->text('file_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_717c03fe9473172e6b73e2db');
        });
        Schema::create('tl_document_attribute_document_attribute_has_stickers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_42a4b2450a55dbb7028ea16c');
        });
        Schema::create('tl_document_attribute_document_attribute_image_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->integer('w');
            $table->integer('h');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1529ce3cb6c3add182acb59e');
        });
        Schema::create('tl_document_attribute_document_attribute_sticker', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_document_attribute')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('mask')->default(false);
            $table->text('alt');
            $table->uuid('stickerset');
            $table->index('stickerset', 'ix_f9fb332bf0026f726e8ecd7a');
            $table->uuid('mask_coords')->nullable();
            $table->index('mask_coords', 'ix_e2e91f51b3c4c1f8670be618');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_23df06661c3913370506d66c');
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
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1601c1dbe24e7985935231b1');
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
