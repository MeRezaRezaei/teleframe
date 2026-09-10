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
        Schema::create('tl_star_gift_auction_round_star_gift_auction_round', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('num')->nullable();
            $table->integer('duration')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b71928c32dc5421ae7c6430d');
            $table->index('account_id', 'ix_5fcf8b40db6aaf6025285e7a');
        });
        Schema::create('tl_star_gift_auction_round_star_gift_auction__a0b7925a30f9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('num')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('extend_top')->nullable();
            $table->integer('extend_window')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4e61f481cfb8a87112cf8c09');
            $table->index('account_id', 'ix_36e43a873a48076974da0868');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_round_star_gift_auction__a0b7925a30f9');
        Schema::dropIfExists('tl_star_gift_auction_round_star_gift_auction_round');
    }
};
