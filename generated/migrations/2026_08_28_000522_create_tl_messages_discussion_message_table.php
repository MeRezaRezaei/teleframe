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
            $table->index('constructor_id', 'ix_e0996f198f25471c3c9fdaa7');
            $table->index('account_id', 'ix_3730dd9bfcba2cb23b446249');
        });
        Schema::create('tl_messages_discussion_message_discussion_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_discussion_message')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('max_id')->nullable();
            $table->integer('read_inbox_max_id')->nullable();
            $table->integer('read_outbox_max_id')->nullable();
            $table->integer('unread_count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2fdbdc26ca4e5501f4b8e6f3');
        });
        Schema::create('tl_messages_discussion_message_discussion_mes_ff71ddca7c9e', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_359902dafa30df282b20');
            $table->index('account_id', 'ix_3cfa97abefdca01de6e49518');
        });
        Schema::create('tl_messages_discussion_message_discussion_message__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9511a173f25ea6ca676d');
            $table->index('account_id', 'ix_d71fcf3921b4f05356d49a4e');
        });
        Schema::create('tl_messages_discussion_message_discussion_message__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_discussion_message_discussion_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7c5119c201298de6f40a');
            $table->index('account_id', 'ix_37f9806320909dbbaf8f7491');
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
