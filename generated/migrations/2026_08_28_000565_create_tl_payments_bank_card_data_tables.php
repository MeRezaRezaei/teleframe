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
        Schema::create('tl_payments_bank_card_data_bank_card_data', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('title')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_594fc39c4e7d4ce897fb75d0');
            $table->index('account_id', 'ix_2940f2d6462f3e3d9e6f92cf');
        });
        Schema::create('tl_payments_bank_card_data_bank_card_data__open_urls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_a35a1468108b407011dd2895')->references('id')->on('tl_payments_bank_card_data_bank_card_data')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_ab820f8fc6cead734c0d');
            $table->index('account_id', 'ix_a0c2bd523f5fb2b1eb7da532');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_bank_card_data_bank_card_data__open_urls');
        Schema::dropIfExists('tl_payments_bank_card_data_bank_card_data');
    }
};
