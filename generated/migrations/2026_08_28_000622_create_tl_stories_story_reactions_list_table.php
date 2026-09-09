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
        Schema::create('tl_stories_story_reactions_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_50fc61a6ec8626396454ce51');
            $table->index('account_id', 'ix_5f9966063e4032a7a0296f01');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactions_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0f9fa49771f2647880ed626a');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_d7e48e0a40c9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_75e51b6b0d697f32a68a');
            $table->index('account_id', 'ix_c85a900804b16a89cd0ec257');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_19a95eef9ec1', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dbfb2fa6825168a57325');
            $table->index('account_id', 'ix_96b644adecb08cc8e9522cf7');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_fd3c0d26748d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_84b8c6884303e300c847');
            $table->index('account_id', 'ix_5063d35894b9868479f2c0ab');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_story_reactions_list_story_reactio_fd3c0d26748d');
        Schema::dropIfExists('tl_stories_story_reactions_list_story_reactio_19a95eef9ec1');
        Schema::dropIfExists('tl_stories_story_reactions_list_story_reactio_d7e48e0a40c9');
        Schema::dropIfExists('tl_stories_story_reactions_list_story_reactions_list');
        Schema::dropIfExists('tl_stories_story_reactions_list');
    }
};
