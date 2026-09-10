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
        Schema::create('tl_messages_exported_chat_invites_exported_chat_invites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7e81207118c76e54644407f6');
            $table->index('account_id', 'ix_25e4e071f5b095049217cac9');
        });
        Schema::create('tl_messages_exported_chat_invites_exported_ch_6aaa781e3e6f', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ddeeaf51b5d744ead9f75cd4')->references('id')->on('tl_messages_exported_chat_invites_exported_chat_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_58155d356c05ff9273fd');
            $table->index('account_id', 'ix_6e32d85113954d8050dd5905');
        });
        Schema::create('tl_messages_exported_chat_invites_exported_ch_a8d7d20365d9', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8f1e892ad7b0becb3085a2a1')->references('id')->on('tl_messages_exported_chat_invites_exported_chat_invites')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_9fdd09b6472d0b80f8a8');
            $table->index('account_id', 'ix_980ab839ef7b3b08f9decd60');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_ch_a8d7d20365d9');
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_ch_6aaa781e3e6f');
        Schema::dropIfExists('tl_messages_exported_chat_invites_exported_chat_invites');
    }
};
