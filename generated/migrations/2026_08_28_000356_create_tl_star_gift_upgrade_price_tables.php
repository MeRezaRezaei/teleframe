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
        Schema::create('tl_star_gift_upgrade_price_star_gift_upgrade_price', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('date')->nullable();
            $table->bigInteger('upgrade_stars')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_91d964e84b08dae4deb38f82');
            $table->index('account_id', 'ix_fa91aad1bbfaf4c30f41283e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_star_gift_upgrade_price_star_gift_upgrade_price');
    }
};
