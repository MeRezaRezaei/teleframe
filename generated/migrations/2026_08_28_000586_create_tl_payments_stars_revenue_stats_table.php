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
        Schema::create('tl_payments_stars_revenue_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_110ac5d1471cee5a26fbf0b5');
            $table->index('account_id', 'ix_5ec8107dff5a6f8601bde003');
        });
        Schema::create('tl_payments_stars_revenue_stats_stars_revenue_stats', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_stars_revenue_stats')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('top_hours_graph')->nullable();
            $table->index('top_hours_graph', 'ix_7249c29eb26c542bbe14b59d');
            $table->uuid('revenue_graph');
            $table->index('revenue_graph', 'ix_eb55ea8e15af36eacd3d2f2d');
            $table->uuid('status');
            $table->index('status', 'ix_cc097adb4351aefd24a62466');
            $table->double('usd_rate');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1d14f718ecdb22bed2f2ef3e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_revenue_stats_stars_revenue_stats');
        Schema::dropIfExists('tl_payments_stars_revenue_stats');
    }
};
