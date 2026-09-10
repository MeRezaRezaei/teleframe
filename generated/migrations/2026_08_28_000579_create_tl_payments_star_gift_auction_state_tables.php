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
        Schema::create('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_e1af9e892753de3da7566145');
            $table->bigInteger('state')->nullable();
            $table->index('state', 'ix_9bbacdff05bd036f82780ea9');
            $table->bigInteger('user_state')->nullable();
            $table->index('user_state', 'ix_3725762a1c3662b4d63e688c');
            $table->integer('timeout')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c1920bf6d03ba61427791d2d');
            $table->index('account_id', 'ix_211a60f74f4ae77a096b5210');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_787ef8d63c6d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff', 'id', 'fk_e9ff79286add5e290ad7b593')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0855df302b8aca6260ee');
            $table->index('account_id', 'ix_3a8c11edffc2cf962dbc6600');
        });
        Schema::create('tl_payments_star_gift_auction_state_star_gift_060c8b849315', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_payments_star_gift_auction_state_star_gift_ba2a6a814fff', 'id', 'fk_b0c79911119a20b1b4911e0f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
