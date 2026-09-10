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
        Schema::create('tl_premium_gift_code_option_premium_gift_code_option', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->integer('users')->nullable();
            $table->integer('months')->nullable();
            $table->text('store_product')->nullable();
            $table->integer('store_quantity')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f358236e879532cf61bfdde7');
            $table->index('account_id', 'ix_08f488473546bb6cc00b0248');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_premium_gift_code_option_premium_gift_code_option');
    }
};
