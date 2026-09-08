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
        Schema::create('tl_star_gift_auction_user_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_auction_user_state_star_gift_auc_62491a9be5e4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_user_state')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('returned')->default(false);
            $table->bigInteger('bid_amount')->nullable();
            $table->integer('bid_date')->nullable();
            $table->bigInteger('min_bid_amount')->nullable();
            $table->uuid('bid_peer')->nullable();
            $table->integer('acquired_count');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_user_state_star_gift_auc_62491a9be5e4');
        Schema::dropIfExists('tl_star_gift_auction_user_state');
    }
};
