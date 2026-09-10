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
        Schema::create('tl_payments_stars_revenue_ads_account_url_sta_1943787d2312', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2b9e7729110e63cd03f25f2e');
            $table->index('account_id', 'ix_023f136d04cb9570539a9057');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_revenue_ads_account_url_sta_1943787d2312');
    }
};
