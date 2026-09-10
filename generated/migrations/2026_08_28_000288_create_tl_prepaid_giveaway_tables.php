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
        Schema::create('tl_prepaid_giveaway_prepaid_giveaway', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('months')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_08a2d1d0400d71212ae83472');
            $table->index('account_id', 'ix_29c8ccccc1e4f7a27838a2ae');
        });
        Schema::create('tl_prepaid_giveaway_prepaid_stars_giveaway', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('stars')->nullable();
            $table->integer('quantity')->nullable();
            $table->integer('boosts')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_740b83f22c9a7feb6a6845bd');
            $table->index('account_id', 'ix_bc1abb5308aea96bee25b624');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_prepaid_giveaway_prepaid_stars_giveaway');
        Schema::dropIfExists('tl_prepaid_giveaway_prepaid_giveaway');
    }
};
