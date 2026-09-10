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
        Schema::create('tl_timezone_timezone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('name')->nullable();
            $table->integer('utc_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c8c8b6f8d10d5b0f9b2eeee');
            $table->index('account_id', 'ix_1a007e09df53dc02a2e740c8');
            $table->unique(['account_id'], 'ux_8eba4f7f938899e92f04');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_timezone_timezone');
    }
};
