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
        Schema::create('tl_stars_rating_stars_rating', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('level')->nullable();
            $table->bigInteger('current_level_stars')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->bigInteger('next_level_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb713956ffce0a8183032a21');
            $table->index('account_id', 'ix_994a48c4641874e2f8c2c3bf');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_rating_stars_rating');
    }
};
