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
        Schema::create('tl_stats_broadcast_stats_broadcast_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('period')->nullable();
            $table->index('period', 'ix_1dbb4e3afa1a9932ce6d1603');
            $table->bigInteger('followers')->nullable();
            $table->index('followers', 'ix_8ac3447d6b8a1312794bebfd');
            $table->bigInteger('views_per_post')->nullable();
            $table->index('views_per_post', 'ix_094654a32f38fecf92e707f8');
            $table->bigInteger('shares_per_post')->nullable();
            $table->index('shares_per_post', 'ix_14d09470c81ced76cb4cdf2d');
            $table->bigInteger('reactions_per_post')->nullable();
            $table->index('reactions_per_post', 'ix_8e698ce0e2a55a43c2fe8dff');
            $table->bigInteger('views_per_story')->nullable();
            $table->index('views_per_story', 'ix_f30288dae438b6f790fceaf0');
            $table->bigInteger('shares_per_story')->nullable();
            $table->index('shares_per_story', 'ix_7b7beaa58e7f4b6181a163c2');
            $table->bigInteger('reactions_per_story')->nullable();
            $table->index('reactions_per_story', 'ix_37b26e4642d199aaa96cf57e');
            $table->bigInteger('enabled_notifications')->nullable();
            $table->index('enabled_notifications', 'ix_13f89ab65a6b529fc8a1a583');
            $table->bigInteger('growth_graph')->nullable();
            $table->index('growth_graph', 'ix_283787866bfc01136e3fe741');
            $table->bigInteger('followers_graph')->nullable();
            $table->index('followers_graph', 'ix_abb45a45f0650c814bf5393d');
            $table->bigInteger('mute_graph')->nullable();
            $table->index('mute_graph', 'ix_63f22fe2df38014e23d2e819');
            $table->bigInteger('top_hours_graph')->nullable();
            $table->index('top_hours_graph', 'ix_5eeb03a5d55bfbdb5dad9b3f');
            $table->bigInteger('interactions_graph')->nullable();
            $table->index('interactions_graph', 'ix_952783556d9f70dd8ccfb1d6');
            $table->bigInteger('iv_interactions_graph')->nullable();
            $table->index('iv_interactions_graph', 'ix_97985ca0d9b7a8fd67cd70ab');
            $table->bigInteger('views_by_source_graph')->nullable();
            $table->index('views_by_source_graph', 'ix_01ffd7e661a58221241bb0a2');
            $table->bigInteger('new_followers_by_source_graph')->nullable();
            $table->index('new_followers_by_source_graph', 'ix_37dc5f1c1cacff5f1e5a6c99');
            $table->bigInteger('languages_graph')->nullable();
            $table->index('languages_graph', 'ix_81792c518c0c6e8d14d8e027');
            $table->bigInteger('reactions_by_emotion_graph')->nullable();
            $table->index('reactions_by_emotion_graph', 'ix_37913d08173c0448e9de8a93');
            $table->bigInteger('story_interactions_graph')->nullable();
            $table->index('story_interactions_graph', 'ix_438b3a4df2a0fa9976dcfda2');
            $table->bigInteger('story_reactions_by_emotion_graph')->nullable();
            $table->index('story_reactions_by_emotion_graph', 'ix_f8fd6f8c59513567e5d1547f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_57b10c0764ee295884d7e038');
            $table->index('account_id', 'ix_3d164c1e89f256c17e66e4f4');
        });
        Schema::create('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c3bca797a5e27cadc1a78197')->references('id')->on('tl_stats_broadcast_stats_broadcast_stats')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c08f8e9f812c2ecd0be2');
            $table->index('account_id', 'ix_b0c49cf1104fea2111599f3a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats__rec_a92d3aa2a305');
        Schema::dropIfExists('tl_stats_broadcast_stats_broadcast_stats');
    }
};
