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
        Schema::create('tl_top_peer_category_peers_top_peer_category_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('category')->nullable();
            $table->index('category', 'ix_d5a4ccf59da7e9dd996cd836');
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2d2f2a5ac62c44fff3eedff3');
            $table->index('account_id', 'ix_a466a81175f7b481ef1724b9');
        });
        Schema::create('tl_top_peer_category_peers_top_peer_category_peers__peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_top_peer_category_peers_top_peer_category_peers', 'id', 'fk_d6808bf8129e57d9a761b736')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_5be6ca247abeb1692d5d');
            $table->index('account_id', 'ix_5815317c857578152a8591fa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_top_peer_category_peers_top_peer_category_peers__peers');
        Schema::dropIfExists('tl_top_peer_category_peers_top_peer_category_peers');
    }
};
