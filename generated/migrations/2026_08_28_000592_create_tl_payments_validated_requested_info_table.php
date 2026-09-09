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
        Schema::create('tl_payments_validated_requested_info', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_bffe60f77ea195fbe7db6fd4');
            $table->index('account_id', 'ix_9ad04304ed5a21e7cb489a2a');
        });
        Schema::create('tl_payments_validated_requested_info_validate_9668a5a19280', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_validated_requested_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_c1922504e717ff76df273dfb');
            $table->unique(['account_id', 'tl_id'], 'ux_5a0bf6003cd2d1540c04');
        });
        Schema::create('tl_payments_validated_requested_info_validate_be6f170ec8df', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_validated_requested_info_validate_9668a5a19280')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->bigInteger('account_id');
            $table->unique(['parent_id', 'idx'], 'ux_e4d0d66c89a89fab0cf7');
            $table->index('account_id', 'ix_ff709ef05c7a5ec1cb3abbd7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_be6f170ec8df');
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_9668a5a19280');
        Schema::dropIfExists('tl_payments_validated_requested_info');
    }
};
