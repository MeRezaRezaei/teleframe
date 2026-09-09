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
        Schema::create('tl_top_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5dc806029365e30221d157d9');
            $table->index('account_id', 'ix_fab8a76d5d0edf7b7017ad77');
        });
        Schema::create('tl_top_peer_top_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_top_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_68193df342d9431b49f36328');
            $table->double('rating');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9139e784047174e30522e749');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_top_peer_top_peer');
        Schema::dropIfExists('tl_top_peer');
    }
};
