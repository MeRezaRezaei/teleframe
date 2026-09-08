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
        Schema::create('tl_photos_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_photos_photos_photos', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photos_photos')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_photos_photos_photos__photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photos_photos_photos')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_22eb0c90e9edc9747469');
        });
        Schema::create('tl_photos_photos_photos__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photos_photos_photos')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ffa5f9e8aa6ac298a1bf');
        });
        Schema::create('tl_photos_photos_photos_slice', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_photos_photos')->cascadeOnDelete();
            $table->integer('count');
            $table->timestamps();
        });
        Schema::create('tl_photos_photos_photos_slice__photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photos_photos_photos_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_ca998ab24ca1b00d0d32');
        });
        Schema::create('tl_photos_photos_photos_slice__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_photos_photos_photos_slice')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_84a2ce5ba9f56e96415e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_photos_photos_photos_slice__users');
        Schema::dropIfExists('tl_photos_photos_photos_slice__photos');
        Schema::dropIfExists('tl_photos_photos_photos_slice');
        Schema::dropIfExists('tl_photos_photos_photos__users');
        Schema::dropIfExists('tl_photos_photos_photos__photos');
        Schema::dropIfExists('tl_photos_photos_photos');
        Schema::dropIfExists('tl_photos_photos');
    }
};
