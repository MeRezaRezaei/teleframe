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
        Schema::create('tl_channels_send_as_peers_send_as_peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_79c335fcc4521633444bd674');
            $table->index('account_id', 'ix_f40e837dba3d55a51d88ad8a');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__peers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a2af197ea3b2b28d31f5c93a')->references('id')->on('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_666ca25a694538a5ed53');
            $table->index('account_id', 'ix_626383ec6dc5672fb22a68f7');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_1e2ebca3e2dc88ec4d6a39b3')->references('id')->on('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_285e3c0dbe285e6ca155');
            $table->index('account_id', 'ix_6fc7757027dc57d314495986');
        });
        Schema::create('tl_channels_send_as_peers_send_as_peers__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_2d7f351f4a3d8f2117079ec6')->references('id')->on('tl_channels_send_as_peers_send_as_peers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_46d6d8a456e7b1b6bae1');
            $table->index('account_id', 'ix_3466ca02a722bf557ab37719');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__users');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__chats');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers__peers');
        Schema::dropIfExists('tl_channels_send_as_peers_send_as_peers');
    }
};
