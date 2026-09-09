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
            $table->index('constructor_id', 'ix_e57bb0be6233fa142d9d41e9');
            $table->index('account_id', 'ix_041d74e634376fee5b755212');
        });
        Schema::create('tl_messages_message_views_message_views', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_message_views')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_25c29ec62502156b1e263af9');
        });
        Schema::create('tl_messages_message_views_message_views__views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_822c071601b991ea8a9d');
            $table->index('account_id', 'ix_f92a1c370ad7727d63bff771');
        });
        Schema::create('tl_messages_message_views_message_views__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_7a95a580ae27b424ee4e');
            $table->index('account_id', 'ix_c70def87502390babcf1893f');
        });
        Schema::create('tl_messages_message_views_message_views__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_message_views_message_views')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_messages_message_views');
    }
};
