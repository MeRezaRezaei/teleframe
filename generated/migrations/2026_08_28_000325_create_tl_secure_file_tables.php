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
        Schema::create('tl_secure_file_secure_file', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_id')->nullable();
            $table->bigInteger('access_hash')->nullable();
            $table->bigInteger('tl_size')->nullable();
            $table->integer('dc_id')->nullable();
            $table->integer('date')->nullable();
            $table->binary('file_hash')->nullable();
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_da542fb3ae480d839d8731e7');
            $table->index('account_id', 'ix_880bd1d5ca80a0c941dcfcd0');
        });
        Schema::create('tl_secure_file_secure_file_empty', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c5b8e13890eed5673d50caab');
            $table->index('account_id', 'ix_14c495a34a59c0d872aa304b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_file_secure_file_empty');
        Schema::dropIfExists('tl_secure_file_secure_file');
    }
};
