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
        Schema::create('tl_payments_stars_revenue_stats_stars_revenue_stats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('top_hours_graph')->nullable();
            $table->index('top_hours_graph', 'ix_7249c29eb26c542bbe14b59d');
            $table->bigInteger('revenue_graph')->nullable();
            $table->index('revenue_graph', 'ix_eb55ea8e15af36eacd3d2f2d');
            $table->bigInteger('status')->nullable();
            $table->index('status', 'ix_cc097adb4351aefd24a62466');
            $table->double('usd_rate')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ae679332594567f9c1054733');
            $table->index('account_id', 'ix_1d14f718ecdb22bed2f2ef3e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_revenue_stats_stars_revenue_stats');
    }
};
