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
        Schema::create('tl_account_business_chat_links_business_chat_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0b936ebcb466283671d9eedb');
            $table->index('account_id', 'ix_324820dc9afb557fe2ec2723');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_b836c1ef0e16edb131a4511e')->references('id')->on('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cd386f6ae9e839227e54');
            $table->index('account_id', 'ix_c7a0de4f22c44a1a6aba2f22');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f18f0600ceb91bc960518e04')->references('id')->on('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e458e815ba3a148ffc09');
            $table->index('account_id', 'ix_a419451609768502aa1ad3ad');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_e1abbd0ee3d8d1c47ef95545')->references('id')->on('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_b09674472dffcc1396bc');
            $table->index('account_id', 'ix_69ff507f586c2251ac435641');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_business_chat_links_business_chat_links__users');
        Schema::dropIfExists('tl_account_business_chat_links_business_chat_links__chats');
        Schema::dropIfExists('tl_account_business_chat_links_business_chat_links__links');
        Schema::dropIfExists('tl_account_business_chat_links_business_chat_links');
    }
};
