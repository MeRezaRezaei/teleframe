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
        Schema::create('tl_stars_amount_stars_amount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('amount')->nullable();
            $table->integer('nanos')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_112ae2be83986a44d5cf0509');
            $table->index('account_id', 'ix_45a9795ad3b3a0708ad64c20');
        });
        Schema::create('tl_stars_amount_stars_ton_amount', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e24c9618742478b13c494bc5');
            $table->index('account_id', 'ix_39b708fc2f0368f400479c6e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_amount_stars_ton_amount');
        Schema::dropIfExists('tl_stars_amount_stars_amount');
    }
};
