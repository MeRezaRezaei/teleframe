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
        Schema::create('tl_story_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_story_views_story_views', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_views')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_viewers')->default(false);
            $table->integer('views_count');
            $table->integer('forwards_count')->nullable();
            $table->integer('reactions_count')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_story_views_story_views__reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_views_story_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e5ad86dc6953012aafef');
        });
        Schema::create('tl_story_views_story_views__recent_viewers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_views_story_views')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_52ab43a7046d0ad7d7a5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_views_story_views__recent_viewers');
        Schema::dropIfExists('tl_story_views_story_views__reactions');
        Schema::dropIfExists('tl_story_views_story_views');
        Schema::dropIfExists('tl_story_views');
    }
};
