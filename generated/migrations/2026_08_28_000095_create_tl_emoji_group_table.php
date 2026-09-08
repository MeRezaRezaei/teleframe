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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_emoji_group_emoji_group', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->timestamps();
        });
        Schema::create('tl_emoji_group_emoji_group__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_group_emoji_group')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_795a43b0057741b8d0fb');
        });
        Schema::create('tl_emoji_group_emoji_group_greeting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->timestamps();
        });
        Schema::create('tl_emoji_group_emoji_group_greeting__emoticons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_emoji_group_emoji_group_greeting')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ff41fc45f3ba8d4c98b1');
        });
        Schema::create('tl_emoji_group_emoji_group_premium', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_emoji_group')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('icon_emoji_id');
            $table->timestamps();
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
