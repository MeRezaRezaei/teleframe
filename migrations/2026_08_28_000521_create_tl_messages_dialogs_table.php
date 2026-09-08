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
        Schema::create('tl_messages_dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_dialogs_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_dialogs_dialogs__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0e52e617288245e4ad0c');
        });
        Schema::create('tl_messages_dialogs_dialogs__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_0898f30e6a4b66295720');
        });
        Schema::create('tl_messages_dialogs_dialogs__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_405fc5e37dfd2c477e1c');
        });
        Schema::create('tl_messages_dialogs_dialogs__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e94f8e5dd7ce9aa8fc69');
        });
        Schema::create('tl_messages_dialogs_dialogs_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_dialogs_dialogs_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_928b96c2229c6f555154');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3292704e5274da7bde00');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_cec5baeead077c653bc8');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_06404969aee15dd4c0f5');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_dialogs_dialogs_slice__users');
        Schema::dropIfExists('tl_messages_dialogs_dialogs_slice__chats');
        Schema::dropIfExists('tl_messages_dialogs_dialogs_slice__messages');
        Schema::dropIfExists('tl_messages_dialogs_dialogs_slice__dialogs');
        Schema::dropIfExists('tl_messages_dialogs_dialogs_slice');
        Schema::dropIfExists('tl_messages_dialogs_dialogs_not_modified');
        Schema::dropIfExists('tl_messages_dialogs_dialogs__users');
        Schema::dropIfExists('tl_messages_dialogs_dialogs__chats');
        Schema::dropIfExists('tl_messages_dialogs_dialogs__messages');
        Schema::dropIfExists('tl_messages_dialogs_dialogs__dialogs');
        Schema::dropIfExists('tl_messages_dialogs_dialogs');
        Schema::dropIfExists('tl_messages_dialogs');
    }
};
