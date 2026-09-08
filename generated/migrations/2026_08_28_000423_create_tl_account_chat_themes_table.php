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
        Schema::create('tl_account_chat_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_chat_themes_chat_themes', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_chat_themes')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('hash');
            $table->text('next_offset')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_account_chat_themes_chat_themes__themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_db95a5e52d2f4cfa98d1');
        });
        Schema::create('tl_account_chat_themes_chat_themes__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_6364c9624de1ee497135');
        });
        Schema::create('tl_account_chat_themes_chat_themes__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_chat_themes_chat_themes')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_600632584b25becef455');
        });
        Schema::create('tl_account_chat_themes_chat_themes_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_chat_themes')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_chat_themes_chat_themes_not_modified');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__users');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__chats');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes__themes');
        Schema::dropIfExists('tl_account_chat_themes_chat_themes');
        Schema::dropIfExists('tl_account_chat_themes');
    }
};
