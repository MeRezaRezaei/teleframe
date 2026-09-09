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
            $table->index('constructor_id', 'ix_1d443eff15a8bf6dc2b25f8b');
            $table->index('account_id', 'ix_2654b3ffea9872a7e273af27');
        });
        Schema::create('tl_base_theme_base_theme_arctic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a014eaea5e9dec009a7977f9');
        });
        Schema::create('tl_base_theme_base_theme_classic', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b6d0e46c0ba2ea981b5352b2');
        });
        Schema::create('tl_base_theme_base_theme_day', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_37a09689e63b74f6b53fbe38');
        });
        Schema::create('tl_base_theme_base_theme_night', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a17ec9aa1d0cbb53e3a9c0b5');
        });
        Schema::create('tl_base_theme_base_theme_tinted', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_base_theme')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_64838f72237ba8bfc1767345');
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
