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
        Schema::create('tl_input_geo_point_input_geo_point', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->double('lat')->nullable();
            $table->double('tl_long')->nullable();
            $table->integer('accuracy_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_08253d8ebeccc2c5afdf1842');
            $table->index('account_id', 'ix_c4e0bf686ba42c8122f20a4d');
        });
        Schema::create('tl_input_geo_point_input_geo_point_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_19c39cb7568e807eab5b6641');
            $table->index('account_id', 'ix_6eb505f46c2afef3c521c618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_geo_point_input_geo_point_empty');
        Schema::dropIfExists('tl_input_geo_point_input_geo_point');
    }
};
