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
        Schema::create('tl_code_settings_code_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('allow_flashcall')->default(false);
            $table->boolean('current_number')->default(false);
            $table->boolean('allow_app_hash')->default(false);
            $table->boolean('allow_missed_call')->default(false);
            $table->boolean('allow_firebase')->default(false);
            $table->boolean('unknown_number')->default(false);
            $table->text('token')->nullable();
            $table->bigInteger('app_sandbox')->nullable();
            $table->index('app_sandbox', 'ix_68cef437aedd626e63b9d8e6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f6011d42b72bf132f7710828');
            $table->index('account_id', 'ix_4abb089725050df0e09e5700');
        });
        Schema::create('tl_code_settings_code_settings__logout_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_f1741d86304f08305118346d')->references('id')->on('tl_code_settings_code_settings')->cascadeOnDelete();
            $table->bigInteger('idx');
        $table->binary('value')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6785f792d3736f2db51e');
            $table->index('account_id', 'ix_1da758bac1bef848aab935cb');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_code_settings_code_settings__logout_tokens');
        Schema::dropIfExists('tl_code_settings_code_settings');
    }
};
