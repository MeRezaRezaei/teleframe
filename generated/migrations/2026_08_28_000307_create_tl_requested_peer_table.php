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
            $table->index('constructor_id', 'ix_3b35ef7dcb7d06b8043a5362');
            $table->index('account_id', 'ix_3237ef368c7c14fa963cf76b');
        });
        Schema::create('tl_requested_peer_requested_peer_channel', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('channel_id');
            $table->index('channel_id', 'ix_93f6dcc5026b2a5011da8ab6');
            $table->text('title')->nullable();
            $table->text('username')->nullable();
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_962a78719eae85b879866ad9');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_edbf5d3629626c68ed2551d0');
        });
        Schema::create('tl_requested_peer_requested_peer_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('chat_id');
            $table->index('chat_id', 'ix_fe640469957c1c2332e84410');
            $table->text('title')->nullable();
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_03d8dfc0ba15000b450ac2ee');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fc9b6fb08a5e3af7b1d18415');
        });
        Schema::create('tl_requested_peer_requested_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_requested_peer')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('user_id');
            $table->index('user_id', 'ix_cef323d230d2e152c272a36c');
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->text('username')->nullable();
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_a4a30cfb22e56b06eda6a145');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_626a5f6d5ff2ebcad67b1407');
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
