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
        Schema::create('tl_business_weekly_open_business_weekly_open', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('start_minute')->nullable();
            $table->integer('end_minute')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_08e27828ed4ac6aa1a2079dd');
            $table->index('account_id', 'ix_94162983be5b4a30c622a0cd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_weekly_open_business_weekly_open');
    }
};
