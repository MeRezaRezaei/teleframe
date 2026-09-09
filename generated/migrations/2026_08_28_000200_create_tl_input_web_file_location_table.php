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
            $table->index('constructor_id', 'ix_d71905b274ffd1d6c3a3de29');
            $table->index('account_id', 'ix_b093c42e2ab645ddb797b744');
        });
        Schema::create('tl_input_web_file_location_input_web_file_aud_36d962fc9d91', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('small')->default(false);
            $table->uuid('document')->nullable();
            $table->index('document', 'ix_4fa1589b7138c1b2c2846141');
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1dbc0e03a651183e5d241472');
        });
        Schema::create('tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_1287e9b205b138ae6b5edc0b');
            $table->bigInteger('access_hash');
            $table->integer('w');
            $table->integer('h');
            $table->integer('zoom');
            $table->integer('scale');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_90ac9ec71599a7c61c4da75b');
        });
        Schema::create('tl_input_web_file_location_input_web_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_web_file_location')->cascadeOnDelete();
            $table->text('url');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c6dc077e4754f223000c94cc');
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
