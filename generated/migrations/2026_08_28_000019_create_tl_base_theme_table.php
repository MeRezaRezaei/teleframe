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
        Schema::create('tl_base_theme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_base_theme_base_theme_arctic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_base_theme_base_theme_classic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_base_theme_base_theme_day', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_base_theme_base_theme_night', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_base_theme_base_theme_tinted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_base_theme_base_theme_tinted');
        Schema::dropIfExists('tl_base_theme_base_theme_night');
        Schema::dropIfExists('tl_base_theme_base_theme_day');
        Schema::dropIfExists('tl_base_theme_base_theme_classic');
        Schema::dropIfExists('tl_base_theme_base_theme_arctic');
        Schema::dropIfExists('tl_base_theme');
    }
};
