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
        Schema::create('tl_messages_chat_full', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_38c9c7fb2b5d116b1fc75537');
            $table->index('account_id', 'ix_0782a65e7ffcbb290cd42ef8');
        });
        Schema::create('tl_messages_chat_full_chat_full', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_full')->cascadeOnDelete();
            $table->uuid('full_chat');
            $table->index('full_chat', 'ix_251a9f7ecd29e39d9fbf9ed5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7949918bd53d0339d9befd3a');
        });
        Schema::create('tl_messages_chat_full_chat_full__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db7041f5d267073833c3');
            $table->index('account_id', 'ix_396c34066e27c362ce25fdb2');
        });
        Schema::create('tl_messages_chat_full_chat_full__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_full_chat_full')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b5af0048623cb07a1f56');
            $table->index('account_id', 'ix_197b1cbf177165f3d43f5801');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_full_chat_full__users');
        Schema::dropIfExists('tl_messages_chat_full_chat_full__chats');
        Schema::dropIfExists('tl_messages_chat_full_chat_full');
        Schema::dropIfExists('tl_messages_chat_full');
    }
};
