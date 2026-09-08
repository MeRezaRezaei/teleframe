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
        Schema::create('tl_premium_subscription_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_premium_subscription_option_premium_subscription_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_premium_subscription_option')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('tl_current')->default(false);
            $table->boolean('can_purchase_upgrade')->default(false);
            $table->text('transaction')->nullable();
            $table->integer('months');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->text('bot_url');
            $table->text('store_product')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_subscription_option_premium_subscription_option');
        Schema::dropIfExists('tl_premium_subscription_option');
    }
};
