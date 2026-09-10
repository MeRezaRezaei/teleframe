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
        Schema::create('tl_payments_validated_requested_info_validate_9668a5a19280', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_fd0361503e3bfb97e656c29e');
            $table->index('account_id', 'ix_c1922504e717ff76df273dfb');
        });
        Schema::create('tl_payments_validated_requested_info_validate_be6f170ec8df', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parent_id');
            $table->foreign('parent_id', 'fk_0489c7dbb0de9332dddbf58d')->references('id')->on('tl_payments_validated_requested_info_validate_9668a5a19280')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->bigInteger('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e4d0d66c89a89fab0cf7');
            $table->index('account_id', 'ix_ff709ef05c7a5ec1cb3abbd7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_be6f170ec8df');
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_9668a5a19280');
    }
};
