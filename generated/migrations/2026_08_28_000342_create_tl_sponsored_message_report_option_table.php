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
        Schema::create('tl_sponsored_message_report_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_d7c57e26290317c52182d68d');
            $table->index('account_id', 'ix_40686c044a5b7b8ae646c675');
        });
        Schema::create('tl_sponsored_message_report_option_sponsored__39be773435f3', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_sponsored_message_report_option')->cascadeOnDelete();
            $table->text('text');
            $table->binary('option');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_8fe240bf3c045f0bf3152673');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_sponsored_message_report_option_sponsored__39be773435f3');
        Schema::dropIfExists('tl_sponsored_message_report_option');
    }
};
