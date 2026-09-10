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
        Schema::create('tl_stories_story_reactions_list_story_reactions_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_17eaddf24e6ef985620a1218');
            $table->index('account_id', 'ix_0f9fa49771f2647880ed626a');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_d7e48e0a40c9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c9ece651e3d0cf3b7d017806')->references('id')->on('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_75e51b6b0d697f32a68a');
            $table->index('account_id', 'ix_c85a900804b16a89cd0ec257');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_19a95eef9ec1', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f8ba956b10a05a025fc0bbe8')->references('id')->on('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dbfb2fa6825168a57325');
            $table->index('account_id', 'ix_96b644adecb08cc8e9522cf7');
        });
        Schema::create('tl_stories_story_reactions_list_story_reactio_fd3c0d26748d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e0da18135709cb8fe21c5edf')->references('id')->on('tl_stories_story_reactions_list_story_reactions_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
