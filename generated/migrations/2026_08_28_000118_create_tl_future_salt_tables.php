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
        Schema::create('tl_future_salt_future_salt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('valid_since')->nullable();
            $table->integer('valid_until')->nullable();
            $table->bigInteger('salt')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c6db0d899c65e284c8e1d1d9');
            $table->index('account_id', 'ix_87955f2d7712247083e64b2c');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_future_salt_future_salt');
    }
};
