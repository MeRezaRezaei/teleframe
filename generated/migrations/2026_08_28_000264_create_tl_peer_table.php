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
        Schema::create('tl_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_peer_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->timestamps();
        });
        Schema::create('tl_peer_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_peer_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_peer_peer_user');
        Schema::dropIfExists('tl_peer_peer_chat');
        Schema::dropIfExists('tl_peer_peer_channel');
        Schema::dropIfExists('tl_peer');
    }
};
