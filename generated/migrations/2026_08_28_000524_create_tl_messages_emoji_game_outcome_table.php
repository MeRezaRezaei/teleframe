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
        Schema::create('tl_messages_emoji_game_outcome', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1c2537faede9f2d79daa5197');
            $table->index('account_id', 'ix_5b756c6f04cb90a98a52b960');
        });
        Schema::create('tl_messages_emoji_game_outcome_emoji_game_outcome', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_emoji_game_outcome')->cascadeOnDelete();
            $table->binary('seed');
            $table->bigInteger('stake_ton_amount');
            $table->bigInteger('ton_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_41755356c47eab753d993bbd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_game_outcome_emoji_game_outcome');
        Schema::dropIfExists('tl_messages_emoji_game_outcome');
    }
};
