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
        Schema::create('tl_star_gift_auction_acquired_gift', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_star_gift_auction_acquired_gift')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('name_hidden')->default(false);
            $table->uuid('peer');
            $table->integer('date');
            $table->bigInteger('bid_amount');
            $table->integer('round');
            $table->integer('pos');
            $table->uuid('message')->nullable();
            $table->integer('gift_num')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_auction_acquired_gift_star_gift__f6508cc9bcc2');
        Schema::dropIfExists('tl_star_gift_auction_acquired_gift');
    }
};
