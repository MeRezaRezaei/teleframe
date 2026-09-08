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
        Schema::create('tl_bots_preview_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bots_preview_info_preview_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_preview_info')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_bots_preview_info_preview_info__media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_preview_info_preview_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_a4cf8719098b062d656f');
        });
        Schema::create('tl_bots_preview_info_preview_info__lang_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_preview_info_preview_info')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_16494497c4d6ff234ba7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bots_preview_info_preview_info__lang_codes');
        Schema::dropIfExists('tl_bots_preview_info_preview_info__media');
        Schema::dropIfExists('tl_bots_preview_info_preview_info');
        Schema::dropIfExists('tl_bots_preview_info');
    }
};
