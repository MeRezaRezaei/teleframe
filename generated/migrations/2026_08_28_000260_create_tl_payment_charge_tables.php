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
        Schema::create('tl_payment_charge_payment_charge', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('provider_charge_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_13b1aa566ed90071bf999752');
            $table->index('account_id', 'ix_3fca0aea88afda645d92ed2e');
            $table->unique(['account_id'], 'ux_562f73826e1b930f3595');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_charge_payment_charge');
    }
};
