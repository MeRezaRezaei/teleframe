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
        Schema::create('tl_stars_giveaway_winners_option_stars_giveaw_0f7ecce3eb88', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_default')->default(false);
            $table->integer('users')->nullable();
            $table->bigInteger('per_user_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_29c5c4aa1cd04dc4e5ce57b6');
            $table->index('account_id', 'ix_9f62a046a1177c410061f836');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_giveaway_winners_option_stars_giveaw_0f7ecce3eb88');
    }
};
