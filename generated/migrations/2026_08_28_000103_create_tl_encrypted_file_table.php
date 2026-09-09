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
        Schema::create('tl_encrypted_file', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_41025d50f2ac30217382a57f');
            $table->index('account_id', 'ix_ef63c657eb9b0f6f574d79be');
        });
        Schema::create('tl_encrypted_file_encrypted_file', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('tl_id');
            $table->bigInteger('access_hash');
            $table->bigInteger('tl_size');
            $table->integer('dc_id');
            $table->integer('key_fingerprint');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b777b804252dafbcd5fe771a');
            $table->unique(['account_id', 'tl_id'], 'ux_fed54892d6c2ecdc215e');
        });
        Schema::create('tl_encrypted_file_encrypted_file_empty', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_encrypted_file')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_32bcf856577681e5d90b2a24');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_file_encrypted_file_empty');
        Schema::dropIfExists('tl_encrypted_file_encrypted_file');
        Schema::dropIfExists('tl_encrypted_file');
    }
};
