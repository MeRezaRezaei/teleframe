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
        Schema::create('tl_file_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_file_location_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_file_location')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('volume_id');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->timestamps();
        });
        Schema::create('tl_file_location_file_location_unavailable', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_file_location')->cascadeOnDelete();
            $table->bigInteger('volume_id');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_file_location_file_location_unavailable');
        Schema::dropIfExists('tl_file_location_file_location');
        Schema::dropIfExists('tl_file_location');
    }
};
