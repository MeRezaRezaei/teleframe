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
        Schema::create('tl_star_gift_active_auction_state', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_active_auction_state')->cascadeOnDelete();
            $table->uuid('gift');
            $table->uuid('state');
            $table->uuid('user_state');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_active_auction_state_star_gift_a_0f6a2e549dbc');
        Schema::dropIfExists('tl_star_gift_active_auction_state');
    }
};
