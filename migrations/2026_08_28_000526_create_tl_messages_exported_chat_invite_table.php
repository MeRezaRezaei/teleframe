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
        Schema::create('tl_messages_exported_chat_invite', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_exported_chat_invite_exported_chat_invite', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_exported_chat_invite')->cascadeOnDelete();
            $table->uuid('invite');
            $table->timestamps();
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_0c41e9ae4e71', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_exported_chat_invite_exported_chat_invite')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7c328d56654a15594123');
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_d180f3d61600', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_exported_chat_invite')->cascadeOnDelete();
            $table->uuid('invite');
            $table->uuid('new_invite');
            $table->timestamps();
        });
        Schema::create('tl_messages_exported_chat_invite_exported_cha_4638a2b5d812', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_exported_chat_invite_exported_cha_d180f3d61600')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_9c47da4cc2e78d9f031c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_4638a2b5d812');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_d180f3d61600');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_cha_0c41e9ae4e71');
        Schema::dropIfExists('tl_messages_exported_chat_invite_exported_chat_invite');
        Schema::dropIfExists('tl_messages_exported_chat_invite');
    }
};
