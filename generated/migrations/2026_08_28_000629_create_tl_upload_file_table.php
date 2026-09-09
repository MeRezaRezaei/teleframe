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
            $table->index('constructor_id', 'ix_fcdc22ff1903bc8bc6e1a31f');
            $table->index('account_id', 'ix_ef2ef7e62a9349d17feb7c2d');
        });
        Schema::create('tl_upload_file_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_file')->cascadeOnDelete();
            $table->uuid('tl_type');
            $table->index('tl_type', 'ix_645dec64f010c7d860962e28');
            $table->integer('mtime');
            $table->binary('bytes');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f2fc4337728de0f7c53794eb');
        });
        Schema::create('tl_upload_file_file_cdn_redirect', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_upload_file')->cascadeOnDelete();
            $table->integer('dc_id');
            $table->binary('file_token');
            $table->binary('encryption_key');
            $table->binary('encryption_iv');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ab3546f853fdf3d465c28c77');
        });
        Schema::create('tl_upload_file_file_cdn_redirect__file_hashes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_upload_file_file_cdn_redirect')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_20b258e7f315d3b14d1f');
            $table->index('account_id', 'ix_513a52a38e2f2e889f67db88');
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
