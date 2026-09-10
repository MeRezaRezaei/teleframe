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
        Schema::create('tl_messages_peer_settings_peer_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('settings')->nullable();
            $table->index('settings', 'ix_20db4dbde7c36c1a48c1278d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b2afce44b3500d8fd1e27a30');
            $table->index('account_id', 'ix_d96d17d9b97a87cd73d75751');
        });
        Schema::create('tl_messages_peer_settings_peer_settings__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f9c95d4692e9a59bac5df141')->references('id')->on('tl_messages_peer_settings_peer_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6f57b7d712bbf0c9ce43');
            $table->index('account_id', 'ix_5883ffe8e0292a755039c41e');
        });
        Schema::create('tl_messages_peer_settings_peer_settings__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ede4d935be14fb112960baf3')->references('id')->on('tl_messages_peer_settings_peer_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_f500bf1f2b4be987703e');
            $table->index('account_id', 'ix_78f4876c42ada067499db2fc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings__users');
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings__chats');
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings');
    }
};
