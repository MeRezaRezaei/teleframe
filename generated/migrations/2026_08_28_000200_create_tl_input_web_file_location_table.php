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
        Schema::create('tl_input_web_file_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_web_file_location_input_web_file_aud_36d962fc9d91', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('small')->default(false);
            $table->uuid('document')->nullable();
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->bigInteger('access_hash');
            $table->integer('w');
            $table->integer('h');
            $table->integer('zoom');
            $table->integer('scale');
            $table->timestamps();
        });
        Schema::create('tl_input_web_file_location_input_web_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_location');
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0');
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_aud_36d962fc9d91');
        Schema::dropIfExists('tl_input_web_file_location');
    }
};
