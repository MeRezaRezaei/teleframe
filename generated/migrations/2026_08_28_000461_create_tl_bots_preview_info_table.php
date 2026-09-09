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
            $table->index('constructor_id', 'ix_fa1bb006aadeca41edafc81a');
            $table->index('account_id', 'ix_0e554b59e8fd9ef89b102e5d');
        });
        Schema::create('tl_bots_preview_info_preview_info', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bots_preview_info')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_3b992ca46d03f3a997cf8e13');
        });
        Schema::create('tl_bots_preview_info_preview_info__media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_preview_info_preview_info')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a4cf8719098b062d656f');
            $table->index('account_id', 'ix_9f5e298bb8c51e09a6ee77c8');
        });
        Schema::create('tl_bots_preview_info_preview_info__lang_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_bots_preview_info_preview_info')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->text('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_16494497c4d6ff234ba7');
            $table->index('account_id', 'ix_94ec7099cd8826a3cb321001');
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
