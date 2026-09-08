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
        Schema::create('tl_messages_emoji_game_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_emoji_game_info_emoji_game_dice_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_emoji_game_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('game_hash');
            $table->bigInteger('prev_stake');
            $table->integer('current_streak');
            $table->integer('plays_left')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_emoji_game_info_emoji_game_dice_info__params', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_emoji_game_info_emoji_game_dice_info')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d8ed97c6cf52f620a941');
        });
        Schema::create('tl_messages_emoji_game_info_emoji_game_unavailable', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_emoji_game_info')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_unavailable');
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_dice_info__params');
        Schema::dropIfExists('tl_messages_emoji_game_info_emoji_game_dice_info');
        Schema::dropIfExists('tl_messages_emoji_game_info');
    }
};
