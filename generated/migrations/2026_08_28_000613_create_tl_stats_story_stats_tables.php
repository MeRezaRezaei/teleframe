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
        Schema::create('tl_stats_story_stats_story_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('views_graph')->nullable();
            $table->index('views_graph', 'ix_a6a84a1305af2e779a7e7a2d');
            $table->bigInteger('reactions_by_emotion_graph')->nullable();
            $table->index('reactions_by_emotion_graph', 'ix_15ebd23211067ef18fa2a887');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_82015df7c4f45c6b344b4b3f');
            $table->index('account_id', 'ix_45b0a301135a2d0fe395ef28');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_story_stats_story_stats');
    }
};
