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
        Schema::create('tl_bot_command_scope', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_chat_admins', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer_admins', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->uuid('peer');
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->uuid('peer');
            $table->uuid('user_id');
            $table->timestamps();
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_users');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_peer_user');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_peer_admins');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_peer');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_default');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_chats');
        Schema::dropIfExists('tl_bot_command_scope_bot_command_scope_chat_admins');
        Schema::dropIfExists('tl_bot_command_scope');
    }
};
