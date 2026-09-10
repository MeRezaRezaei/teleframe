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
        Schema::create('tl_star_ref_program_star_ref_program', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_0c22c6edce6d17d8104fd835');
            $table->integer('commission_permille')->nullable();
            $table->integer('duration_months')->nullable();
            $table->integer('end_date')->nullable();
            $table->bigInteger('daily_revenue_per_user')->nullable();
            $table->index('daily_revenue_per_user', 'ix_f13a3c6ae7c6f2de62e61e2a');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_64461f5f39d8c6627a6b419a');
            $table->index('account_id', 'ix_7198e2213a948fcc0c0c9e1e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_ref_program_star_ref_program');
    }
};
