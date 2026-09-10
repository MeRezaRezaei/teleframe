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
        Schema::create('tl_search_results_calendar_period_search_resu_9116af4a75f6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->integer('min_msg_id')->nullable();
            $table->integer('max_msg_id')->nullable();
            $table->integer('count')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2255ee9bc90e3287bcbab268');
            $table->index('account_id', 'ix_04c79d3ebebf4f8877ddd7f2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_search_results_calendar_period_search_resu_9116af4a75f6');
    }
};
