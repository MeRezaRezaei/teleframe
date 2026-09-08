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
        Schema::create('tl_report_result', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_report_result_report_result_add_comment', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('optional')->default(false);
            $table->binary('option');
            $table->timestamps();
        });
        Schema::create('tl_report_result_report_result_choose_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->text('title');
            $table->timestamps();
        });
        Schema::create('tl_report_result_report_result_choose_option__options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_report_result_report_result_choose_option')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_3354a037697ce93ec227');
        });
        Schema::create('tl_report_result_report_result_reported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_report_result_report_result_reported');
        Schema::dropIfExists('tl_report_result_report_result_choose_option__options');
        Schema::dropIfExists('tl_report_result_report_result_choose_option');
        Schema::dropIfExists('tl_report_result_report_result_add_comment');
        Schema::dropIfExists('tl_report_result');
    }
};
