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
            $table->index('constructor_id', 'ix_e5239b272e18f12a558a883d');
            $table->index('account_id', 'ix_b7287dcfb8e9f7bd404d6ef3');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_85076fa1b27322b212c874c3');
            $table->bigInteger('provider_id');
            $table->index('provider_id', 'ix_d543ff5086f93ba03b23323d');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_486ae6f76e16a8d684ea6949');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_a52bcdfed15a2024db4e946e');
            $table->uuid('info')->nullable();
            $table->index('info', 'ix_d3a87f0e22953bb10c37ed45');
            $table->uuid('shipping')->nullable();
            $table->index('shipping', 'ix_306bd6193ef6cd254e3ae9e4');
            $table->bigInteger('tip_amount')->nullable();
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('credentials_title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_998a77780cd852e6ae7f670f');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_receipt_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_bd3a34d6232789f08ead');
            $table->index('account_id', 'ix_1f7af5bd5e2c909848221999');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_payment_receipt')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->integer('date');
            $table->bigInteger('bot_id');
            $table->index('bot_id', 'ix_c1f843ab758519c21ab82e32');
            $table->text('title');
            $table->text('description');
            $table->uuid('photo')->nullable();
            $table->index('photo', 'ix_467e615e8c1d333538996029');
            $table->uuid('invoice');
            $table->index('invoice', 'ix_96182993cffab1b45932fba2');
            $table->text('currency');
            $table->bigInteger('total_amount');
            $table->text('transaction_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_93bf74528b8b29e33263286e');
        });
        Schema::create('tl_payments_payment_receipt_payment_receipt_stars__users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_payment_receipt_payment_receipt_stars')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
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
        Schema::dropIfExists('tl_payments_payment_receipt');
    }
};
