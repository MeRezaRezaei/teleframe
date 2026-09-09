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
            $table->index('constructor_id', 'ix_e7cb4f9662bc19a2d9029ed2');
            $table->index('account_id', 'ix_205f72b93a77e4ed2485da80');
        });
        Schema::create('tl_messages_dialogs_dialogs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_551a5854085e7e27f3fa54db');
        });
        Schema::create('tl_messages_dialogs_dialogs__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0e52e617288245e4ad0c');
            $table->index('account_id', 'ix_5bed707f7fff5b2cca6fa0d7');
        });
        Schema::create('tl_messages_dialogs_dialogs__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_0898f30e6a4b66295720');
            $table->index('account_id', 'ix_474705724c009bd1253b6eb7');
        });
        Schema::create('tl_messages_dialogs_dialogs__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_405fc5e37dfd2c477e1c');
            $table->index('account_id', 'ix_9fff5a6a980f5559ad0621f4');
        });
        Schema::create('tl_messages_dialogs_dialogs__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e94f8e5dd7ce9aa8fc69');
            $table->index('account_id', 'ix_8988485cc93b8f89c3d9bb38');
        });
        Schema::create('tl_messages_dialogs_dialogs_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_de6d86a184772e7e8a59cc39');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_dialogs')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9120be00cdc6a57d054659d2');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__dialogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_928b96c2229c6f555154');
            $table->index('account_id', 'ix_5fe148f05f40ae50edb200a4');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3292704e5274da7bde00');
            $table->index('account_id', 'ix_810524ff599523c168890aa4');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cec5baeead077c653bc8');
            $table->index('account_id', 'ix_dcf930b343d962afa127ee10');
        });
        Schema::create('tl_messages_dialogs_dialogs_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_dialogs_dialogs_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_06404969aee15dd4c0f5');
            $table->index('account_id', 'ix_0278611c0494e90afc0b33d2');
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
