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
        Schema::create('tl_peer_located', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_feea29c4574edfea1b2c50e8');
            $table->index('account_id', 'ix_90c7b10c326b2748d3a55613');
        });
        Schema::create('tl_peer_located_peer_located', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_located')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_55ec9250e978076ee4fd32b7');
            $table->integer('expires');
            $table->integer('distance');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9089a7537fc04933bfdd7b34');
        });
        Schema::create('tl_peer_located_peer_self_located', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer_located')->cascadeOnDelete();
            $table->integer('expires');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_25d7c9f39ff6a55698c1a7a6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_located_peer_self_located');
        Schema::dropIfExists('tl_peer_located_peer_located');
        Schema::dropIfExists('tl_peer_located');
    }
};
