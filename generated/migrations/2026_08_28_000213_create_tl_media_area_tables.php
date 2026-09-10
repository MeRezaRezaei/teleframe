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
        Schema::create('tl_media_area_input_media_area_channel_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_38d1938153a54ac9de63e1d8');
            $table->bigInteger('channel')->nullable();
            $table->index('channel', 'ix_11bbb647c66cca98f4f61413');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aafbb43d69be4d234433563f');
            $table->index('account_id', 'ix_35eaeae4b1f8bae57e2442fd');
        });
        Schema::create('tl_media_area_input_media_area_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_44adb56188e1330afc95ae7b');
            $table->bigInteger('query_id')->nullable();
            $table->index('query_id', 'ix_45bba37d407004ee57bb6c8d');
            $table->text('result_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a80dad7075e663bc3dde0bef');
            $table->index('account_id', 'ix_c1a7837cd64002772ad6a7fc');
        });
        Schema::create('tl_media_area_media_area_channel_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_3be126cd20307558142ac3b6');
            $table->bigInteger('channel_id')->nullable();
            $table->index('channel_id', 'ix_30449886829e7a645511f6f9');
            $table->integer('msg_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1d01eb5f8f9906eddb27d71d');
            $table->index('account_id', 'ix_b26ee6f2f41fd0ee0dc8a343');
        });
        Schema::create('tl_media_area_media_area_geo_point', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_a97b01364730add144cc04f7');
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_24dbcb5f2f70bcfe3ac3f013');
            $table->bigInteger('address')->nullable();
            $table->index('address', 'ix_8df4abfad5aa9aac7a3a3623');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3c481a14df282d11372160c6');
            $table->index('account_id', 'ix_4feff07f6c4c4614fabb4045');
        });
        Schema::create('tl_media_area_media_area_star_gift', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_13829c1168124b80120b3854');
            $table->text('slug')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e616b2020bb1a4bd4e2b2060');
            $table->index('account_id', 'ix_5bb9313ec35b6f3aae394429');
        });
        Schema::create('tl_media_area_media_area_suggested_reaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('dark')->default(false);
            $table->boolean('flipped')->default(false);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_07f7037a2917fb7bc2aa7095');
            $table->bigInteger('reaction')->nullable();
            $table->index('reaction', 'ix_077daf3a4681c1e8e34e2ec2');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c2ca340b746333cb2a21037f');
            $table->index('account_id', 'ix_2ba20b5208b9447905a5d705');
        });
        Schema::create('tl_media_area_media_area_url', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_b1f3526362a9f1961a54a8bd');
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9e7e57aaf69918f4ef413223');
            $table->index('account_id', 'ix_904246e678a938d01923326d');
        });
        Schema::create('tl_media_area_media_area_venue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_d206c3e8d4d25eccc4337018');
            $table->bigInteger('geo')->nullable();
            $table->index('geo', 'ix_8bedbc3fcaca3f71ad600326');
            $table->text('title')->nullable();
            $table->text('address')->nullable();
            $table->text('provider')->nullable();
            $table->text('venue_id')->nullable();
            $table->text('venue_type')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0faefc9b26a302f614f17c04');
            $table->index('account_id', 'ix_b79e402342da2bc54803a13e');
        });
        Schema::create('tl_media_area_media_area_weather', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('coordinates')->nullable();
            $table->index('coordinates', 'ix_ea990744d228cd5d038fa6d3');
            $table->text('emoji')->nullable();
            $table->double('temperature_c')->nullable();
            $table->integer('color')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_385c3fd828c5fbb72d980514');
            $table->index('account_id', 'ix_56cb9a77b2c6a8066688dcae');
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
    }
};
