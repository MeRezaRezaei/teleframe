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
            $table->index('constructor_id', 'ix_fd2f9cc5fbc77f97a337f103');
            $table->index('account_id', 'ix_ddb130e74d3bc6d5bd3c6cad');
        });
        Schema::create('tl_input_file_location_input_document_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->text('thumb_size');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d1aeb88bfb5f92ffe1a72eb5');
            $table->unique(['account_id', 'tl_id'], 'ux_97f35c252b116627af3a');
        });
        Schema::create('tl_input_file_location_input_encrypted_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8e5eb12f8b063a7de3f43e0c');
            $table->unique(['account_id', 'tl_id'], 'ux_350fba23e9a6bdf9deab');
        });
        Schema::create('tl_input_file_location_input_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('volume_id');
            $table->index('volume_id', 'ix_818aa28abce94f2254092ed5');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->binary('file_reference');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e178f8c37f8064301995a1a9');
        });
        Schema::create('tl_input_file_location_input_group_call_stream', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('call');
            $table->index('call', 'ix_3c6bacfc2e06e3849d143b5c');
            $table->bigInteger('time_ms');
            $table->integer('scale');
            $table->integer('video_channel')->nullable();
            $table->integer('video_quality')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f6f0f5c30a9e795e7da1bed7');
        });
        Schema::create('tl_input_file_location_input_peer_photo_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('big')->default(false);
            $table->bigInteger('peer');
            $table->index('peer', 'ix_07bc8925dcce54c0967072a9');
            $table->bigInteger('photo_id');
            $table->index('photo_id', 'ix_32f93cf0dc38776f0b5ac345');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dfb512f3f425d3aea5f94240');
        });
        Schema::create('tl_input_file_location_input_photo_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->text('thumb_size');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6de3c74253b0deeca2621545');
            $table->unique(['account_id', 'tl_id'], 'ux_c8c58b1424ff4425dbf1');
        });
        Schema::create('tl_input_file_location_input_photo_legacy_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->bigInteger('volume_id');
            $table->index('volume_id', 'ix_0eabfdf8465b5edb0cce95f0');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f8790a7a486788b7d5f11318');
            $table->unique(['account_id', 'tl_id'], 'ux_ed3a5183333658435331');
        });
        Schema::create('tl_input_file_location_input_secure_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f095d64934539167a19f6b57');
            $table->unique(['account_id', 'tl_id'], 'ux_dcf28cfcde3d6fbe5ba0');
        });
        Schema::create('tl_input_file_location_input_sticker_set_thumb', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->uuid('stickerset');
            $table->index('stickerset', 'ix_ed2d570dc85b348e9762cc9e');
            $table->integer('thumb_version');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5843f25dc1a76bcf2d673d91');
        });
        Schema::create('tl_input_file_location_input_takeout_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_file_location')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4f804340f31638bacab0f014');
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
