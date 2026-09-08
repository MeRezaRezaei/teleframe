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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_encrypted_chat_encrypted_chat', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->binary('g_a_or_b');
            $table->bigInteger('key_fingerprint');
            $table->timestamps();
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_discarded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('history_deleted')->default(false);
            $table->integer('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->timestamps();
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_requested', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('folder_id')->nullable();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->binary('g_a');
            $table->timestamps();
        });
        Schema::create('tl_encrypted_chat_encrypted_chat_waiting', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_chat')->cascadeOnDelete();
            $table->integer('tl_id');
            $table->bigInteger('access_hash');
            $table->integer('date');
            $table->bigInteger('admin_id');
            $table->bigInteger('participant_id');
            $table->timestamps();
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
