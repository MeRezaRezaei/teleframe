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
        Schema::create('tl_messages_emoji_game_outcome_emoji_game_outcome', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('seed');
            $table->bigInteger('stake_ton_amount');
            $table->bigInteger('ton_amount');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c80f6bb0c39aeaf3ae3d957a');
            $table->index('account_id', 'ix_41755356c47eab753d993bbd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_emoji_game_outcome_emoji_game_outcome');
    }
};
