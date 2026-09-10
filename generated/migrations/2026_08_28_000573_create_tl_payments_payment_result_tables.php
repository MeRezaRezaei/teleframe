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
        Schema::create('tl_payments_payment_result_payment_result', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('updates')->nullable();
            $table->index('updates', 'ix_b930443fbeb7319d2dd3e114');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_79793b4551cf8f79ec9da8fa');
            $table->index('account_id', 'ix_16529d5ed032f9967f75413f');
        });
        Schema::create('tl_payments_payment_result_payment_verification_needed', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4790467c7fe58b6de6c01469');
            $table->index('account_id', 'ix_b346e7d7441cdc75c0e75474');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_payment_result_payment_verification_needed');
        Schema::dropIfExists('tl_payments_payment_result_payment_result');
    }
};
