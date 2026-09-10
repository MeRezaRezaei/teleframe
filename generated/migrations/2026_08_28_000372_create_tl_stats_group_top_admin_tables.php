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
        Schema::create('tl_stats_group_top_admin_stats_group_top_admin', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('user_id')->nullable();
            $table->index('user_id', 'ix_5fe6de302b721edc46299c74');
            $table->integer('deleted')->nullable();
            $table->integer('kicked')->nullable();
            $table->integer('banned')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_36fedb585437c8099ebcf2c1');
            $table->index('account_id', 'ix_b5e1cbef7b383ed36cefd8e6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stats_group_top_admin_stats_group_top_admin');
    }
};
