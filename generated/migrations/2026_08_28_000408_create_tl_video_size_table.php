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
        Schema::create('tl_video_size', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_video_size_video_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->double('video_start_ts')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_video_size_video_size_emoji_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->bigInteger('emoji_id');
            $table->timestamps();
        });
        Schema::create('tl_video_size_video_size_emoji_markup__background_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_video_size_video_size_emoji_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_947ef58472249455cf92');
        });
        Schema::create('tl_video_size_video_size_sticker_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->uuid('stickerset');
            $table->bigInteger('sticker_id');
            $table->timestamps();
        });
        Schema::create('tl_video_size_video_size_sticker_markup__background_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_video_size_video_size_sticker_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_18ea102b8bd70043f6cc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_video_size_video_size_sticker_markup__background_colors');
        Schema::dropIfExists('tl_video_size_video_size_sticker_markup');
        Schema::dropIfExists('tl_video_size_video_size_emoji_markup__background_colors');
        Schema::dropIfExists('tl_video_size_video_size_emoji_markup');
        Schema::dropIfExists('tl_video_size_video_size');
        Schema::dropIfExists('tl_video_size');
    }
};
