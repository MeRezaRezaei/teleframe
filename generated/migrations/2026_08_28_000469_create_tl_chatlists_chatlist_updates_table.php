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
        Schema::create('tl_chatlists_chatlist_updates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chatlists_chatlist_updates')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_update_ca3e3eeccb63', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bca6e764b9f57f452280');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4ba9f6c8ea169e0f7e29');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2bef073a8ea3437a75a2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates__users');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates__chats');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_update_ca3e3eeccb63');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates');
        Schema::dropIfExists('tl_chatlists_chatlist_updates');
    }
};
