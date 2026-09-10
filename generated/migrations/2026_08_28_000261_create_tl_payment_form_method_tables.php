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
        Schema::create('tl_payment_form_method_payment_form_method', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b5465985bc57c2c5cf758cc9');
            $table->index('account_id', 'ix_0cbf6a08caab6f81c21752e0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payment_form_method_payment_form_method');
    }
};
