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
        Schema::create('tl_report_reason', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_report_reason_input_report_reason_child_abuse', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_copyright', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_fake', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_geo_irrelevant', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_illegal_drugs', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_other', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_personal_details', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_pornography', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_spam', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_report_reason_input_report_reason_violence', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_report_reason')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_report_reason_input_report_reason_violence');
        Schema::dropIfExists('tl_report_reason_input_report_reason_spam');
        Schema::dropIfExists('tl_report_reason_input_report_reason_pornography');
        Schema::dropIfExists('tl_report_reason_input_report_reason_personal_details');
        Schema::dropIfExists('tl_report_reason_input_report_reason_other');
        Schema::dropIfExists('tl_report_reason_input_report_reason_illegal_drugs');
        Schema::dropIfExists('tl_report_reason_input_report_reason_geo_irrelevant');
        Schema::dropIfExists('tl_report_reason_input_report_reason_fake');
        Schema::dropIfExists('tl_report_reason_input_report_reason_copyright');
        Schema::dropIfExists('tl_report_reason_input_report_reason_child_abuse');
        Schema::dropIfExists('tl_report_reason');
    }
};
