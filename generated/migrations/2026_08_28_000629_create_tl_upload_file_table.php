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
        Schema::create('tl_upload_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_upload_file_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_file')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->integer('mtime');
            $table->binary('bytes');
            $table->timestamps();
        });
        Schema::create('tl_upload_file_file_cdn_redirect', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_file')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->binary('file_token');
            $table->binary('encryption_key');
            $table->binary('encryption_iv');
            $table->timestamps();
        });
        Schema::create('tl_upload_file_file_cdn_redirect__file_hashes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_upload_file_file_cdn_redirect')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_20b258e7f315d3b14d1f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_upload_file_file_cdn_redirect__file_hashes');
        Schema::dropIfExists('tl_upload_file_file_cdn_redirect');
        Schema::dropIfExists('tl_upload_file_file');
        Schema::dropIfExists('tl_upload_file');
    }
};
