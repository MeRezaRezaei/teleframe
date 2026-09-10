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
        Schema::create('tl_chat_onlines_chat_onlines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('onlines')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d8df0c41a543adc62ddf1709');
            $table->index('account_id', 'ix_45d7c36beccfb4bf4637e2e2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_chat_onlines_chat_onlines');
    }
};
