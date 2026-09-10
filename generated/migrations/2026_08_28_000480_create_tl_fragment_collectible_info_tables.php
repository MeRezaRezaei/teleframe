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
        Schema::create('tl_fragment_collectible_info_collectible_info', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('purchase_date')->nullable();
            $table->text('currency')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->text('crypto_currency')->nullable();
            $table->bigInteger('crypto_amount')->nullable();
            $table->text('url')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1ed4287fcdea918cdd9060ab');
            $table->index('account_id', 'ix_fc7c85348bfda2c94f10688b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_fragment_collectible_info_collectible_info');
    }
};
