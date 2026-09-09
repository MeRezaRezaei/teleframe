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
        Schema::create('tl_business_work_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_79f3279e0ebc8fffa2ec2ead');
            $table->index('account_id', 'ix_60a510826d3f7b11c9364a19');
        });
        Schema::create('tl_business_work_hours_business_work_hours', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_work_hours')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('open_now')->default(false);
            $table->text('timezone_id');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8705d2d0e0c1d13d829fd975');
        });
        Schema::create('tl_business_work_hours_business_work_hours__weekly_open', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_business_work_hours_business_work_hours')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_6d6a7baab2d79fe6a888');
            $table->index('account_id', 'ix_3b8f14e1f371c9fc7d2fe0c1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_work_hours_business_work_hours__weekly_open');
        Schema::dropIfExists('tl_business_work_hours_business_work_hours');
        Schema::dropIfExists('tl_business_work_hours');
    }
};
