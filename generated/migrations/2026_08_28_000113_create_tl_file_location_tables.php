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
        Schema::create('tl_file_location_file_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->bigInteger('volume_id')->nullable();
            $table->index('volume_id', 'ix_6c2080e3dd0f4dbe151ced47');
            $table->integer('local_id')->nullable();
            $table->bigInteger('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5340f94900e85e321d8830a3');
            $table->index('account_id', 'ix_b429280121643609ddf92447');
        });
        Schema::create('tl_file_location_file_location_unavailable', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('volume_id')->nullable();
            $table->index('volume_id', 'ix_50ebf87962fe7c3d1f1bc3a6');
            $table->integer('local_id')->nullable();
            $table->bigInteger('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_33f06e49bd55158b22d6dabd');
            $table->index('account_id', 'ix_a0a0fc8bef660f3bf7de0794');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_file_location_file_location_unavailable');
        Schema::dropIfExists('tl_file_location_file_location');
    }
};
