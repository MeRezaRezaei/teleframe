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
        Schema::create('tl_business_intro_business_intro', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->bigInteger('sticker')->nullable();
            $table->index('sticker', 'ix_bfaacdfb18092c94624348a5');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0c4b8a91b5df547c75fdab04');
            $table->index('account_id', 'ix_cec88f966c079f57bd09a022');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_intro_business_intro');
    }
};
