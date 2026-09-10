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
        Schema::create('tl_account_tmp_password_tmp_password', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('tmp_password')->nullable();
            $table->integer('valid_until')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_01f0d7102d85ef682384c2c1');
            $table->index('account_id', 'ix_e72390e4ad2989d370a71787');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_tmp_password_tmp_password');
    }
};
