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
        Schema::create('tl_emoji_group', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5d2b349721b905b2edf23a5b');
            $table->index('account_id', 'ix_a5a29f5ddde596e2c7880d36');
        });
        Schema::create('tl_emoji_group_emoji_group', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->index('icon_emoji_id', 'ix_cace0f54beb01b5bece860ee');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_69b1e369fbf314f7e3d988bf');
        });
        Schema::create('tl_emoji_group_emoji_group__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_group_emoji_group')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_795a43b0057741b8d0fb');
            $table->index('account_id', 'ix_6f0ad17a4306ec6cb8b66282');
        });
        Schema::create('tl_emoji_group_emoji_group_greeting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->index('icon_emoji_id', 'ix_1affdb46e6cee7153a988d83');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df5dc3823c45cfa6cf3d7cdc');
        });
        Schema::create('tl_emoji_group_emoji_group_greeting__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_group_emoji_group_greeting')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ff41fc45f3ba8d4c98b1');
            $table->index('account_id', 'ix_7226a4da47cfb8484f2ad0db');
        });
        Schema::create('tl_emoji_group_emoji_group_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->index('icon_emoji_id', 'ix_9e715db5e2ee3bc54fec4374');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0886cb9c33b1a1f2ce2752db');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_emoji_group_emoji_group_premium');
        Schema::dropIfExists('tl_emoji_group_emoji_group_greeting__emoticons');
        Schema::dropIfExists('tl_emoji_group_emoji_group_greeting');
        Schema::dropIfExists('tl_emoji_group_emoji_group__emoticons');
        Schema::dropIfExists('tl_emoji_group_emoji_group');
        Schema::dropIfExists('tl_emoji_group');
    }
};
