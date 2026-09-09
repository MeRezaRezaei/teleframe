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
        Schema::create('tl_sms_job', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_c69e381464a3e7ce4f89c966');
            $table->index('account_id', 'ix_f299696b44c561709c1715c4');
        });
        Schema::create('tl_sms_job_sms_job', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sms_job')->cascadeOnDelete();
            $table->text('job_id');
            $table->text('phone_number');
            $table->text('text');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8fd3c8b8c5297c992723337a');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sms_job_sms_job');
        Schema::dropIfExists('tl_sms_job');
    }
};
