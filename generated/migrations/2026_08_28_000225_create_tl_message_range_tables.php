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
        Schema::create('tl_message_range_message_range', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('min_id')->nullable();
            $table->integer('max_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e784cee6ff9c25eda3207e0f');
            $table->index('account_id', 'ix_90f5df29936ce0da8ca25ae6');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_range_message_range');
    }
};
