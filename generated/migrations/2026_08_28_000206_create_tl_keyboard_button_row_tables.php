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
        Schema::create('tl_keyboard_button_row_keyboard_button_row', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_128d8b9b7b0e16726322d318');
            $table->index('account_id', 'ix_aa27fe4e28be63e17a83d28b');
        });
        Schema::create('tl_keyboard_button_row_keyboard_button_row__buttons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_keyboard_button_row_keyboard_button_row', 'id', 'fk_dbb90e21c1998380f65dcb51')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_de8c84a2e4dd20ee6a2c');
            $table->index('account_id', 'ix_353df1eef74c174b89d50d99');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_keyboard_button_row_keyboard_button_row__buttons');
        Schema::dropIfExists('tl_keyboard_button_row_keyboard_button_row');
    }
};
