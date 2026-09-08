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
        Schema::create('tl_stars_transaction_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_a_p_i', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_ads', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_app_store', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_peer_fragment', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_60215ddb959c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_stars_transaction_peer_stars_transaction_p_dea11222315a', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_stars_transaction_peer')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_dea11222315a');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_p_60215ddb959c');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_fragment');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_app_store');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_ads');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer_a_p_i');
        Schema::dropIfExists('tl_stars_transaction_peer_stars_transaction_peer');
        Schema::dropIfExists('tl_stars_transaction_peer');
    }
};
