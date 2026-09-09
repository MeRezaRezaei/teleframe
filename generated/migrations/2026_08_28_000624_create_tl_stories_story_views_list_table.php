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
        Schema::create('tl_stories_story_views_list', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_068c2cd198e460f1cd6a3758');
            $table->index('account_id', 'ix_7387d643c79be45c9954f978');
        });
        Schema::create('tl_stories_story_views_list_story_views_list', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stories_story_views_list')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('count');
            $table->integer('views_count');
            $table->integer('forwards_count');
            $table->integer('reactions_count');
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cf16086832bc0a9665bee815');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_views_list_story_views_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_c1fd58992ac4dfcd2df4');
            $table->index('account_id', 'ix_813e394290e0e8f0b3850760');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_views_list_story_views_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_28cb498052bd75a5f53a');
            $table->index('account_id', 'ix_12676667df6b1eba00ac7b8c');
        });
        Schema::create('tl_stories_story_views_list_story_views_list__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_stories_story_views_list_story_views_list')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_stories_story_views_list');
    }
};
