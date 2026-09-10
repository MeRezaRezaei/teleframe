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
        Schema::create('tl_payments_stars_status_stars_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->bigInteger('balance')->nullable();
            $table->index('balance', 'ix_b71cc86454ef33bd2cf1d748');
            $table->text('subscriptions_next_offset')->nullable();
            $table->bigInteger('subscriptions_missing_balance')->nullable();
            $table->text('next_offset')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3408c7abc168b01a52d49028');
            $table->index('account_id', 'ix_5bdda2e61df2e5d3121b5499');
        });
        Schema::create('tl_payments_stars_status_stars_status__subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_275ff63ac745845614d032a9')->references('id')->on('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_8b522994047e25269a57');
            $table->index('account_id', 'ix_34e3264a5eb855a5bda550aa');
        });
        Schema::create('tl_payments_stars_status_stars_status__history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_9e4a6ae50b6345be35969f8f')->references('id')->on('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_fdedad58fc14001b086d');
            $table->index('account_id', 'ix_4219b11c595815d75c1391c4');
        });
        Schema::create('tl_payments_stars_status_stars_status__chats', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0394d896bceafbff3277ddd8')->references('id')->on('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_1b1ea0cb05e4d01a7a03');
            $table->index('account_id', 'ix_58443ad4e35ced06c033c6eb');
        });
        Schema::create('tl_payments_stars_status_stars_status__users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_c01fd94c67b701668bc7023e')->references('id')->on('tl_payments_stars_status_stars_status')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_743645596d180c0cd7b2');
            $table->index('account_id', 'ix_53df7711430327bf7e9cba7f');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_stars_status_stars_status__users');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__chats');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__history');
        Schema::dropIfExists('tl_payments_stars_status_stars_status__subscriptions');
        Schema::dropIfExists('tl_payments_stars_status_stars_status');
    }
};
