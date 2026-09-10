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
        Schema::create('tl_input_business_away_message_input_business_away_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('offline_only')->default(false);
            $table->integer('shortcut_id')->nullable();
            $table->bigInteger('schedule')->nullable();
            $table->index('schedule', 'ix_ab60e283f470c2601ce05e90');
            $table->bigInteger('recipients')->nullable();
            $table->index('recipients', 'ix_9d3ce440655fc8ff073ece6d');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_179b78147cbaeddb2bc2731b');
            $table->index('account_id', 'ix_4e57994fa98b75d1f8058d9e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_business_away_message_input_business_away_message');
    }
};
