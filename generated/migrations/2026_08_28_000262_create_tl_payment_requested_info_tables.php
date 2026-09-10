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
        Schema::create('tl_payment_requested_info_payment_requested_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('name')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->bigInteger('shipping_address')->nullable();
            $table->index('shipping_address', 'ix_5f33f5cd97652efb46253485');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_a02d7cdf134a4d05a2989c96');
            $table->index('account_id', 'ix_b0b98e1fd5156725da4e292e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_requested_info_payment_requested_info');
    }
};
