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
        Schema::create('tl_input_theme_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_theme_settings_input_theme_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_theme_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('message_colors_animated')->default(false);
            $table->uuid('base_theme');
            $table->integer('accent_color');
            $table->integer('outbox_accent_color')->nullable();
            $table->uuid('wallpaper')->nullable();
            $table->uuid('wallpaper_settings')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_theme_settings_input_theme_settings__a1d0879fb3ca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_theme_settings_input_theme_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_fb64dbcf78b54607458d');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_theme_settings_input_theme_settings__a1d0879fb3ca');
        Schema::dropIfExists('tl_input_theme_settings_input_theme_settings');
        Schema::dropIfExists('tl_input_theme_settings');
    }
};
