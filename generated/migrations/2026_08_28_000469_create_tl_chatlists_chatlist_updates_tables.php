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
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_63505888c932a1fd69e9f572');
            $table->index('account_id', 'ix_480279c16a58e9e9fae03bc9');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_update_ca3e3eeccb63', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates', 'id', 'fk_65dbda77b52678c3d2ab760f')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bca6e764b9f57f452280');
            $table->index('account_id', 'ix_4d69d1628bb287cf05c75612');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates', 'id', 'fk_04a65f8fe2888b3bb7b90192')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4ba9f6c8ea169e0f7e29');
            $table->index('account_id', 'ix_85f67a2cf81fe7f5a0ac931e');
        });
        Schema::create('tl_chatlists_chatlist_updates_chatlist_updates__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chatlists_chatlist_updates_chatlist_updates', 'id', 'fk_2e82dfbe9aaab0623f41a7d8')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2bef073a8ea3437a75a2');
            $table->index('account_id', 'ix_5aeee8d027ee283a4620db00');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates__users');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates__chats');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_update_ca3e3eeccb63');
        Schema::dropIfExists('tl_chatlists_chatlist_updates_chatlist_updates');
    }
};
