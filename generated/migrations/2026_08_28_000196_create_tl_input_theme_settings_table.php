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
            $table->index('constructor_id', 'ix_8c6cee690d88b4e6b91876e5');
            $table->index('account_id', 'ix_3957bef1f05dc0d031b92e1c');
        });
        Schema::create('tl_input_theme_settings_input_theme_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_theme_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('message_colors_animated')->default(false);
            $table->uuid('base_theme');
            $table->index('base_theme', 'ix_5c0994d7dacdaf568ebe4862');
            $table->integer('accent_color');
            $table->integer('outbox_accent_color')->nullable();
            $table->uuid('wallpaper')->nullable();
            $table->index('wallpaper', 'ix_89301cfa0e1944adb90a1bdf');
            $table->uuid('wallpaper_settings')->nullable();
            $table->index('wallpaper_settings', 'ix_3b0c28194a51e0834e5fa21e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_bb206102b97c5c55ba96ed60');
        });
        Schema::create('tl_input_theme_settings_input_theme_settings__a1d0879fb3ca', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_input_theme_settings_input_theme_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->integer('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_fb64dbcf78b54607458d');
            $table->index('account_id', 'ix_fac82e578fc12cd0c308a703');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_theme_settings_input_theme_settings__a1d0879fb3ca');
        Schema::dropIfExists('tl_input_theme_settings_input_theme_settings');
        Schema::dropIfExists('tl_input_theme_settings');
    }
};
