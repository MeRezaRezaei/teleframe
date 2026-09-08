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
        Schema::create('tl_star_gift_auction_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction_state', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_state')->cascadeOnDelete();
            $table->integer('version');
            $table->integer('start_date');
            $table->integer('end_date');
            $table->bigInteger('min_bid_amount');
            $table->integer('next_round_at');
            $table->integer('last_gift_num');
            $table->integer('gifts_left');
            $table->integer('current_round');
            $table->integer('total_rounds');
            $table->timestamps();
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__bb6c7ebe1d9b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_cc0f2fe61df4f86afd2a');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__3f263c3c4430', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->bigInteger('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_81e93d2b65274e46bc49');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction_state__rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_star_gift_auction_state_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_42ca4b4f2af788b93303');
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__3ffddf14cd70', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_state')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('start_date');
            $table->integer('end_date');
            $table->bigInteger('average_price');
            $table->integer('listed_count')->nullable();
            $table->integer('fragment_listed_count')->nullable();
            $table->text('fragment_listed_url')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_star_gift_auction_state_star_gift_auction__ba2b64ef8cf9', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_state')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__ba2b64ef8cf9');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__3ffddf14cd70');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction_state__rounds');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__3f263c3c4430');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction__bb6c7ebe1d9b');
        Schema::dropIfExists('tl_star_gift_auction_state_star_gift_auction_state');
        Schema::dropIfExists('tl_star_gift_auction_state');
    }
};
