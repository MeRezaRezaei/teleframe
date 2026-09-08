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
        Schema::create('tl_messages_quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_quick_replies_quick_replies', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_quick_replies')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_messages_quick_replies_quick_replies__quick_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_443df8577df6e233ba3d');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4aacb9cbba52a31467c8');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_76a656d69ca21dffbccf');
        });
        Schema::create('tl_messages_quick_replies_quick_replies__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_quick_replies_quick_replies')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_820c0f0d047345664def');
        });
        Schema::create('tl_messages_quick_replies_quick_replies_not_modified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_quick_replies')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies_not_modified');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__users');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__chats');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__messages');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies__quick_replies');
        Schema::dropIfExists('tl_messages_quick_replies_quick_replies');
        Schema::dropIfExists('tl_messages_quick_replies');
    }
};
