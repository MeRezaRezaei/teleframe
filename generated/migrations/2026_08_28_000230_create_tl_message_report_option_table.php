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
        Schema::create('tl_message_report_option', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_1bb43e4ecedd369f71f1fb58');
            $table->index('account_id', 'ix_a6d8192076f52a78aadaaf6f');
        });
        Schema::create('tl_message_report_option_message_report_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_message_report_option')->cascadeOnDelete();
            $table->text('text');
            $table->binary('option');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b79f4af1b50c3272ff426d96');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_message_report_option_message_report_option');
        Schema::dropIfExists('tl_message_report_option');
    }
};
