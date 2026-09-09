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
            $table->index('constructor_id', 'ix_f6a20326d60052370ad070f6');
            $table->index('account_id', 'ix_8a2422c39eb341de37ebaed1');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_9d6af1d2003ffafe345d47db');
            $table->unique(['account_id', 'tl_id'], 'ux_6da262db491c0c5908b9');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_big_uploaded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->integer('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a612ed05362f23f26da2e43d');
            $table->unique(['account_id', 'tl_id'], 'ux_1f772ad1e2c59b6a299e');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_002475f460526e0b32ad1f4d');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_uploaded', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->integer('parts');
            $table->text('md5_checksum');
            $table->integer('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d79d9b5bfb38b622967404fe');
            $table->unique(['account_id', 'tl_id'], 'ux_6792b3a57685fa42ed41');
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
