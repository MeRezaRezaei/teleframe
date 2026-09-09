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
        Schema::create('tl_bot_app_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_191633dadb78119445c014c8');
            $table->index('account_id', 'ix_c8d2bbf9de715917061ac58b');
        });
        Schema::create('tl_bot_app_settings_bot_app_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_app_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->binary('placeholder_path')->nullable();
            $table->integer('background_color')->nullable();
            $table->integer('background_dark_color')->nullable();
            $table->integer('header_color')->nullable();
            $table->integer('header_dark_color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9b1a1567d3793f186f434f1f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_app_settings_bot_app_settings');
        Schema::dropIfExists('tl_bot_app_settings');
    }
};
