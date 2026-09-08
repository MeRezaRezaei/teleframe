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
        Schema::create('tl_geo_point', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_geo_point_geo_point', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_geo_point')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->double('tl_long');
            $table->double('lat');
            $table->bigInteger('access_hash');
            $table->integer('accuracy_radius')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_geo_point_geo_point_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_geo_point')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_geo_point_geo_point_empty');
        Schema::dropIfExists('tl_geo_point_geo_point');
        Schema::dropIfExists('tl_geo_point');
    }
};
