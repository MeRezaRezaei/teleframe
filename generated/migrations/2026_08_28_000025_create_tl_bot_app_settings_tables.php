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
        Schema::create('tl_bot_app_settings_bot_app_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->binary('placeholder_path')->nullable();
            $table->integer('background_color')->nullable();
            $table->integer('background_dark_color')->nullable();
            $table->integer('header_color')->nullable();
            $table->integer('header_dark_color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a2502a85a7a588efc6497c10');
            $table->index('account_id', 'ix_9b1a1567d3793f186f434f1f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_app_settings_bot_app_settings');
    }
};
