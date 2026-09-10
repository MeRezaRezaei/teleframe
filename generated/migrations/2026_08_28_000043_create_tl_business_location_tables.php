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
        Schema::create('tl_business_location_business_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_ffece6d459d9f3f4aa60df57');
            $table->text('address')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1ca8f3c8ec087628a44bd20f');
            $table->index('account_id', 'ix_8a8ce01557c1344517fb5a9f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_location_business_location');
    }
};
