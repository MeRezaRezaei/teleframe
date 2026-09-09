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
            $table->index('constructor_id', 'ix_3eb21cf8980d2327fdcdc264');
            $table->index('account_id', 'ix_b15331749994bf6e30ec1669');
        });
        Schema::create('tl_video_size_video_size', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_type');
            $table->integer('w');
            $table->integer('h');
            $table->integer('tl_size');
            $table->double('video_start_ts')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_289f50cce145bbd4f77dcf5a');
        });
        Schema::create('tl_video_size_video_size_emoji_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->bigInteger('emoji_id');
            $table->index('emoji_id', 'ix_57760bf8fa4b89ccfe53d619');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0ddcce363117fe1b4c7d7860');
        });
        Schema::create('tl_video_size_video_size_emoji_markup__background_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_video_size_video_size_emoji_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_947ef58472249455cf92');
            $table->index('account_id', 'ix_fe290a97daf4ce7385c5fcb5');
        });
        Schema::create('tl_video_size_video_size_sticker_markup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_video_size')->cascadeOnDelete();
            $table->uuid('stickerset');
            $table->index('stickerset', 'ix_afb74ada93bbd7344a676fcd');
            $table->bigInteger('sticker_id');
            $table->index('sticker_id', 'ix_c8eca08d4a65011fe1b4acff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4d23c3810380af0ce51ce347');
        });
        Schema::create('tl_video_size_video_size_sticker_markup__background_colors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_video_size_video_size_sticker_markup')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_18ea102b8bd70043f6cc');
            $table->index('account_id', 'ix_3b0c57b8eef930c191de1e97');
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
