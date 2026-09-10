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
        Schema::create('tl_emoji_group_emoji_group', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_cace0f54beb01b5bece860ee');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7ee00e2c5c3235f588c56fa5');
            $table->index('account_id', 'ix_69b1e369fbf314f7e3d988bf');
        });
        Schema::create('tl_emoji_group_emoji_group__emoticons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_5403c54fe0c848ab7afdcc65')->references('id')->on('tl_emoji_group_emoji_group')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_795a43b0057741b8d0fb');
            $table->index('account_id', 'ix_6f0ad17a4306ec6cb8b66282');
        });
        Schema::create('tl_emoji_group_emoji_group_greeting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_1affdb46e6cee7153a988d83');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5e3955c6df59b87a4f938453');
            $table->index('account_id', 'ix_df5dc3823c45cfa6cf3d7cdc');
        });
        Schema::create('tl_emoji_group_emoji_group_greeting__emoticons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f9f5d47877f4788523758345')->references('id')->on('tl_emoji_group_emoji_group_greeting')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ff41fc45f3ba8d4c98b1');
            $table->index('account_id', 'ix_7226a4da47cfb8484f2ad0db');
        });
        Schema::create('tl_emoji_group_emoji_group_premium', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->bigInteger('icon_emoji_id')->nullable();
            $table->index('icon_emoji_id', 'ix_9e715db5e2ee3bc54fec4374');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_59b98a69bffa56818daedb9b');
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
    }
};
