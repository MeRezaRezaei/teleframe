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
        Schema::create('tl_video_size_video_size', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_type')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('tl_size')->nullable();
            $table->double('video_start_ts')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3526a53cc806f5cc31db9dc1');
            $table->index('account_id', 'ix_289f50cce145bbd4f77dcf5a');
        });
        Schema::create('tl_video_size_video_size_emoji_markup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('emoji_id')->nullable();
            $table->index('emoji_id', 'ix_57760bf8fa4b89ccfe53d619');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0baaf4e9394dcafb064bdde5');
            $table->index('account_id', 'ix_0ddcce363117fe1b4c7d7860');
        });
        Schema::create('tl_video_size_video_size_emoji_markup__background_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_video_size_video_size_emoji_markup', 'id', 'fk_4c5de7211135752b01facc40')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_947ef58472249455cf92');
            $table->index('account_id', 'ix_fe290a97daf4ce7385c5fcb5');
        });
        Schema::create('tl_video_size_video_size_sticker_markup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('stickerset')->nullable();
            $table->index('stickerset', 'ix_afb74ada93bbd7344a676fcd');
            $table->bigInteger('sticker_id')->nullable();
            $table->index('sticker_id', 'ix_c8eca08d4a65011fe1b4acff');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3bb04cc0014c77a75ce6ec55');
            $table->index('account_id', 'ix_4d23c3810380af0ce51ce347');
        });
        Schema::create('tl_video_size_video_size_sticker_markup__background_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_video_size_video_size_sticker_markup', 'id', 'fk_fdd2a5aebdd457502ba93782')->cascadeOnDelete();
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
    }
};
