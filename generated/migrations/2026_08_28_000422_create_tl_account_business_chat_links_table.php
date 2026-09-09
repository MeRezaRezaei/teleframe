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
        Schema::create('tl_account_business_chat_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_e6844a8db1d764dc6960758e');
            $table->index('account_id', 'ix_084bfd26010cea043ec8c36f');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_324820dc9afb557fe2ec2723');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_cd386f6ae9e839227e54');
            $table->index('account_id', 'ix_c7a0de4f22c44a1a6aba2f22');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e458e815ba3a148ffc09');
            $table->index('account_id', 'ix_a419451609768502aa1ad3ad');
        });
        Schema::create('tl_account_business_chat_links_business_chat_links__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_business_chat_links_business_chat_links')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_account_business_chat_links');
    }
};
