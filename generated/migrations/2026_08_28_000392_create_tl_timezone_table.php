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
        Schema::create('tl_timezone', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_281d267250a54f2ad36eaa08');
            $table->index('account_id', 'ix_1cb0d14b646f4c8d968d687f');
        });
        Schema::create('tl_timezone_timezone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_timezone')->cascadeOnDelete();
            $table->text('tl_id');
            $table->text('name');
            $table->integer('utc_offset');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1a007e09df53dc02a2e740c8');
            $table->unique(['account_id', 'tl_id'], 'ux_8eba4f7f938899e92f04');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_timezone_timezone');
        Schema::dropIfExists('tl_timezone');
    }
};
