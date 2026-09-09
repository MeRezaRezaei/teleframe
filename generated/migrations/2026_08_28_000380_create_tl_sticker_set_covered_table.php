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
            $table->index('constructor_id', 'ix_08e1b330fc7e78df42b6261d');
            $table->index('account_id', 'ix_60074443b128d27698315f45');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->index('set', 'ix_1fc529bc28d7d36d86a28f74');
            $table->uuid('cover');
            $table->index('cover', 'ix_be322d1d0d19b2d8c2ddcc7f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_475e168907561717ebbe1673');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->index('set', 'ix_e59883a0e22c61d04e37e7ca');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_422a87747bacb4199be9d700');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__packs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_53a39a8d4b393b7fe6db');
            $table->index('account_id', 'ix_e96ce9a3bd57e3c0b2714d67');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__keywords', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3090260e7f9319eb56ca');
            $table->index('account_id', 'ix_de47de9f90b65f1386704a62');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_full_covered__documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_full_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_660cbe4c5d23a45da80d');
            $table->index('account_id', 'ix_768c91dd04977d004216aaec');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_multi_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->index('set', 'ix_062bca5f157e1f9515bf8a32');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bcd92faea11c44194a5568de');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_multi_covered__covers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_sticker_set_covered_sticker_set_multi_covered')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_caa75b30a551f12ed308');
            $table->index('account_id', 'ix_79e4fd896204f40cecbecac5');
        });
        Schema::create('tl_sticker_set_covered_sticker_set_no_covered', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sticker_set_covered')->cascadeOnDelete();
            $table->uuid('set');
            $table->index('set', 'ix_1219a1f5b452f3febf61b54a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b8653f17e8775fb9e4b6488f');
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
