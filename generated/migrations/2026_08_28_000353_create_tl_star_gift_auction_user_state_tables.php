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
        Schema::create('tl_star_gift_auction_user_state_star_gift_auc_62491a9be5e4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('returned')->default(false);
            $table->bigInteger('bid_amount')->nullable();
            $table->integer('bid_date')->nullable();
            $table->bigInteger('min_bid_amount')->nullable();
            $table->bigInteger('bid_peer')->nullable();
            $table->index('bid_peer', 'ix_3e3a59406d3564b0d73c854f');
            $table->integer('acquired_count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5b79501f74043e3689a02b2f');
            $table->index('account_id', 'ix_82b756e93ef82af5800c9ee8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_user_state_star_gift_auc_62491a9be5e4');
    }
};
