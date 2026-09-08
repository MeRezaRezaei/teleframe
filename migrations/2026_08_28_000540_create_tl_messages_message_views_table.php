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
        Schema::create('tl_messages_message_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_message_views_message_views', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_message_views')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_message_views_message_views__views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_822c071601b991ea8a9d');
        });
        Schema::create('tl_messages_message_views_message_views__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7a95a580ae27b424ee4e');
        });
        Schema::create('tl_messages_message_views_message_views__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_2ddd8f8985c1606cac50');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_message_views_message_views__users');
        Schema::dropIfExists('tl_messages_message_views_message_views__chats');
        Schema::dropIfExists('tl_messages_message_views_message_views__views');
        Schema::dropIfExists('tl_messages_message_views_message_views');
        Schema::dropIfExists('tl_messages_message_views');
    }
};
