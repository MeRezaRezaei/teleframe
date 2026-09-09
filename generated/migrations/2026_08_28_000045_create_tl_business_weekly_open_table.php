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
        Schema::create('tl_business_weekly_open', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d3c08914c4060609fe7b6dbc');
            $table->index('account_id', 'ix_6156b0fd1e3780571091599c');
        });
        Schema::create('tl_business_weekly_open_business_weekly_open', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_weekly_open')->cascadeOnDelete();
            $table->integer('start_minute');
            $table->integer('end_minute');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_94162983be5b4a30c622a0cd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_weekly_open_business_weekly_open');
        Schema::dropIfExists('tl_business_weekly_open');
    }
};
