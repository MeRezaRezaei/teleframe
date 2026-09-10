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
        Schema::create('tl_media_area_coordinates_media_area_coordinates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->double('x')->nullable();
            $table->double('y')->nullable();
            $table->double('w')->nullable();
            $table->double('h')->nullable();
            $table->double('rotation')->nullable();
            $table->double('radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b25ab57ff77463cdd52b110f');
            $table->index('account_id', 'ix_562ca00f261bfba833d0f057');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_media_area_coordinates_media_area_coordinates');
    }
};
