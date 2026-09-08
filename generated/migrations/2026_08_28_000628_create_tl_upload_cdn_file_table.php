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
        Schema::create('tl_upload_cdn_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_upload_cdn_file_cdn_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_cdn_file')->cascadeOnDelete();
            $table->binary('bytes');
            $table->timestamps();
        });
        Schema::create('tl_upload_cdn_file_cdn_file_reupload_needed', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_cdn_file')->cascadeOnDelete();
            $table->binary('request_token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_upload_cdn_file_cdn_file_reupload_needed');
        Schema::dropIfExists('tl_upload_cdn_file_cdn_file');
        Schema::dropIfExists('tl_upload_cdn_file');
    }
};
