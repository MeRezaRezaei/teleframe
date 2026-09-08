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
        Schema::create('tl_messages_chat_invite_importers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_importers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_invite_importers')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_8f980112eace', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a249185e1b5d5840e76b');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_990880919889f734645d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b');
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_8f980112eace');
        Schema::dropIfExists('tl_messages_chat_invite_importers_chat_invite_importers');
        Schema::dropIfExists('tl_messages_chat_invite_importers');
    }
};
