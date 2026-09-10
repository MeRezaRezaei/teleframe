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
        Schema::create('tl_input_theme_settings_input_theme_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('message_colors_animated')->default(false);
            $table->bigInteger('base_theme')->nullable();
            $table->index('base_theme', 'ix_5c0994d7dacdaf568ebe4862');
            $table->integer('accent_color')->nullable();
            $table->integer('outbox_accent_color')->nullable();
            $table->bigInteger('wallpaper')->nullable();
            $table->index('wallpaper', 'ix_89301cfa0e1944adb90a1bdf');
            $table->bigInteger('wallpaper_settings')->nullable();
            $table->index('wallpaper_settings', 'ix_3b0c28194a51e0834e5fa21e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b39c87f23e5ba641667a9694');
            $table->index('account_id', 'ix_bb206102b97c5c55ba96ed60');
        });
        Schema::create('tl_input_theme_settings_input_theme_settings__a1d0879fb3ca', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c25969af6881a186f191f9cd')->references('id')->on('tl_input_theme_settings_input_theme_settings')->cascadeOnDelete();
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
    }
};
