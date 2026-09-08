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
        Schema::create('tl_storage_file_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_storage_file_type_file_gif', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_jpeg', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_mov', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_mp3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_mp4', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_partial', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_pdf', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_png', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_unknown', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_storage_file_type_file_webp', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_storage_file_type')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_storage_file_type_file_webp');
        Schema::dropIfExists('tl_storage_file_type_file_unknown');
        Schema::dropIfExists('tl_storage_file_type_file_png');
        Schema::dropIfExists('tl_storage_file_type_file_pdf');
        Schema::dropIfExists('tl_storage_file_type_file_partial');
        Schema::dropIfExists('tl_storage_file_type_file_mp4');
        Schema::dropIfExists('tl_storage_file_type_file_mp3');
        Schema::dropIfExists('tl_storage_file_type_file_mov');
        Schema::dropIfExists('tl_storage_file_type_file_jpeg');
        Schema::dropIfExists('tl_storage_file_type_file_gif');
        Schema::dropIfExists('tl_storage_file_type');
    }
};
