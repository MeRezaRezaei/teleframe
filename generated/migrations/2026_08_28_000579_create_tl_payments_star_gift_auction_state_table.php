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
            $table->index('constructor_id', 'ix_fcc4c08a257288f6e38fd24b');
            $table->index('account_id', 'ix_6736c7a88d1c57ff4935698f');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_auction_state')->cascadeOnDelete();
            $table->uuid('gift');
            $table->index('gift', 'ix_e1af9e892753de3da7566145');
            $table->uuid('state');
            $table->index('state', 'ix_9bbacdff05bd036f82780ea9');
            $table->uuid('user_state');
            $table->index('user_state', 'ix_3725762a1c3662b4d63e688c');
            $table->integer('timeout');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_211a60f74f4ae77a096b5210');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_787ef8d63c6d', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0855df302b8aca6260ee');
            $table->index('account_id', 'ix_3a8c11edffc2cf962dbc6600');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_060c8b849315', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_22302317ac2522d7a1a1');
            $table->index('account_id', 'ix_2421e5dfae9aa8c356b3ad42');
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
