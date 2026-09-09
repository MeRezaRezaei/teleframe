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
        Schema::create('tl_account_tmp_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_2a66c258d42b4ec303ec0621');
            $table->index('account_id', 'ix_655000da110aecb703214696');
        });
        Schema::create('tl_account_tmp_password_tmp_password', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_tmp_password')->cascadeOnDelete();
            $table->binary('tmp_password');
            $table->integer('valid_until');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e72390e4ad2989d370a71787');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_tmp_password_tmp_password');
        Schema::dropIfExists('tl_account_tmp_password');
    }
};
