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
        Schema::create('tl_peer_located_peer_located', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('peer')->nullable();
            $table->index('peer', 'ix_55ec9250e978076ee4fd32b7');
            $table->integer('expires')->nullable();
            $table->integer('distance')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fa0c56dda99b8157799f56bb');
            $table->index('account_id', 'ix_9089a7537fc04933bfdd7b34');
        });
        Schema::create('tl_peer_located_peer_self_located', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('expires')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d9aa1a2d443ec45fe5c24091');
            $table->index('account_id', 'ix_25d7c9f39ff6a55698c1a7a6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_located_peer_self_located');
        Schema::dropIfExists('tl_peer_located_peer_located');
    }
};
