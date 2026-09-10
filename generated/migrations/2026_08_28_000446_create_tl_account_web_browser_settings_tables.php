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
        Schema::create('tl_account_web_browser_settings_web_browser_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('open_external_browser')->default(false);
            $table->boolean('display_close_button')->default(false);
            $table->bigInteger('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5792f6acdb5bd071faa8ec5a');
            $table->index('account_id', 'ix_252286cecf9335fad6cdc50f');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_3c50a1f6bf77', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_084183c231572f4e75353b48')->references('id')->on('tl_account_web_browser_settings_web_browser_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_79f7b5a5b8ed0d501bc9');
            $table->index('account_id', 'ix_c86bed2e71b138ad3c6eec67');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_4c8f4a163493', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_96a6a339ce2d41930dadbd55')->references('id')->on('tl_account_web_browser_settings_web_browser_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e8f56413fadd8b99682c');
            $table->index('account_id', 'ix_7c4410050d74916d4009f2b8');
        });
        Schema::create('tl_account_web_browser_settings_web_browser_s_2e6cc2129fbc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8b3351ae3e12c2ed75e299f7');
            $table->index('account_id', 'ix_bb30af3c029b7fca14d7f507');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_2e6cc2129fbc');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_4c8f4a163493');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_s_3c50a1f6bf77');
        Schema::dropIfExists('tl_account_web_browser_settings_web_browser_settings');
    }
};
