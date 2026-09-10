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
        Schema::create('tl_input_collectible_input_collectible_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5c4fbd541a37d19074971bce');
            $table->index('account_id', 'ix_5569697f8f4954d96f4e163e');
        });
        Schema::create('tl_input_collectible_input_collectible_username', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('username')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ec50010ada7209d3cd018b2');
            $table->index('account_id', 'ix_d8aa142a1441a11a0ff427ef');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_collectible_input_collectible_username');
        Schema::dropIfExists('tl_input_collectible_input_collectible_phone');
    }
};
