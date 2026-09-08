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
        Schema::create('tl_input_peer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_peer_input_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('channel_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_channel_from_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->bigInteger('channel_id');
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('chat_id');
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_self', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->bigInteger('user_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_peer_input_peer_user_from_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_peer')->cascadeOnDelete();
            $table->uuid('peer');
            $table->integer('msg_id');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_peer_input_peer_user_from_message');
        Schema::dropIfExists('tl_input_peer_input_peer_user');
        Schema::dropIfExists('tl_input_peer_input_peer_self');
        Schema::dropIfExists('tl_input_peer_input_peer_empty');
        Schema::dropIfExists('tl_input_peer_input_peer_chat');
        Schema::dropIfExists('tl_input_peer_input_peer_channel_from_message');
        Schema::dropIfExists('tl_input_peer_input_peer_channel');
        Schema::dropIfExists('tl_input_peer');
    }
};
