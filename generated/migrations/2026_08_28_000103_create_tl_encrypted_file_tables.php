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
        Schema::create('tl_encrypted_file_encrypted_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('tl_size')->nullable();
            $table->integer('dc_id')->nullable();
            $table->integer('key_fingerprint')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d227fb36f7aad4655eb6ee50');
            $table->index('account_id', 'ix_b777b804252dafbcd5fe771a');
        });
        Schema::create('tl_encrypted_file_encrypted_file_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ec633cda4e85e5dfb0d85659');
            $table->index('account_id', 'ix_32bcf856577681e5d90b2a24');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_encrypted_file_encrypted_file_empty');
        Schema::dropIfExists('tl_encrypted_file_encrypted_file');
    }
};
