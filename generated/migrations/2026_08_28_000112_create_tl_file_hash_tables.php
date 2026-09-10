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
        Schema::create('tl_file_hash_file_hash', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('tl_offset')->nullable();
            $table->integer('tl_limit')->nullable();
            $table->binary('hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8138118c404db092b2612b79');
            $table->index('account_id', 'ix_8f6752d903a2caa400384379');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_file_hash_file_hash');
    }
};
