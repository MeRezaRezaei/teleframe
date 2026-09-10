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
        Schema::create('tl_stats_percent_value_stats_percent_value', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->double('part')->nullable();
            $table->double('total')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_aeae91e7b2357f6a967965e7');
            $table->index('account_id', 'ix_47b0d7edb140d950ebce62aa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_percent_value_stats_percent_value');
    }
};
