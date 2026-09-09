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
        Schema::create('tl_keyboard_button_style', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8a8d047ccc53cb5b9c142fe7');
            $table->index('account_id', 'ix_240ddf805c52b6974ba7fe3f');
        });
        Schema::create('tl_keyboard_button_style_keyboard_button_style', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button_style')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('bg_primary')->default(false);
            $table->boolean('bg_danger')->default(false);
            $table->boolean('bg_success')->default(false);
            $table->bigInteger('icon')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b65db7425695154181e20b26');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_style_keyboard_button_style');
        Schema::dropIfExists('tl_keyboard_button_style');
    }
};
