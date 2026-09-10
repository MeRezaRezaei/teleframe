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
        Schema::create('tl_auction_bid_level_auction_bid_level', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('pos')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_47cea5372e6af288216eced2');
            $table->index('account_id', 'ix_2477f8384db6f4b5324ee3c2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auction_bid_level_auction_bid_level');
    }
};
