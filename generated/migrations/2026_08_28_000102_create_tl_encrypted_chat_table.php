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
        Schema::create('tl_encrypted_chat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_42a282c263ff9f0a965d2d7f');
            $table->index('account_id', 'ix_166d63debbc2c85dcac622cc');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_912d16da02863e1b9cb36552');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_c8050839041e5a15253d009f');
            $table->binary('g_a_or_b');
            $table->bigInteger('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_eb819daacaea8eb02d081862');
            $table->unique(['account_id', 'tl_id'], 'ux_e9fcc1e96a3abce75e0e');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_discarded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('history_deleted')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b2ea5fc94d3905e215ee5f18');
            $table->unique(['account_id', 'tl_id'], 'ux_2641e16a46b718b7b69a');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1745e42f77291a5cb333e7e1');
            $table->unique(['account_id', 'tl_id'], 'ux_fded20f0d341ebda2fb0');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_requested', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('folder_id')->nullable();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_83ac137a3442c23f0aeb8d10');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_9db5a7233283f565425a7aac');
            $table->binary('g_a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_08919fd237bfa395415eabb3');
            $table->unique(['account_id', 'tl_id'], 'ux_52144a12a8faa2378670');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_waiting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->index('admin_id', 'ix_324f25130521fc9bc62ce86c');
            $table->bigInteger('participant_id');
            $table->index('participant_id', 'ix_41eb2250add5302c919c8d84');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_072721cc33c8486873828cba');
            $table->unique(['account_id', 'tl_id'], 'ux_2e4ea47cf728c1bea76a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_chat_encrypted_chat_waiting');
        Schema::dropIfExists('tl_encrypted_chat_encrypted_chat_requested');
        Schema::dropIfExists('tl_encrypted_chat_encrypted_chat_empty');
        Schema::dropIfExists('tl_encrypted_chat_encrypted_chat_discarded');
        Schema::dropIfExists('tl_encrypted_chat_encrypted_chat');
        Schema::dropIfExists('tl_encrypted_chat');
    }
};
