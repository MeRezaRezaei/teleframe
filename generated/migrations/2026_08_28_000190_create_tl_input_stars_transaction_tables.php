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
        Schema::create('tl_input_stars_transaction_input_stars_transaction', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('refund')->default(false);
            $table->text('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_50b82f04c2bd4d5a5f749863');
            $table->index('account_id', 'ix_ab92cb9fc59246edd56113d5');
            $table->unique(['account_id'], 'ux_993852539e2eab745dc7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_stars_transaction_input_stars_transaction');
    }
};
