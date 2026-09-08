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
        Schema::create('tl_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_photo_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_stickers')->default(false);
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->binary('file_reference');
            $table->integer('date');
            $table->integer('dc_id');
            $table->timestamps();
        });
        Schema::create('tl_photo_photo__sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4d6adb44fa99462b6e26');
        });
        Schema::create('tl_photo_photo__video_sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f59e9f56f1c3ebd38c8b');
        });
        Schema::create('tl_photo_photo_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photo_photo_empty');
        Schema::dropIfExists('tl_photo_photo__video_sizes');
        Schema::dropIfExists('tl_photo_photo__sizes');
        Schema::dropIfExists('tl_photo_photo');
        Schema::dropIfExists('tl_photo');
    }
};
