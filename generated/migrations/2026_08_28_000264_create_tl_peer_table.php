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
            $table->index('constructor_id', 'ix_73c9d220ac150ac4fbe68ef8');
            $table->index('account_id', 'ix_ed96a445ea7be41a652843d2');
        });
        Schema::create('tl_peer_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_6b60abe05ea85685a2b6cbe9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c07e1465a4d2f03c114f3d81');
        });
        Schema::create('tl_peer_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->index('chat_id', 'ix_b8590a6a16287b3f80f7a1c0');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b1846a27e4ed531124103803');
        });
        Schema::create('tl_peer_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_peer')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_ceded89f04f207343535c096');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_899946ce35ed2753c5236380');
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
