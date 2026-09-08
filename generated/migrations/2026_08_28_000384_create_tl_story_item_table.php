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
        Schema::create('tl_story_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_story_item_story_item', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->boolean('public')->default(false);
            $table->boolean('close_friends')->default(false);
            $table->boolean('min')->default(false);
            $table->boolean('noforwards')->default(false);
            $table->boolean('edited')->default(false);
            $table->boolean('contacts')->default(false);
            $table->boolean('selected_contacts')->default(false);
            $table->boolean('out')->default(false);
            $table->integer('tl_id');
            $table->integer('date');
            $table->uuid('from_id')->nullable();
            $table->uuid('fwd_from')->nullable();
            $table->integer('expire_date');
            $table->text('caption')->nullable();
            $table->uuid('media');
            $table->uuid('views')->nullable();
            $table->uuid('sent_reaction')->nullable();
            $table->uuid('music')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_story_item_story_item__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_766cfe3c4dc1ec4d0cf9');
        });
        Schema::create('tl_story_item_story_item__media_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_1543dffcde867a1f7a68');
        });
        Schema::create('tl_story_item_story_item__privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_74e9ba61d4f683033fd4');
        });
        Schema::create('tl_story_item_story_item__albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e6c5ecf2c8b2dbb73420');
        });
        Schema::create('tl_story_item_story_item_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_item')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_story_item_story_item_skipped', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('close_friends')->default(false);
            $table->boolean('live')->default(false);
            $table->integer('tl_id');
            $table->integer('date');
            $table->integer('expire_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_story_item_story_item_skipped');
        Schema::dropIfExists('tl_story_item_story_item_deleted');
        Schema::dropIfExists('tl_story_item_story_item__albums');
        Schema::dropIfExists('tl_story_item_story_item__privacy');
        Schema::dropIfExists('tl_story_item_story_item__media_areas');
        Schema::dropIfExists('tl_story_item_story_item__entities');
        Schema::dropIfExists('tl_story_item_story_item');
        Schema::dropIfExists('tl_story_item');
    }
};
