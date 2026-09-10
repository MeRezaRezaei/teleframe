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
        Schema::create('tl_theme_settings_theme_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('message_colors_animated')->default(false);
            $table->bigInteger('base_theme')->nullable();
            $table->index('base_theme', 'ix_c92f479e1bebf57225385a6e');
            $table->integer('accent_color')->nullable();
            $table->integer('outbox_accent_color')->nullable();
            $table->bigInteger('wallpaper')->nullable();
            $table->index('wallpaper', 'ix_905ce443de062f2ee96c72fa');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6b27a2fd78658aeac0902825');
            $table->index('account_id', 'ix_371dd6d32c145c32e608330a');
        });
        Schema::create('tl_theme_settings_theme_settings__message_colors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_8658576137d04605c559e570')->references('id')->on('tl_theme_settings_theme_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_058d4e4a29bfdd216cb8');
            $table->index('account_id', 'ix_b37e7bb3ec27403a77f73f44');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_theme_settings_theme_settings__message_colors');
        Schema::dropIfExists('tl_theme_settings_theme_settings');
    }
};
