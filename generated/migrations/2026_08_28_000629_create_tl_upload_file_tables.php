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
        Schema::create('tl_upload_file_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_type')->nullable();
            $table->index('tl_type', 'ix_645dec64f010c7d860962e28');
            $table->integer('mtime')->nullable();
            $table->binary('bytes')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9df311d4f44c494837e32a33');
            $table->index('account_id', 'ix_f2fc4337728de0f7c53794eb');
        });
        Schema::create('tl_upload_file_file_cdn_redirect', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('dc_id')->nullable();
            $table->binary('file_token')->nullable();
            $table->binary('encryption_key')->nullable();
            $table->binary('encryption_iv')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b879c6e8da8f2309685b26f5');
            $table->index('account_id', 'ix_ab3546f853fdf3d465c28c77');
        });
        Schema::create('tl_upload_file_file_cdn_redirect__file_hashes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0e48d8359d72626945f24cd2')->references('id')->on('tl_upload_file_file_cdn_redirect')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
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
    }
};
