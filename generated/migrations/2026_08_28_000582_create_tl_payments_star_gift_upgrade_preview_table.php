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
        Schema::create('tl_payments_star_gift_upgrade_preview', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_star_gift_upgrade_preview')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_d382bb48e929', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ac1776527465ac7a21c1');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_7020ccaa5d71', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_29e63869420f5239412c');
        });
        Schema::create('tl_payments_star_gift_upgrade_preview_star_gi_668a785fa9b7', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_7f75b8c92551f2c7718d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_668a785fa9b7');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_7020ccaa5d71');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_d382bb48e929');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview_star_gi_2469e890a24d');
        Schema::dropIfExists('tl_payments_star_gift_upgrade_preview');
    }
};
