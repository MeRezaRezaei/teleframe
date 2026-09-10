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
        Schema::create('tl_stats_date_range_days_stats_date_range_days', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('min_date')->nullable();
            $table->integer('max_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_207b3bf48e50a95fb126914e');
            $table->index('account_id', 'ix_efa139d221a64ffeaa3feb79');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_date_range_days_stats_date_range_days');
    }
};
