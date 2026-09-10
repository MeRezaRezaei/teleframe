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
        Schema::create('tl_stars_topup_option_stars_topup_option', function (Blueprint $table) {
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
            $table->index('constructor_id', 'ix_00950694ef99826aebbb9627');
            $table->index('account_id', 'ix_9634d7d6fb6de61981bfcda4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_stars_topup_option_stars_topup_option');
    }
};
