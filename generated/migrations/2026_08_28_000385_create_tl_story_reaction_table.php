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
        Schema::create('tl_story_reaction', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_story_reaction_story_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->uuid('peer_id');
            $table->integer('date');
            $table->uuid('reaction');
            $table->timestamps();
        });
        Schema::create('tl_story_reaction_story_reaction_public_forward', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->uuid('message');
            $table->timestamps();
        });
        Schema::create('tl_story_reaction_story_reaction_public_repost', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_reaction')->cascadeOnDelete();
            $table->uuid('peer_id');
            $table->uuid('story');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_repost');
        Schema::dropIfExists('tl_story_reaction_story_reaction_public_forward');
        Schema::dropIfExists('tl_story_reaction_story_reaction');
        Schema::dropIfExists('tl_story_reaction');
    }
};
