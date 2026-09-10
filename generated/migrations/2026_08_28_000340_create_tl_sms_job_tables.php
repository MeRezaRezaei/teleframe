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
        Schema::create('tl_sms_job_sms_job', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('job_id')->nullable();
            $table->text('phone_number')->nullable();
            $table->text('text')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d2358917070a637493d25bc9');
            $table->index('account_id', 'ix_8fd3c8b8c5297c992723337a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sms_job_sms_job');
    }
};
