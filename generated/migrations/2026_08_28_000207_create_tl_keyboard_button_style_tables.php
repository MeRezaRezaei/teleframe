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
        Schema::create('tl_keyboard_button_style_keyboard_button_style', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('bg_primary')->default(false);
            $table->boolean('bg_danger')->default(false);
            $table->boolean('bg_success')->default(false);
            $table->bigInteger('icon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_82882a2fcca1c6c8693e4bb9');
            $table->index('account_id', 'ix_b65db7425695154181e20b26');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_style_keyboard_button_style');
    }
};
