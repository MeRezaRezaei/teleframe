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
        Schema::create('tl_payments_payment_receipt', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date');
            $table->bigInteger('bot_id');
            $table->bigInteger('provider_id');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->uuid('info')->nullable();
            $table->uuid('shipping')->nullable();
            $table->bigInteger('tip_amount')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('credentials_title');
            $table->timestamps();
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_receipt_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_bd3a34d6232789f08ead');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date');
            $table->bigInteger('bot_id');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->uuid('invoice');
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('transaction_id');
            $table->timestamps();
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_receipt_payment_receipt_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_4e5a130baa92df682d8b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt_stars__users');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt_stars');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt__users');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt');
        Schema::dropIfExists('tl_payments_payment_receipt');
    }
};
