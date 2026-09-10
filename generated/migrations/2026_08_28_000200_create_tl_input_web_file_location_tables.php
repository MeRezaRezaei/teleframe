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
        Schema::create('tl_input_web_file_location_input_web_file_aud_36d962fc9d91', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('small')->default(false);
            $table->bigInteger('document')->nullable();
            $table->index('document', 'ix_4fa1589b7138c1b2c2846141');
            $table->text('title')->nullable();
            $table->text('performer')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_53a3cc6f1f09ca0ebb6c5add');
            $table->index('account_id', 'ix_1dbc0e03a651183e5d241472');
        });
        Schema::create('tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_1287e9b205b138ae6b5edc0b');
            $table->bigInteger('access_hash')->nullable();
            $table->integer('w')->nullable();
            $table->integer('h')->nullable();
            $table->integer('zoom')->nullable();
            $table->integer('scale')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0db55789353a341f8a086478');
            $table->index('account_id', 'ix_90ac9ec71599a7c61c4da75b');
        });
        Schema::create('tl_input_web_file_location_input_web_file_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cdbd2d8210ac30bc6586ec51');
            $table->index('account_id', 'ix_c6dc077e4754f223000c94cc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_location');
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_geo_aad57bf4e8d0');
        Schema::dropIfExists('tl_input_web_file_location_input_web_file_aud_36d962fc9d91');
    }
};
