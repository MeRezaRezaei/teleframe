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
            $table->index('constructor_id', 'ix_5cf21cec6835b61200c95b8f');
            $table->index('account_id', 'ix_d1d882b1f600792f5378d9e2');
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
            $table->bigInteger('from_id')->nullable();
            $table->index('from_id', 'ix_dfae57f9ec17458a9b45c939');
            $table->uuid('fwd_from')->nullable();
            $table->index('fwd_from', 'ix_28e52461448a54ae884847ea');
            $table->integer('expire_date');
            $table->text('caption')->nullable();
            $table->uuid('media');
            $table->index('media', 'ix_7b8f83522b2cc85a1a7e3775');
            $table->uuid('views')->nullable();
            $table->index('views', 'ix_1d7b6c4e4ba6050c9e59821a');
            $table->uuid('sent_reaction')->nullable();
            $table->index('sent_reaction', 'ix_175748648a5492b419b07d93');
            $table->uuid('music')->nullable();
            $table->index('music', 'ix_75ffa6fd69d95e5e899281d1');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_00c80a488b7a97893fbe0f99');
            $table->unique(['account_id', 'from_id', 'tl_id'], 'ux_9eccf57c06a390c77d77');
        });
        Schema::create('tl_story_item_story_item__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_766cfe3c4dc1ec4d0cf9');
            $table->index('account_id', 'ix_10d175049648ba159615b97d');
        });
        Schema::create('tl_story_item_story_item__media_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1543dffcde867a1f7a68');
            $table->index('account_id', 'ix_2161b88cd1e8b0ab5dfaaf54');
        });
        Schema::create('tl_story_item_story_item__privacy', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_74e9ba61d4f683033fd4');
            $table->index('account_id', 'ix_12f1f74a48a3892f45e2ac9f');
        });
        Schema::create('tl_story_item_story_item__albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_story_item_story_item')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e6c5ecf2c8b2dbb73420');
            $table->index('account_id', 'ix_e07c0c17becb7dc8541255f0');
        });
        Schema::create('tl_story_item_story_item_deleted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_item')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_629ce46149fd38b6ea343a38');
            $table->unique(['account_id', 'tl_id'], 'ux_1ebabb1dd0eb2b2a86b6');
        });
        Schema::create('tl_story_item_story_item_skipped', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_story_item')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('close_friends')->default(false);
            $table->boolean('live')->default(false);
            $table->integer('tl_id');
            $table->integer('date');
            $table->integer('expire_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7730f3e180f4e1b895da4b9b');
            $table->unique(['account_id', 'tl_id'], 'ux_2139e2790726418c669c');
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
