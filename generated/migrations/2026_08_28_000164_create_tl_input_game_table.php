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
        Schema::create('tl_input_game', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_6669bb6b62f3215c057528a9');
            $table->index('account_id', 'ix_323a433d745ba6792b84d5ff');
        });
        Schema::create('tl_input_game_input_game_i_d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_game')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dc09c4bf15ced8a1b22fba8a');
            $table->unique(['account_id', 'tl_id'], 'ux_f2c1d7535d0e097a3c65');
        });
        Schema::create('tl_input_game_input_game_short_name', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_game')->cascadeOnDelete();
            $table->uuid('bot_id');
            $table->index('bot_id', 'ix_73ce47b62bb129ffe8816a44');
            $table->text('short_name');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8f1c12cd745dce2c063a114b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_game_input_game_short_name');
        Schema::dropIfExists('tl_input_game_input_game_i_d');
        Schema::dropIfExists('tl_input_game');
    }
};
