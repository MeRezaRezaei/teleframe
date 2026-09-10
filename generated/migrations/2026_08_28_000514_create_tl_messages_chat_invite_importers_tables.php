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
        Schema::create('tl_messages_chat_invite_importers_chat_invite_importers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d28887358d931f4618e486b3');
            $table->index('account_id', 'ix_692e470aa9ac76c46e421e85');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_8f980112eace', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c435721ad8dbdaa64451dd94')->references('id')->on('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a249185e1b5d5840e76b');
            $table->index('account_id', 'ix_2d3097abc7a22a3563b0ea98');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_6dee4bd5cbd2e0566429098b')->references('id')->on('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_990880919889f734645d');
            $table->index('account_id', 'ix_97c822ffda710ad9e601c481');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b');
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_8f980112eace');
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_importers');
    }
};
