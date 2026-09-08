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
        Schema::create('tl_sticker_set_covered', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->uuid('cover');
            $table->timestamps();
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->timestamps();
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__packs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_53a39a8d4b393b7fe6db');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3090260e7f9319eb56ca');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_660cbe4c5d23a45da80d');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_multi_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->timestamps();
        });
        Schema::create('tl_sticker_set_covered_sticker_set_multi_covered__covers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_multi_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_caa75b30a551f12ed308');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_no_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_no_covered');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_multi_covered__covers');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_multi_covered');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_full_covered__documents');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_full_covered__keywords');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_full_covered__packs');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_full_covered');
        Schema::dropIfExists('tl_sticker_set_covered_sticker_set_covered');
        Schema::dropIfExists('tl_sticker_set_covered');
    }
};
