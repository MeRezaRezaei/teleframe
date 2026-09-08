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
        Schema::create('tl_chatlists_chatlist_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chatlists_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('title_noanimate')->default(false);
            $table->uuid('title');
            $table->text('emoticon')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__peers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8a24b5683be2c9f3e0db');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9552067a2d3a31c0a605');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_f8740832af9426b344f4');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite_already', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chatlists_chatlist_invite')->cascadeOnDelete();
            $table->integer('filter_id');
            $table->timestamps();
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__e88ac70d6871', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_5b3ce2c728689e6a00c8');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6718b1fb3bf75c584b80');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__1d33efbd497a', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_eb91a1204fcad1bd27ea');
        });
        Schema::create('tl_chatlists_chatlist_invite_chatlist_invite__f31957d13fad', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_invite_chatlist_invite_already')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e570a8ab4efec6e9cb3f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__f31957d13fad');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__1d33efbd497a');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__e88ac70d6871');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite_already');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__users');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__chats');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite__peers');
        Schema::dropIfExists('tl_chatlists_chatlist_invite_chatlist_invite');
        Schema::dropIfExists('tl_chatlists_chatlist_invite');
    }
};
