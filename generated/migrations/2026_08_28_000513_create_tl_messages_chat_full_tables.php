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
        Schema::create('tl_messages_chat_full_chat_full', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('full_chat')->nullable();
            $table->index('full_chat', 'ix_251a9f7ecd29e39d9fbf9ed5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7682f23ea709efc47b3bc403');
            $table->index('account_id', 'ix_7949918bd53d0339d9befd3a');
        });
        Schema::create('tl_messages_chat_full_chat_full__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_chat_full_chat_full', 'id', 'fk_a674077317e8d159c0301bf1')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_db7041f5d267073833c3');
            $table->index('account_id', 'ix_396c34066e27c362ce25fdb2');
        });
        Schema::create('tl_messages_chat_full_chat_full__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_messages_chat_full_chat_full', 'id', 'fk_b8d4ce018c8b38c864023fce')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b5af0048623cb07a1f56');
            $table->index('account_id', 'ix_197b1cbf177165f3d43f5801');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_full_chat_full__users');
        Schema::dropIfExists('tl_messages_chat_full_chat_full__chats');
        Schema::dropIfExists('tl_messages_chat_full_chat_full');
    }
};
