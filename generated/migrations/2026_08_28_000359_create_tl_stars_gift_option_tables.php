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
        Schema::create('tl_stars_gift_option_stars_gift_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('extended')->default(false);
            $table->bigInteger('stars')->nullable();
            $table->text('store_product')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dd72922af830c23a4ae5192b');
            $table->index('account_id', 'ix_911870344a1f05f6d0326707');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_gift_option_stars_gift_option');
    }
};
