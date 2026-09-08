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
        Schema::create('tl_input_file_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_file_location_input_document_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->text('thumb_size');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_encrypted_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('volume_id');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->binary('file_reference');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_group_call_stream', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('call');
            $table->bigInteger('time_ms');
            $table->integer('scale');
            $table->integer('video_channel')->nullable();
            $table->integer('video_quality')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_peer_photo_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('big')->default(false);
            $table->uuid('peer');
            $table->bigInteger('photo_id');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_photo_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->text('thumb_size');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_photo_legacy_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->bigInteger('volume_id');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_secure_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_sticker_set_thumb', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->uuid('stickerset');
            $table->integer('thumb_version');
            $table->timestamps();
        });
        Schema::create('tl_input_file_location_input_takeout_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_file_location_input_takeout_file_location');
        Schema::dropIfExists('tl_input_file_location_input_sticker_set_thumb');
        Schema::dropIfExists('tl_input_file_location_input_secure_file_location');
        Schema::dropIfExists('tl_input_file_location_input_photo_legacy_file_location');
        Schema::dropIfExists('tl_input_file_location_input_photo_file_location');
        Schema::dropIfExists('tl_input_file_location_input_peer_photo_file_location');
        Schema::dropIfExists('tl_input_file_location_input_group_call_stream');
        Schema::dropIfExists('tl_input_file_location_input_file_location');
        Schema::dropIfExists('tl_input_file_location_input_encrypted_file_location');
        Schema::dropIfExists('tl_input_file_location_input_document_file_location');
        Schema::dropIfExists('tl_input_file_location');
    }
};
