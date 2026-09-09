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
        Schema::create('tl_secure_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_b6c52f26426a6d36d3590849');
            $table->index('account_id', 'ix_bc3d5feed16d46f66c72ab35');
        });
        Schema::create('tl_secure_file_secure_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('tl_size');
            $table->integer('dc_id');
            $table->integer('date');
            $table->binary('file_hash');
            $table->binary('secret');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_880bd1d5ca80a0c941dcfcd0');
            $table->unique(['account_id', 'tl_id'], 'ux_360ca72e626e77ed2268');
        });
        Schema::create('tl_secure_file_secure_file_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_file')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_14c495a34a59c0d872aa304b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_file_secure_file_empty');
        Schema::dropIfExists('tl_secure_file_secure_file');
        Schema::dropIfExists('tl_secure_file');
    }
};
