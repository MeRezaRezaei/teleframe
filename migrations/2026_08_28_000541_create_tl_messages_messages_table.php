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
        Schema::create('tl_messages_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_messages_channel_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_messages')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->integer('pts');
            $table->integer('count');
            $table->integer('offset_id_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_messages_channel_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c7f7ad0b4d0de4a9eae1');
        });
        Schema::create('tl_messages_messages_channel_messages__topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_c153c549b622b162d66f');
        });
        Schema::create('tl_messages_messages_channel_messages__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d116205bf5be2a14114b');
        });
        Schema::create('tl_messages_messages_channel_messages__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_channel_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_df059b3bc356deaae0b9');
        });
        Schema::create('tl_messages_messages_messages', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_messages')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_messages_messages__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d3116c59aef2426bcaa5');
        });
        Schema::create('tl_messages_messages_messages__topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_aaf8340bdb15711d53b2');
        });
        Schema::create('tl_messages_messages_messages__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2f84f9693760818d8d7c');
        });
        Schema::create('tl_messages_messages_messages__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9973ff1293932d5bc53d');
        });
        Schema::create('tl_messages_messages_messages_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_messages')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_messages_messages_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_messages')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('inexact')->default(false);
            $table->integer('count');
            $table->integer('next_rate')->nullable();
            $table->integer('offset_id_offset')->nullable();
            $table->uuid('search_flood')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_messages_messages_messages_slice__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8228322cf3e5e0eba04b');
        });
        Schema::create('tl_messages_messages_messages_slice__topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8c5e6b160eb71310139c');
        });
        Schema::create('tl_messages_messages_messages_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_84f7212688708458c8ea');
        });
        Schema::create('tl_messages_messages_messages_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_messages_messages_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_52d3e392a396a4545b9b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_messages_messages_slice__users');
        Schema::dropIfExists('tl_messages_messages_messages_slice__chats');
        Schema::dropIfExists('tl_messages_messages_messages_slice__topics');
        Schema::dropIfExists('tl_messages_messages_messages_slice__messages');
        Schema::dropIfExists('tl_messages_messages_messages_slice');
        Schema::dropIfExists('tl_messages_messages_messages_not_modified');
        Schema::dropIfExists('tl_messages_messages_messages__users');
        Schema::dropIfExists('tl_messages_messages_messages__chats');
        Schema::dropIfExists('tl_messages_messages_messages__topics');
        Schema::dropIfExists('tl_messages_messages_messages__messages');
        Schema::dropIfExists('tl_messages_messages_messages');
        Schema::dropIfExists('tl_messages_messages_channel_messages__users');
        Schema::dropIfExists('tl_messages_messages_channel_messages__chats');
        Schema::dropIfExists('tl_messages_messages_channel_messages__topics');
        Schema::dropIfExists('tl_messages_messages_channel_messages__messages');
        Schema::dropIfExists('tl_messages_messages_channel_messages');
        Schema::dropIfExists('tl_messages_messages');
    }
};
