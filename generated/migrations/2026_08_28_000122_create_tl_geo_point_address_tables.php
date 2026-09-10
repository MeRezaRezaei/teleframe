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
        Schema::create('tl_geo_point_address_geo_point_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('country_iso2')->nullable();
            $table->text('state')->nullable();
            $table->text('city')->nullable();
            $table->text('street')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b701b08077002a454282b719');
            $table->index('account_id', 'ix_483447a4ed62f508fa69d888');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_geo_point_address_geo_point_address');
    }
};
