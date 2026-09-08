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
        Schema::create('tl_account_web_browser_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_web_browser_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('open_external_browser')->default(false);
            $table->boolean('display_close_button')->default(false);
            $table->bigInteger('hash');
            $table->timestamps();
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_3c50a1f6bf77', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_web_browser_settings_web_browser_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_79f7b5a5b8ed0d501bc9');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_4c8f4a163493', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_account_web_browser_settings_web_browser_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e8f56413fadd8b99682c');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_2e6cc2129fbc', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_web_browser_settings')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_2e6cc2129fbc');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_4c8f4a163493');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_3c50a1f6bf77');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_settings');
        Schema::dropIfExists('tl_account_web_browser_settings');
    }
};
