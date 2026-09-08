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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_8055eec565ef09bc893a');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6a3c4e68ab534e7250b5');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_81fec5f724cfbe2a390f');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_42bb141cc9c7c0b416c3');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_saved_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_946993988d590f736ccc');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_579fc484cc9773180e26');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0cb78cf8f438489185e9');
        });
        Schema::create('tl_messages_saved_dialogs_saved_dialogs_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_saved_dialogs_saved_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_492ffbc2922b468184af');
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
