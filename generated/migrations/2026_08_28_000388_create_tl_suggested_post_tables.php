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
        Schema::create('tl_suggested_post_suggested_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('accepted')->default(false);
            $table->boolean('rejected')->default(false);
            $table->bigInteger('price')->nullable();
            $table->index('price', 'ix_8a3e4d2a236381399f99d5bf');
            $table->integer('schedule_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8650d1e062595fd7448a5de3');
            $table->index('account_id', 'ix_601f97587ad4bf8757f22fb8');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_suggested_post_suggested_post');
    }
};
