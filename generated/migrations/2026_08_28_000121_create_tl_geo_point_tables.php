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
        Schema::create('tl_geo_point_geo_point', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->double('tl_long')->nullable();
            $table->double('lat')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->integer('accuracy_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_81318b404b418b0b5bce8823');
            $table->index('account_id', 'ix_1957ef36d0a43e7a6eb1e658');
        });
        Schema::create('tl_geo_point_geo_point_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d4039f58184c587f1c4c6b44');
            $table->index('account_id', 'ix_2a3ebeb6a0f15318dc14482f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_geo_point_geo_point_empty');
        Schema::dropIfExists('tl_geo_point_geo_point');
    }
};
