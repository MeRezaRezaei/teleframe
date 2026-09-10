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
        Schema::create('tl_stars_subscription_pricing_stars_subscription_pricing', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('period')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6e9766f863173339d7544b17');
            $table->index('account_id', 'ix_d80e09a82caf27a0ff90be58');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_subscription_pricing_stars_subscription_pricing');
    }
};
