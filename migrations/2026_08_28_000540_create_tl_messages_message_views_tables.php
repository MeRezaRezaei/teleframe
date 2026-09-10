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
        Schema::create('tl_messages_message_views_message_views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1fc3dfed91cdd159d7539e1d');
            $table->index('account_id', 'ix_25c29ec62502156b1e263af9');
        });
        Schema::create('tl_messages_message_views_message_views__views', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_608db0d53e3795cea4bc14f1')->references('id')->on('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_822c071601b991ea8a9d');
            $table->index('account_id', 'ix_f92a1c370ad7727d63bff771');
        });
        Schema::create('tl_messages_message_views_message_views__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_ecb3d0c7c0914833e5fed1c4')->references('id')->on('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7a95a580ae27b424ee4e');
            $table->index('account_id', 'ix_c70def87502390babcf1893f');
        });
        Schema::create('tl_messages_message_views_message_views__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c15b9ada3ccb0d9e39b2bbef')->references('id')->on('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_2ddd8f8985c1606cac50');
            $table->index('account_id', 'ix_6ef4a313ee64a0c88f420321');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_views_message_views__users');
        Schema::dropIfExists('tl_messages_message_views_message_views__chats');
        Schema::dropIfExists('tl_messages_message_views_message_views__views');
        Schema::dropIfExists('tl_messages_message_views_message_views');
    }
};
