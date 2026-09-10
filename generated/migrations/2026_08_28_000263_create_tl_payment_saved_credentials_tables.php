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
        Schema::create('tl_payment_saved_credentials_payment_saved_cr_5362dcf43125', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2c12b6681ac66028221f1692');
            $table->index('account_id', 'ix_4e105d16962c738c16f5278b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_saved_credentials_payment_saved_cr_5362dcf43125');
    }
};
