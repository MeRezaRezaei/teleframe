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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_payments_validated_requested_info_validate_9668a5a19280', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_payments_validated_requested_info')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('tl_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_payments_validated_requested_info_validate_be6f170ec8df', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('tl_payments_validated_requested_info_validate_9668a5a19280')->cascadeOnDelete();
            $table->bigInteger('idx');
            $table->uuid('value_id')->nullable();
            $table->unique(['parent_id', 'idx'], 'ux_e4d0d66c89a89fab0cf7');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_be6f170ec8df');
        Schema::dropIfExists('tl_payments_validated_requested_info_validate_9668a5a19280');
        Schema::dropIfExists('tl_payments_validated_requested_info');
    }
};
