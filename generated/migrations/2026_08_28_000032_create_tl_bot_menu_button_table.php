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
        Schema::create('tl_bot_menu_button', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba4bca28ef96096f92637822');
            $table->index('account_id', 'ix_f0de498dcec9abbe88ce9066');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->text('text');
            $table->text('url');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d3bb5a0f383b41f64b1e2d31');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_commands', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c92f4c75d65fff42f996812d');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6449bcf538e8d37438e50a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button_default');
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button_commands');
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button');
        Schema::dropIfExists('tl_bot_menu_button');
    }
};
