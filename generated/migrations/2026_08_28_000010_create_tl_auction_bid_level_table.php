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
        Schema::create('tl_auction_bid_level', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2800361854feafd1cac6c00c');
            $table->index('account_id', 'ix_24228509a448498261a64edd');
        });
        Schema::create('tl_auction_bid_level_auction_bid_level', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auction_bid_level')->cascadeOnDelete();
            $table->integer('pos');
            $table->bigInteger('amount');
            $table->integer('date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2477f8384db6f4b5324ee3c2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auction_bid_level_auction_bid_level');
        Schema::dropIfExists('tl_auction_bid_level');
    }
};
