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
        Schema::create('tl_input_encrypted_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->timestamps();
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_big_uploaded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->integer('key_fingerprint');
            $table->timestamps();
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_uploaded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->text('md5_checksum');
            $table->integer('key_fingerprint');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_uploaded');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_empty');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_big_uploaded');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file');
        Schema::dropIfExists('tl_input_encrypted_file');
    }
};
