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
        Schema::create('tl_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_updates_update_short', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->uuid('update');
            $table->integer('date');
            $table->timestamps();
        });
        Schema::create('tl_updates_update_short_chat_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('from_id');
            $table->bigInteger('chat_id');
            $table->text('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('fwd_from')->nullable();
            $table->bigInteger('via_bot_id')->nullable();
            $table->uuid('reply_to')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_updates_update_short_chat_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_chat_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0a4e729b3f356264a2b8');
        });
        Schema::create('tl_updates_update_short_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->boolean('mentioned')->default(false);
            $table->boolean('media_unread')->default(false);
            $table->boolean('silent')->default(false);
            $table->integer('tl_id');
            $table->bigInteger('user_id');
            $table->text('message');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('fwd_from')->nullable();
            $table->bigInteger('via_bot_id')->nullable();
            $table->uuid('reply_to')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_updates_update_short_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_16d2c9eb89082b8aae79');
        });
        Schema::create('tl_updates_update_short_sent_message', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('out')->default(false);
            $table->integer('tl_id');
            $table->integer('pts');
            $table->integer('pts_count');
            $table->integer('date');
            $table->uuid('media')->nullable();
            $table->integer('ttl_period')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_updates_update_short_sent_message__entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_update_short_sent_message')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bd45d68cbc4f8afd9c34');
        });
        Schema::create('tl_updates_updates', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq');
            $table->timestamps();
        });
        Schema::create('tl_updates_updates__updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b6c0efd4e8d6b429e369');
        });
        Schema::create('tl_updates_updates__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_b87bc8d0093d34ee9243');
        });
        Schema::create('tl_updates_updates__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5b5732d34d391ad11799');
        });
        Schema::create('tl_updates_updates_combined', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->integer('date');
            $table->integer('seq_start');
            $table->integer('seq');
            $table->timestamps();
        });
        Schema::create('tl_updates_updates_combined__updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5945967c5e65c9c80268');
        });
        Schema::create('tl_updates_updates_combined__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7347de1d490987ecf2af');
        });
        Schema::create('tl_updates_updates_combined__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_updates_updates_combined')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5c640e1e6f07ffa44ca6');
        });
        Schema::create('tl_updates_updates_too_long', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_updates')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_updates_updates_too_long');
        Schema::dropIfExists('tl_updates_updates_combined__chats');
        Schema::dropIfExists('tl_updates_updates_combined__users');
        Schema::dropIfExists('tl_updates_updates_combined__updates');
        Schema::dropIfExists('tl_updates_updates_combined');
        Schema::dropIfExists('tl_updates_updates__chats');
        Schema::dropIfExists('tl_updates_updates__users');
        Schema::dropIfExists('tl_updates_updates__updates');
        Schema::dropIfExists('tl_updates_updates');
        Schema::dropIfExists('tl_updates_update_short_sent_message__entities');
        Schema::dropIfExists('tl_updates_update_short_sent_message');
        Schema::dropIfExists('tl_updates_update_short_message__entities');
        Schema::dropIfExists('tl_updates_update_short_message');
        Schema::dropIfExists('tl_updates_update_short_chat_message__entities');
        Schema::dropIfExists('tl_updates_update_short_chat_message');
        Schema::dropIfExists('tl_updates_update_short');
        Schema::dropIfExists('tl_updates');
    }
};
