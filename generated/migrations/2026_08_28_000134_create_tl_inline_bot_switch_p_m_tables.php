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
        Schema::create('tl_inline_bot_switch_p_m_inline_bot_switch_p_m', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('text')->nullable();
            $table->text('start_param')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6a86832741ede6c6b48d15d3');
            $table->index('account_id', 'ix_fdf019fbcc40b712f0285ace');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_inline_bot_switch_p_m_inline_bot_switch_p_m');
    }
};
