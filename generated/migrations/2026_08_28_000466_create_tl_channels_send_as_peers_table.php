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
        Schema::create('tl_channels_send_as_peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channels_send_as_peers')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_666ca25a694538a5ed53');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_285e3c0dbe285e6ca155');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_46d6d8a456e7b1b6bae1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__users');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__chats');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__peers');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers');
        Schema::dropIfExists('tl_channels_send_as_peers');
    }
};
