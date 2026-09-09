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
        Schema::create('tl_messages_saved_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_540af52e7a43204d6c93624c');
            $table->index('account_id', 'ix_8ce1d2f72159288ba4fefd4d');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_234cb4f1c45115d097adf93d');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8055eec565ef09bc893a');
            $table->index('account_id', 'ix_6a970f759f3ba3c8bb631432');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6a3c4e68ab534e7250b5');
            $table->index('account_id', 'ix_f3e1d0109b9c1b89feec03b5');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_81fec5f724cfbe2a390f');
            $table->index('account_id', 'ix_d17556528bc78f8bd20760d5');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_42bb141cc9c7c0b416c3');
            $table->index('account_id', 'ix_be4519f7e2992a05fcb1ac42');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_45ebc405bae42d726250a3d4');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6d66215c3f27a166ac90e96f');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_946993988d590f736ccc');
            $table->index('account_id', 'ix_632ec2cf32ba785f0fd9bc59');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_579fc484cc9773180e26');
            $table->index('account_id', 'ix_c91097e0d6ca1705bb2ed281');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0cb78cf8f438489185e9');
            $table->index('account_id', 'ix_0131004d10d4199c072fd4cf');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_492ffbc2922b468184af');
            $table->index('account_id', 'ix_8136abf801fe1856cb81922c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__users');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__chats');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__messages');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice__dialogs');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_slice');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs_not_modified');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__users');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__chats');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__messages');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs__dialogs');
        Schema::dropIfExists('tl_messages_saved_dialogs_saved_dialogs');
        Schema::dropIfExists('tl_messages_saved_dialogs');
    }
};
