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
        Schema::create('tl_requested_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_requested_peer_requested_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->text('title')->nullable();
            $table->text('username')->nullable();
            $table->uuid('photo')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_requested_peer_requested_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('chat_id');
            $table->text('title')->nullable();
            $table->uuid('photo')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_requested_peer_requested_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('username')->nullable();
            $table->uuid('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_requested_peer_requested_peer_user');
        Schema::dropIfExists('tl_requested_peer_requested_peer_chat');
        Schema::dropIfExists('tl_requested_peer_requested_peer_channel');
        Schema::dropIfExists('tl_requested_peer');
    }
};
