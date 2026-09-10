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
        Schema::create('tl_business_away_message_schedule_business_aw_c4687f6e65da', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_da67f013a56776fe33724cc9');
            $table->index('account_id', 'ix_7595e4be3552a2ec6cded304');
        });
        Schema::create('tl_business_away_message_schedule_business_aw_34632876acc8', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('start_date')->nullable();
            $table->integer('end_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9e50dfc3a2258115cd9e470b');
            $table->index('account_id', 'ix_e544f135a94ea7476dbe9649');
        });
        Schema::create('tl_business_away_message_schedule_business_aw_b08cf0d2f8a8', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8c95bad4eb45003d28be9c64');
            $table->index('account_id', 'ix_1b41f831be13ec5401f8243b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_b08cf0d2f8a8');
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_34632876acc8');
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_c4687f6e65da');
    }
};
