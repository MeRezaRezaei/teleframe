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
            $table->index('constructor_id', 'ix_43cd2ee9b0a6233576578347');
            $table->index('account_id', 'ix_efde5c6143ed6e45476029f2');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_chat_admins', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_99a73a4bd34059adddac8c44');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_chats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b296e9ce0b22a95d975d0edb');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_df8a2c999f319ab5adefb2c9');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_d9d5b631c29263f35b94b847');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_add70cf4800c0ed9f6948623');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer_admins', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_e6e79d1e243b45cd4dd5cf46');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_40ac8e392672cc45c02960bd');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_peer_user', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('peer');
            $table->index('peer', 'ix_45b87cf95661340a29176779');
            $table->uuid('user_id');
            $table->index('user_id', 'ix_2337e24ad5c02f4040802073');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_dfb51f9e456d45402d42ad61');
        });
        Schema::create('tl_bot_command_scope_bot_command_scope_users', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_command_scope')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_976a38acb57b354f7d6fc96e');
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
