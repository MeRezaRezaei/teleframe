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
        Schema::create('tl_media_area', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_media_area_input_media_area_channel_post', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->uuid('channel');
            $table->integer('msg_id');
            $table->timestamps();
        });
        Schema::create('tl_media_area_input_media_area_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->bigInteger('query_id');
            $table->text('result_id');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_channel_post', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->bigInteger('channel_id');
            $table->integer('msg_id');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_geo_point', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('coordinates');
            $table->uuid('geo');
            $table->uuid('address')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_star_gift', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->text('slug');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_suggested_reaction', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('dark')->default(false);
            $table->boolean('flipped')->default(false);
            $table->uuid('coordinates');
            $table->uuid('reaction');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_url', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->text('url');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_venue', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->uuid('geo');
            $table->text('title');
            $table->text('address');
            $table->text('provider');
            $table->text('venue_id');
            $table->text('venue_type');
            $table->timestamps();
        });
        Schema::create('tl_media_area_media_area_weather', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_media_area')->cascadeOnDelete();
            $table->uuid('coordinates');
            $table->text('emoji');
            $table->double('temperature_c');
            $table->integer('color');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_media_area_media_area_weather');
        Schema::dropIfExists('tl_media_area_media_area_venue');
        Schema::dropIfExists('tl_media_area_media_area_url');
        Schema::dropIfExists('tl_media_area_media_area_suggested_reaction');
        Schema::dropIfExists('tl_media_area_media_area_star_gift');
        Schema::dropIfExists('tl_media_area_media_area_geo_point');
        Schema::dropIfExists('tl_media_area_media_area_channel_post');
        Schema::dropIfExists('tl_media_area_input_media_area_venue');
        Schema::dropIfExists('tl_media_area_input_media_area_channel_post');
        Schema::dropIfExists('tl_media_area');
    }
};
