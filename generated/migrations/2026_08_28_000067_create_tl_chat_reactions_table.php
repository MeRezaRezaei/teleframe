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
        Schema::create('tl_chat_reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_chat_reactions_chat_reactions_all', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_reactions')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('allow_custom')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_chat_reactions_chat_reactions_none', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_reactions')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_chat_reactions_chat_reactions_some', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_chat_reactions')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_chat_reactions_chat_reactions_some__reactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_chat_reactions_chat_reactions_some')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_d58f1a14f24e7b5ac901');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_some__reactions');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_some');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_none');
        Schema::dropIfExists('tl_chat_reactions_chat_reactions_all');
        Schema::dropIfExists('tl_chat_reactions');
    }
};
