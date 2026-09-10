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
        Schema::create('tl_bot_menu_button_bot_menu_button', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b5e7de89422c25a77bb38c14');
            $table->index('account_id', 'ix_d3bb5a0f383b41f64b1e2d31');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_commands', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b5a795ef013c4cab1dde6d06');
            $table->index('account_id', 'ix_c92f4c75d65fff42f996812d');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_default', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_eb71f1480197da1ca001ad9e');
            $table->index('account_id', 'ix_c6449bcf538e8d37438e50a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button_default');
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button_commands');
        Schema::dropIfExists('tl_bot_menu_button_bot_menu_button');
    }
};
