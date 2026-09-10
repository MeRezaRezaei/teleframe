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
        Schema::create('tl_input_secure_file_input_secure_file', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dfd0b7eb88e57c2bf8b89ffc');
            $table->index('account_id', 'ix_ceb3ffed45e6765009e1e922');
        });
        Schema::create('tl_input_secure_file_input_secure_file_uploaded', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->integer('parts')->nullable();
            $table->text('md5_checksum')->nullable();
            $table->binary('file_hash')->nullable();
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4dc1587abde1fe3e22b3c9bf');
            $table->index('account_id', 'ix_2ab1f756262eca86271170a8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_secure_file_input_secure_file_uploaded');
        Schema::dropIfExists('tl_input_secure_file_input_secure_file');
    }
};
