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
        Schema::create('tl_keyboard_button_row', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5e1a7247db70868d0f7f51c');
            $table->index('account_id', 'ix_099d7572472df74e1de102a1');
        });
        Schema::create('tl_keyboard_button_row_keyboard_button_row', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_keyboard_button_row')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aa27fe4e28be63e17a83d28b');
        });
        Schema::create('tl_keyboard_button_row_keyboard_button_row__buttons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_keyboard_button_row_keyboard_button_row')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_de8c84a2e4dd20ee6a2c');
            $table->index('account_id', 'ix_353df1eef74c174b89d50d99');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_row_keyboard_button_row__buttons');
        Schema::dropIfExists('tl_keyboard_button_row_keyboard_button_row');
        Schema::dropIfExists('tl_keyboard_button_row');
    }
};
