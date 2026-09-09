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
        Schema::create('tl_top_peer_category_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bf8b4ba8e89f6d21d0a2c545');
            $table->index('account_id', 'ix_8b361e2dc9347466a6a68ed4');
        });
        Schema::create('tl_top_peer_category_peers_top_peer_category_peers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer_category_peers')->cascadeOnDelete();
            $table->uuid('category');
            $table->index('category', 'ix_d5a4ccf59da7e9dd996cd836');
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a466a81175f7b481ef1724b9');
        });
        Schema::create('tl_top_peer_category_peers_top_peer_category_peers__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_top_peer_category_peers_top_peer_category_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5be6ca247abeb1692d5d');
            $table->index('account_id', 'ix_5815317c857578152a8591fa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_top_peer_category_peers_top_peer_category_peers__peers');
        Schema::dropIfExists('tl_top_peer_category_peers_top_peer_category_peers');
        Schema::dropIfExists('tl_top_peer_category_peers');
    }
};
