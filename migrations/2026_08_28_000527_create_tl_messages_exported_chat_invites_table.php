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
        Schema::create('tl_messages_exported_chat_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_exported_chat_invites_exported_chat_invites', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_exported_chat_invites')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_exported_chat_invites_exported_ch_6aaa781e3e6f', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_exported_chat_invites_exported_chat_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_58155d356c05ff9273fd');
        });
        Schema::create('tl_messages_exported_chat_invites_exported_ch_a8d7d20365d9', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_exported_chat_invites_exported_chat_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9fdd09b6472d0b80f8a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_ch_a8d7d20365d9');
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_ch_6aaa781e3e6f');
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_chat_invites');
        Schema::dropIfExists('tl_messages_exported_chat_invites');
    }
};
