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
        Schema::create('tl_stats_broadcast_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stats_broadcast_stats_broadcast_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_broadcast_stats')->cascadeOnDelete();
            $table->uuid('period');
            $table->uuid('followers');
            $table->uuid('views_per_post');
            $table->uuid('shares_per_post');
            $table->uuid('reactions_per_post');
            $table->uuid('views_per_story');
            $table->uuid('shares_per_story');
            $table->uuid('reactions_per_story');
            $table->uuid('enabled_notifications');
            $table->uuid('growth_graph');
            $table->uuid('followers_graph');
            $table->uuid('mute_graph');
            $table->uuid('top_hours_graph');
            $table->uuid('interactions_graph');
            $table->uuid('iv_interactions_graph');
            $table->uuid('views_by_source_graph');
            $table->uuid('new_followers_by_source_graph');
            $table->uuid('languages_graph');
            $table->uuid('reactions_by_emotion_graph');
            $table->uuid('story_interactions_graph');
            $table->uuid('story_reactions_by_emotion_graph');
            $table->timestamps();
        });
        Schema::create('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_broadcast_stats_broadcast_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c08f8e9f812c2ecd0be2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305');
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats');
        Schema::dropIfExists('tl_stats_broadcast_stats');
    }
};
