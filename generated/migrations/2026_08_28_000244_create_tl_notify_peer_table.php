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
        Schema::create('tl_notify_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_3a0036506764c00541f75804');
            $table->index('account_id', 'ix_6f0c7742cf52c348f5980d21');
        });
        Schema::create('tl_notify_peer_notify_broadcasts', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notify_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fbf0208bf5cb5ed003eb6dc4');
        });
        Schema::create('tl_notify_peer_notify_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notify_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_33a921b4a3de1b8a94a0422d');
        });
        Schema::create('tl_notify_peer_notify_forum_topic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notify_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_bc15428056a9bebb207e2ed7');
            $table->integer('top_msg_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_be0b4e07ee6982b392cc7f61');
        });
        Schema::create('tl_notify_peer_notify_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notify_peer')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_fda54ac37d3e3bcf6187cd03');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c78165e23ef5867aa0b8b5c3');
        });
        Schema::create('tl_notify_peer_notify_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_notify_peer')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_02b13cd75255003b6b11ce09');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_notify_peer_notify_users');
        Schema::dropIfExists('tl_notify_peer_notify_peer');
        Schema::dropIfExists('tl_notify_peer_notify_forum_topic');
        Schema::dropIfExists('tl_notify_peer_notify_chats');
        Schema::dropIfExists('tl_notify_peer_notify_broadcasts');
        Schema::dropIfExists('tl_notify_peer');
    }
};
