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
        Schema::create('tl_photo_size', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_photo_size_photo_cached_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->binary('bytes');
            $table->timestamps();
        });
        Schema::create('tl_photo_size_photo_path_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->binary('bytes');
            $table->timestamps();
        });
        Schema::create('tl_photo_size_photo_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->timestamps();
        });
        Schema::create('tl_photo_size_photo_size_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->timestamps();
        });
        Schema::create('tl_photo_size_photo_size_progressive', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->timestamps();
        });
        Schema::create('tl_photo_size_photo_size_progressive__sizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photo_size_photo_size_progressive')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b604eca0ff5967528041');
        });
        Schema::create('tl_photo_size_photo_stripped_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photo_size')->cascadeOnDelete();
            $table->text('tl_type');
            $table->binary('bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photo_size_photo_stripped_size');
        Schema::dropIfExists('tl_photo_size_photo_size_progressive__sizes');
        Schema::dropIfExists('tl_photo_size_photo_size_progressive');
        Schema::dropIfExists('tl_photo_size_photo_size_empty');
        Schema::dropIfExists('tl_photo_size_photo_size');
        Schema::dropIfExists('tl_photo_size_photo_path_size');
        Schema::dropIfExists('tl_photo_size_photo_cached_size');
        Schema::dropIfExists('tl_photo_size');
    }
};
