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
        Schema::create('tl_report_reason_input_report_reason_child_abuse', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7f0609532da6fcf969054aa6');
            $table->index('account_id', 'ix_3921f8864c548e5dd99b4f88');
        });
        Schema::create('tl_report_reason_input_report_reason_copyright', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e794a2572c69b65198bd0f59');
            $table->index('account_id', 'ix_fa9c21bf4b79a1ab21a74943');
        });
        Schema::create('tl_report_reason_input_report_reason_fake', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_115cc0fd05ed6127299a537f');
            $table->index('account_id', 'ix_32bd0caf0bd4398817b34c06');
        });
        Schema::create('tl_report_reason_input_report_reason_geo_irrelevant', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_9ccd3d7dd0c474f92f2fe52a');
            $table->index('account_id', 'ix_40a2d6558b15d7648fb2f188');
        });
        Schema::create('tl_report_reason_input_report_reason_illegal_drugs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b7e1776cbd45aa4fc32f537d');
            $table->index('account_id', 'ix_195f65eb889bbb38d53f40b6');
        });
        Schema::create('tl_report_reason_input_report_reason_other', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_661bc6592b55a61bca57c994');
            $table->index('account_id', 'ix_8f0a119ca32b7daa22828e65');
        });
        Schema::create('tl_report_reason_input_report_reason_personal_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4538ed777609eb7e0d0de756');
            $table->index('account_id', 'ix_759ec36302bb3894ab41ca8a');
        });
        Schema::create('tl_report_reason_input_report_reason_pornography', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_3f752b93f4c9242cc82d8da3');
            $table->index('account_id', 'ix_8efe1c1c11ba27d9a5335725');
        });
        Schema::create('tl_report_reason_input_report_reason_spam', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b50c087aca7ac0081b8ae185');
            $table->index('account_id', 'ix_6dcb1d72ae6af143de578db2');
        });
        Schema::create('tl_report_reason_input_report_reason_violence', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6b614c79489107be22fd0ede');
            $table->index('account_id', 'ix_d6a2902012cc97923ba4e181');
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
    }
};
