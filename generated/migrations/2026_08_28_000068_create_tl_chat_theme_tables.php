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
        Schema::create('tl_chat_theme_chat_theme', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('emoticon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_49264bad15062a7782b6071c');
            $table->index('account_id', 'ix_7a7e046277f3f889835afec2');
        });
        Schema::create('tl_chat_theme_chat_theme_unique_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('gift')->nullable();
            $table->index('gift', 'ix_a8fef6cf9658d925f40596f6');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e4b0239f09594928d27b478f');
            $table->index('account_id', 'ix_1ec3e04cba3cefcf8cc3d356');
        });
        Schema::create('tl_chat_theme_chat_theme_unique_gift__theme_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_chat_theme_chat_theme_unique_gift', 'id', 'fk_1fc361b1c3c159b8f87e01dd')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e2687527348a42aaa8fe');
            $table->index('account_id', 'ix_00ddb328c0b43f92d650bd4e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_theme_chat_theme_unique_gift__theme_settings');
        Schema::dropIfExists('tl_chat_theme_chat_theme_unique_gift');
        Schema::dropIfExists('tl_chat_theme_chat_theme');
    }
};
