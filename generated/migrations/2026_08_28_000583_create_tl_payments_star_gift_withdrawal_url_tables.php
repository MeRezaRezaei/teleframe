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
        Schema::create('tl_payments_star_gift_withdrawal_url_star_gif_98844d3a363c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6d15b839f53ce3714c751900');
            $table->index('account_id', 'ix_b5a5768df733888c27bf0288');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_star_gift_withdrawal_url_star_gif_98844d3a363c');
    }
};
