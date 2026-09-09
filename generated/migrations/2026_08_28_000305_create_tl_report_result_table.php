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
            $table->index('constructor_id', 'ix_c093f16c766a1f1ebb1fa5f3');
            $table->index('account_id', 'ix_144593d002f8d8b6bb092491');
        });
        Schema::create('tl_report_result_report_result_add_comment', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('optional')->default(false);
            $table->binary('option');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d3e5b7ecbb08277b7ddc060b');
        });
        Schema::create('tl_report_result_report_result_choose_option', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->text('title');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_60b9bb70fd799537964727fe');
        });
        Schema::create('tl_report_result_report_result_choose_option__options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_report_result_report_result_choose_option')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_3354a037697ce93ec227');
            $table->index('account_id', 'ix_57277575f0f78a8166ea7804');
        });
        Schema::create('tl_report_result_report_result_reported', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_result')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c2141b25f113bb69c38aa762');
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
