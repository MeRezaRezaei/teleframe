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
        Schema::create('tl_channel_location', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0ed06fc90a5631baed7325b5');
            $table->index('account_id', 'ix_e29a8c0883fc666f13f6b3db');
        });
        Schema::create('tl_channel_location_channel_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_location')->cascadeOnDelete();
            $table->uuid('geo_point');
            $table->index('geo_point', 'ix_55af9b548ca5626bfabaa7f1');
            $table->text('address');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a1d69b7acea3b001aaec5293');
        });
        Schema::create('tl_channel_location_channel_location_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_channel_location')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_fe4c39f0ff89f63b50417da3');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_channel_location_channel_location_empty');
        Schema::dropIfExists('tl_channel_location_channel_location');
        Schema::dropIfExists('tl_channel_location');
    }
};
