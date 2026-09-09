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
        Schema::create('tl_star_gift_auction_round', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_358fc7ba103d0780d40885de');
            $table->index('account_id', 'ix_56e0dd030a689a947b4ed16d');
        });
        Schema::create('tl_star_gift_auction_round_star_gift_auction_round', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_round')->cascadeOnDelete();
            $table->integer('num');
            $table->integer('duration');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_5fcf8b40db6aaf6025285e7a');
        });
        Schema::create('tl_star_gift_auction_round_star_gift_auction__a0b7925a30f9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_round')->cascadeOnDelete();
            $table->integer('num');
            $table->integer('duration');
            $table->integer('extend_top');
            $table->integer('extend_window');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_36e43a873a48076974da0868');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_round_star_gift_auction__a0b7925a30f9');
        Schema::dropIfExists('tl_star_gift_auction_round_star_gift_auction_round');
        Schema::dropIfExists('tl_star_gift_auction_round');
    }
};
