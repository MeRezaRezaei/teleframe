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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_bot_menu_button_bot_menu_button', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->text('text');
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_commands', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_bot_menu_button_bot_menu_button_default', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_bot_menu_button')->cascadeOnDelete();
            $table->timestamps();
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
