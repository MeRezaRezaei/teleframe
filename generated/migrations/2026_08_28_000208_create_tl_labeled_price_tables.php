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
        Schema::create('tl_labeled_price_labeled_price', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('label')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2ea3916174962d81f644c97b');
            $table->index('account_id', 'ix_ad83567b68aeda1138927b22');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_labeled_price_labeled_price');
    }
};
