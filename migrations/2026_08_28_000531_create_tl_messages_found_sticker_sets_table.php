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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('hash');
            $table->timestamps();
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker_sets__sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_messages_found_sticker_sets_found_sticker_sets')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ba9657045b25c5544ea3');
        });
        Schema::create('tl_messages_found_sticker_sets_found_sticker__68e11d7b41b6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_messages_found_sticker_sets')->cascadeOnDelete();
            $table->timestamps();
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
