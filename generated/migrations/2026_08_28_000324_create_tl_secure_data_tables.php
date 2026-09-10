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
        Schema::create('tl_secure_data_secure_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('data')->nullable();
            $table->binary('data_hash')->nullable();
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0e71d9926d091832bc4bb770');
            $table->index('account_id', 'ix_cd6803d4a4ead8bdf6bb1699');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_data_secure_data');
    }
};
