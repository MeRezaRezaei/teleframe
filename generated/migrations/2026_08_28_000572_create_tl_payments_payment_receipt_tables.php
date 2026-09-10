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
        Schema::create('tl_payments_payment_receipt_payment_receipt', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_85076fa1b27322b212c874c3');
            $table->bigInteger('provider_id')->nullable();
            $table->index('provider_id', 'ix_d543ff5086f93ba03b23323d');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_486ae6f76e16a8d684ea6949');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_a52bcdfed15a2024db4e946e');
            $table->bigInteger('info')->nullable();
            $table->index('info', 'ix_d3a87f0e22953bb10c37ed45');
            $table->bigInteger('shipping')->nullable();
            $table->index('shipping', 'ix_306bd6193ef6cd254e3ae9e4');
            $table->bigInteger('tip_amount')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('total_amount')->nullable();
            $table->text('credentials_title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_34862e704713072fd7583325');
            $table->index('account_id', 'ix_998a77780cd852e6ae7f670f');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_be0edb52c56f80e008b17201')->references('id')->on('tl_payments_payment_receipt_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bd3a34d6232789f08ead');
            $table->index('account_id', 'ix_1f7af5bd5e2c909848221999');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('date')->nullable();
            $table->bigInteger('bot_id')->nullable();
            $table->index('bot_id', 'ix_c1f843ab758519c21ab82e32');
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('photo')->nullable();
            $table->index('photo', 'ix_467e615e8c1d333538996029');
            $table->bigInteger('invoice')->nullable();
            $table->index('invoice', 'ix_96182993cffab1b45932fba2');
            $table->text('currency')->nullable();
            $table->bigInteger('total_amount')->nullable();
            $table->text('transaction_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_58e2a0df242d2e2154326b8e');
            $table->index('account_id', 'ix_93bf74528b8b29e33263286e');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_93be7352503aa96cca815791')->references('id')->on('tl_payments_payment_receipt_payment_receipt_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_4e5a130baa92df682d8b');
            $table->index('account_id', 'ix_7e0ad419ed265774854be848');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt_stars__users');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt_stars');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt__users');
        Schema::dropIfExists('tl_payments_payment_receipt_payment_receipt');
    }
};
