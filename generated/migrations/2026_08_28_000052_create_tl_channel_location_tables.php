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
        Schema::create('tl_channel_location_channel_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('geo_point')->nullable();
            $table->index('geo_point', 'ix_55af9b548ca5626bfabaa7f1');
            $table->text('address')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a7cc61ccc839f201a18685ec');
            $table->index('account_id', 'ix_a1d69b7acea3b001aaec5293');
        });
        Schema::create('tl_channel_location_channel_location_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e3edb6da7ebc3ee4fcebe0e0');
            $table->index('account_id', 'ix_fe4c39f0ff89f63b50417da3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_location_channel_location_empty');
        Schema::dropIfExists('tl_channel_location_channel_location');
    }
};
