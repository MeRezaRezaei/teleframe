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
        Schema::create('tl_bots_preview_info_preview_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_85b057eb4d08663bba58eeef');
            $table->index('account_id', 'ix_3b992ca46d03f3a997cf8e13');
        });
        Schema::create('tl_bots_preview_info_preview_info__media', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bots_preview_info_preview_info', 'id', 'fk_38288a7a3e143b1544223075')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_a4cf8719098b062d656f');
            $table->index('account_id', 'ix_9f5e298bb8c51e09a6ee77c8');
        });
        Schema::create('tl_bots_preview_info_preview_info__lang_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_bots_preview_info_preview_info', 'id', 'fk_07019b2a4c9df13f36104060')->cascadeOnDelete();
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
    }
};
