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
        Schema::create('tl_stats_story_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_00c98b965908bf9145062c04');
            $table->index('account_id', 'ix_dc53aef8add9dd53c1eb174c');
        });
        Schema::create('tl_stats_story_stats_story_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stats_story_stats')->cascadeOnDelete();
            $table->uuid('views_graph');
            $table->index('views_graph', 'ix_a6a84a1305af2e779a7e7a2d');
            $table->uuid('reactions_by_emotion_graph');
            $table->index('reactions_by_emotion_graph', 'ix_15ebd23211067ef18fa2a887');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_45b0a301135a2d0fe395ef28');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_story_stats_story_stats');
        Schema::dropIfExists('tl_stats_story_stats');
    }
};
