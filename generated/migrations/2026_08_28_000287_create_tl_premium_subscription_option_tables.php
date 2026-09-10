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
        Schema::create('tl_premium_subscription_option_premium_subscription_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_current')->default(false);
            $table->boolean('can_purchase_upgrade')->default(false);
            $table->text('transaction')->nullable();
            $table->integer('months')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->text('bot_url')->nullable();
            $table->text('store_product')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8daae9944705e2934728a13c');
            $table->index('account_id', 'ix_ee7aeb26159c5027bcb43a8a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_subscription_option_premium_subscription_option');
    }
};
