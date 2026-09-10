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
        Schema::create('tl_stories_story_views_list_story_views_list', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('count')->nullable();
            $table->integer('views_count')->nullable();
            $table->integer('forwards_count')->nullable();
            $table->integer('reactions_count')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fa014ccba3c9e6a5f061ab82');
            $table->index('account_id', 'ix_cf16086832bc0a9665bee815');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_story_views_list_story_views_list', 'id', 'fk_89922aa95c949bb96fa434b1')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c1fd58992ac4dfcd2df4');
            $table->index('account_id', 'ix_813e394290e0e8f0b3850760');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_story_views_list_story_views_list', 'id', 'fk_dab9a60320ff31e0f49b4af2')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_28cb498052bd75a5f53a');
            $table->index('account_id', 'ix_12676667df6b1eba00ac7b8c');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_stories_story_views_list_story_views_list', 'id', 'fk_fbb3ae670f113edb1df55440')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4ac4b7569b0528a5e03d');
            $table->index('account_id', 'ix_aa870c08e858a6cd1057a2eb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stories_story_views_list_story_views_list__users');
        Schema::dropIfExists('tl_stories_story_views_list_story_views_list__chats');
        Schema::dropIfExists('tl_stories_story_views_list_story_views_list__views');
        Schema::dropIfExists('tl_stories_story_views_list_story_views_list');
    }
};
