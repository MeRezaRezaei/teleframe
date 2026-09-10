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
        Schema::create('tl_bot_business_connection_bot_business_connection', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('disabled')->default(false);
            $table->text('connection_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_a05776bf10eba6a733b0ff4f');
            $table->integer('dc_id')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('rights')->nullable();
            $table->index('rights', 'ix_af518d8f85c4a7169404a92f');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d0754a83242aa6d4927594f5');
            $table->index('account_id', 'ix_f12b1347715c4602e7a6e184');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_bot_business_connection_bot_business_connection');
    }
};
