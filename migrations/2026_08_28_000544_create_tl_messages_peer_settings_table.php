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
        Schema::create('tl_messages_peer_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_peer_settings_peer_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_peer_settings')->cascadeOnDelete();
            $table->uuid('settings');
            $table->timestamps();
        });
        Schema::create('tl_messages_peer_settings_peer_settings__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_settings_peer_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6f57b7d712bbf0c9ce43');
        });
        Schema::create('tl_messages_peer_settings_peer_settings__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_settings_peer_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f500bf1f2b4be987703e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings__users');
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings__chats');
        Schema::dropIfExists('tl_messages_peer_settings_peer_settings');
        Schema::dropIfExists('tl_messages_peer_settings');
    }
};
