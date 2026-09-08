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
        Schema::create('tl_photos_photo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_photos_photo_photo', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photos_photo')->cascadeOnDelete();
            $table->uuid('photo');
            $table->timestamps();
        });
        Schema::create('tl_photos_photo_photo__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photos_photo_photo')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_cbae3c4749d3aabc2c28');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photos_photo_photo__users');
        Schema::dropIfExists('tl_photos_photo_photo');
        Schema::dropIfExists('tl_photos_photo');
    }
};
