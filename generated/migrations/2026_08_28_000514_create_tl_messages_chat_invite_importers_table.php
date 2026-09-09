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
            $table->index('constructor_id', 'ix_c2b87bf0d77bee947f236f45');
            $table->index('account_id', 'ix_0a349ce728fd5ef6e8fc7b7d');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_importers', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_chat_invite_importers')->cascadeOnDelete();
            $table->integer('count');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_692e470aa9ac76c46e421e85');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_8f980112eace', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a249185e1b5d5840e76b');
            $table->index('account_id', 'ix_2d3097abc7a22a3563b0ea98');
        });
        Schema::create('tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_chat_invite_importers_chat_invite_importers')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_chat_invite_importers');
    }
};
