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
        Schema::create('tl_stories_story_views_story_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49f6a630c740cf9582c01442');
            $table->index('account_id', 'ix_ef86893be8ad2be78c39b662');
        });
        Schema::create('tl_stories_story_views_story_views__views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8a913e0c725acfa95d0dc0df')->references('id')->on('tl_stories_story_views_story_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cc141a0e0b803597495a');
            $table->index('account_id', 'ix_f69ec0ec7c9af91c5425b81a');
        });
        Schema::create('tl_stories_story_views_story_views__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b6b6e3ed27e590129bb4d2d5')->references('id')->on('tl_stories_story_views_story_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db6008724f30978a59aa');
            $table->index('account_id', 'ix_d83c487f575ca306790c5877');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_story_views_story_views__users');
        Schema::dropIfExists('tl_stories_story_views_story_views__views');
        Schema::dropIfExists('tl_stories_story_views_story_views');
    }
};
