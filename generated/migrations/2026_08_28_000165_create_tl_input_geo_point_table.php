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
        Schema::create('tl_input_geo_point', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_523c539db857b80bd63b8f41');
            $table->index('account_id', 'ix_cdf24f06cbaa33be42ca120f');
        });
        Schema::create('tl_input_geo_point_input_geo_point', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_geo_point')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->double('lat');
            $table->double('tl_long');
            $table->integer('accuracy_radius')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c4e0bf686ba42c8122f20a4d');
        });
        Schema::create('tl_input_geo_point_input_geo_point_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_geo_point')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6eb505f46c2afef3c521c618');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_geo_point_input_geo_point_empty');
        Schema::dropIfExists('tl_input_geo_point_input_geo_point');
        Schema::dropIfExists('tl_input_geo_point');
    }
};
