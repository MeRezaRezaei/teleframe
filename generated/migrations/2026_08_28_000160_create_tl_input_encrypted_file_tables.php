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
        Schema::create('tl_input_encrypted_file_input_encrypted_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_11ea51eca51e1b8ed84caecb');
            $table->index('account_id', 'ix_9d6af1d2003ffafe345d47db');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_big_uploaded', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('parts')->nullable();
            $table->integer('key_fingerprint')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6182a29156c6652489b7aa70');
            $table->index('account_id', 'ix_a612ed05362f23f26da2e43d');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fb7519fe2562dd033b3d7a02');
            $table->index('account_id', 'ix_002475f460526e0b32ad1f4d');
        });
        Schema::create('tl_input_encrypted_file_input_encrypted_file_uploaded', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('parts')->nullable();
            $table->text('md5_checksum')->nullable();
            $table->integer('key_fingerprint')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_bca70b92c16f8ca15b6de511');
            $table->index('account_id', 'ix_d79d9b5bfb38b622967404fe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_uploaded');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_empty');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file_big_uploaded');
        Schema::dropIfExists('tl_input_encrypted_file_input_encrypted_file');
    }
};
