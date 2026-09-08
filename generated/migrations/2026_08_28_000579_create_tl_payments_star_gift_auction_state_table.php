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
        Schema::create('tl_payments_star_gift_auction_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_auction_state')->cascadeOnDelete();
            $table->uuid('gift');
            $table->uuid('state');
            $table->uuid('user_state');
            $table->integer('timeout');
            $table->timestamps();
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_787ef8d63c6d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0855df302b8aca6260ee');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_060c8b849315', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_22302317ac2522d7a1a1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_auction_state_star_gift_060c8b849315');
        Schema::dropIfExists('tl_payments_star_gift_auction_state_star_gift_787ef8d63c6d');
        Schema::dropIfExists('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff');
        Schema::dropIfExists('tl_payments_star_gift_auction_state');
    }
};
