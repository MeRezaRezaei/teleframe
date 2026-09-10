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
        Schema::create('tl_input_game_input_game_i_d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2bdbbdb92b7acf46f9b58aa4');
            $table->index('account_id', 'ix_dc09c4bf15ced8a1b22fba8a');
        });
        Schema::create('tl_input_game_input_game_short_name', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_73ce47b62bb129ffe8816a44');
            $table->text('short_name')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_de725f75f78ff40b810fbc4d');
            $table->index('account_id', 'ix_8f1c12cd745dce2c063a114b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_game_input_game_short_name');
        Schema::dropIfExists('tl_input_game_input_game_i_d');
    }
};
