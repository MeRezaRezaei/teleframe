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
        Schema::create('tl_messages_found_sticker_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b9a1a9ad432a63a791ea0182');
            $table->index('account_id', 'ix_e7725c4235b0ba06ab369d43');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0a6ed8b13f2b70b8549834ca');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_found_sticker_sets_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ba9657045b25c5544ea3');
            $table->index('account_id', 'ix_6bb7fca15e090bc9b9096685');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c62d60f367866cc1469c387b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6');
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker_sets__sets');
        Schema::dropIfExists('tl_messages_found_sticker_sets_found_sticker_sets');
        Schema::dropIfExists('tl_messages_found_sticker_sets');
    }
};
