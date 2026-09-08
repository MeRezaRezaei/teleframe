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
        Schema::create('tl_messages_discussion_message', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_discussion_message_discussion_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_discussion_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('max_id')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count');
            $table->timestamps();
        });
        Schema::create('tl_messages_discussion_message_discussion_mes_ff71ddca7c9e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_359902dafa30df282b20');
        });
        Schema::create('tl_messages_discussion_message_discussion_message__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9511a173f25ea6ca676d');
        });
        Schema::create('tl_messages_discussion_message_discussion_message__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7c5119c201298de6f40a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_discussion_message_discussion_message__users');
        Schema::dropIfExists('tl_messages_discussion_message_discussion_message__chats');
        Schema::dropIfExists('tl_messages_discussion_message_discussion_mes_ff71ddca7c9e');
        Schema::dropIfExists('tl_messages_discussion_message_discussion_message');
        Schema::dropIfExists('tl_messages_discussion_message');
    }
};
