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
        Schema::create('tl_business_work_hours_business_work_hours', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('open_now')->default(false);
            $table->text('timezone_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a4d4d8a4a7399344cba6f85');
            $table->index('account_id', 'ix_8705d2d0e0c1d13d829fd975');
        });
        Schema::create('tl_business_work_hours_business_work_hours__weekly_open', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id')->constrained('tl_business_work_hours_business_work_hours', 'id', 'fk_9eae4718824a0cdc5b1e1d37')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d6a7baab2d79fe6a888');
            $table->index('account_id', 'ix_3b8f14e1f371c9fc7d2fe0c1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_work_hours_business_work_hours__weekly_open');
        Schema::dropIfExists('tl_business_work_hours_business_work_hours');
    }
};
