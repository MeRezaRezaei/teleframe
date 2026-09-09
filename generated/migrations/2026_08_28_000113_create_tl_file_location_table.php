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
            $table->index('constructor_id', 'ix_1f62c3dd5615d88020f57037');
            $table->index('account_id', 'ix_c3a739994b66454c9c13d806');
        });
        Schema::create('tl_file_location_file_location', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_file_location')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->bigInteger('volume_id');
            $table->index('volume_id', 'ix_6c2080e3dd0f4dbe151ced47');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b429280121643609ddf92447');
        });
        Schema::create('tl_file_location_file_location_unavailable', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_file_location')->cascadeOnDelete();
            $table->bigInteger('volume_id');
            $table->index('volume_id', 'ix_50ebf87962fe7c3d1f1bc3a6');
            $table->integer('local_id');
            $table->bigInteger('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a0a0fc8bef660f3bf7de0794');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_file_location_file_location_unavailable');
        Schema::dropIfExists('tl_file_location_file_location');
        Schema::dropIfExists('tl_file_location');
    }
};
