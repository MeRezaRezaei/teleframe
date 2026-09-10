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
        Schema::create('tl_nearest_dc_nearest_dc', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('country')->nullable();
            $table->integer('this_dc')->nullable();
            $table->integer('nearest_dc')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7f8b6f3be7b67406a256cb60');
            $table->index('account_id', 'ix_0007c62a6c390c7671d10e40');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_nearest_dc_nearest_dc');
    }
};
