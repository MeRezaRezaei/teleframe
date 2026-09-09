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
        Schema::create('tl_messages_peer_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_865e0d54cfbf1ab45512828c');
            $table->index('account_id', 'ix_9683344020dbc2fd126afc9f');
        });
        Schema::create('tl_messages_peer_dialogs_peer_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_peer_dialogs')->cascadeOnDelete();
            $table->uuid('state');
            $table->index('state', 'ix_3739288c062d327e859f92c6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_da10f0a61f26de979a292f1c');
        });
        Schema::create('tl_messages_peer_dialogs_peer_dialogs__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_dialogs_peer_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_dc9dd944e573c05d74f4');
            $table->index('account_id', 'ix_13afe552f7b7853c938b99b4');
        });
        Schema::create('tl_messages_peer_dialogs_peer_dialogs__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_dialogs_peer_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_d074770864baa7133726');
            $table->index('account_id', 'ix_9e9c50c388ce4474b76f4cf9');
        });
        Schema::create('tl_messages_peer_dialogs_peer_dialogs__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_dialogs_peer_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b23e8c28472da590a544');
            $table->index('account_id', 'ix_6a99724f8ba9fcbd8f07fee6');
        });
        Schema::create('tl_messages_peer_dialogs_peer_dialogs__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_peer_dialogs_peer_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1f8e0da34746b120e56c');
            $table->index('account_id', 'ix_a6fb6fa4724f0cf8186e505a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_peer_dialogs_peer_dialogs__users');
        Schema::dropIfExists('tl_messages_peer_dialogs_peer_dialogs__chats');
        Schema::dropIfExists('tl_messages_peer_dialogs_peer_dialogs__messages');
        Schema::dropIfExists('tl_messages_peer_dialogs_peer_dialogs__dialogs');
        Schema::dropIfExists('tl_messages_peer_dialogs_peer_dialogs');
        Schema::dropIfExists('tl_messages_peer_dialogs');
    }
};
