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
        Schema::create('tl_stats_abs_value_and_prev_stats_abs_value_and_prev', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('tl_current')->nullable();
            $table->double('previous')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae8dcf9e636865288ba6a2f6');
            $table->index('account_id', 'ix_530ba376bc6767d32b13fcbe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_abs_value_and_prev_stats_abs_value_and_prev');
    }
};
