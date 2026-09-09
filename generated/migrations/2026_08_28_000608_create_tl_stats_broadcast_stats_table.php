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
            $table->index('constructor_id', 'ix_c5ea6585749d0013289b57c4');
            $table->index('account_id', 'ix_0cf7e4c8a91d8db24dfaf289');
        });
        Schema::create('tl_stats_broadcast_stats_broadcast_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_broadcast_stats')->cascadeOnDelete();
            $table->uuid('period');
            $table->index('period', 'ix_1dbb4e3afa1a9932ce6d1603');
            $table->uuid('followers');
            $table->index('followers', 'ix_8ac3447d6b8a1312794bebfd');
            $table->uuid('views_per_post');
            $table->index('views_per_post', 'ix_094654a32f38fecf92e707f8');
            $table->uuid('shares_per_post');
            $table->index('shares_per_post', 'ix_14d09470c81ced76cb4cdf2d');
            $table->uuid('reactions_per_post');
            $table->index('reactions_per_post', 'ix_8e698ce0e2a55a43c2fe8dff');
            $table->uuid('views_per_story');
            $table->index('views_per_story', 'ix_f30288dae438b6f790fceaf0');
            $table->uuid('shares_per_story');
            $table->index('shares_per_story', 'ix_7b7beaa58e7f4b6181a163c2');
            $table->uuid('reactions_per_story');
            $table->index('reactions_per_story', 'ix_37b26e4642d199aaa96cf57e');
            $table->uuid('enabled_notifications');
            $table->index('enabled_notifications', 'ix_13f89ab65a6b529fc8a1a583');
            $table->uuid('growth_graph');
            $table->index('growth_graph', 'ix_283787866bfc01136e3fe741');
            $table->uuid('followers_graph');
            $table->index('followers_graph', 'ix_abb45a45f0650c814bf5393d');
            $table->uuid('mute_graph');
            $table->index('mute_graph', 'ix_63f22fe2df38014e23d2e819');
            $table->uuid('top_hours_graph');
            $table->index('top_hours_graph', 'ix_5eeb03a5d55bfbdb5dad9b3f');
            $table->uuid('interactions_graph');
            $table->index('interactions_graph', 'ix_952783556d9f70dd8ccfb1d6');
            $table->uuid('iv_interactions_graph');
            $table->index('iv_interactions_graph', 'ix_97985ca0d9b7a8fd67cd70ab');
            $table->uuid('views_by_source_graph');
            $table->index('views_by_source_graph', 'ix_01ffd7e661a58221241bb0a2');
            $table->uuid('new_followers_by_source_graph');
            $table->index('new_followers_by_source_graph', 'ix_37dc5f1c1cacff5f1e5a6c99');
            $table->uuid('languages_graph');
            $table->index('languages_graph', 'ix_81792c518c0c6e8d14d8e027');
            $table->uuid('reactions_by_emotion_graph');
            $table->index('reactions_by_emotion_graph', 'ix_37913d08173c0448e9de8a93');
            $table->uuid('story_interactions_graph');
            $table->index('story_interactions_graph', 'ix_438b3a4df2a0fa9976dcfda2');
            $table->uuid('story_reactions_by_emotion_graph');
            $table->index('story_reactions_by_emotion_graph', 'ix_f8fd6f8c59513567e5d1547f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3d164c1e89f256c17e66e4f4');
        });
        Schema::create('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stats_broadcast_stats_broadcast_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c08f8e9f812c2ecd0be2');
            $table->index('account_id', 'ix_b0c49cf1104fea2111599f3a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305');
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats');
        Schema::dropIfExists('tl_stats_broadcast_stats');
    }
};
