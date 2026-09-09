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
        Schema::create('tl_business_away_message_schedule', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ee6ea701ffc1ca12241f6b5');
            $table->index('account_id', 'ix_e2ff812fdff7f21b94985e2e');
        });
        Schema::create('tl_business_away_message_schedule_business_aw_c4687f6e65da', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_away_message_schedule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7595e4be3552a2ec6cded304');
        });
        Schema::create('tl_business_away_message_schedule_business_aw_34632876acc8', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_away_message_schedule')->cascadeOnDelete();
            $table->integer('start_date');
            $table->integer('end_date');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e544f135a94ea7476dbe9649');
        });
        Schema::create('tl_business_away_message_schedule_business_aw_b08cf0d2f8a8', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_business_away_message_schedule')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1b41f831be13ec5401f8243b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_b08cf0d2f8a8');
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_34632876acc8');
        Schema::dropIfExists('tl_business_away_message_schedule_business_aw_c4687f6e65da');
        Schema::dropIfExists('tl_business_away_message_schedule');
    }
};
