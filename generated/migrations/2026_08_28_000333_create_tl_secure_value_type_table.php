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
        Schema::create('tl_secure_value_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_secure_value_type_secure_value_type_address', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_bank_statement', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_driver_license', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_identity_card', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_internal_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_passpo_7641251fdc42', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_personal_details', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_rental_agreement', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_tempor_020a5e2a279c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_secure_value_type_secure_value_type_utility_bill', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_utility_bill');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_tempor_020a5e2a279c');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_rental_agreement');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_phone');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_personal_details');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_passpo_7641251fdc42');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_passport');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_internal_passport');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_identity_card');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_email');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_driver_license');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_bank_statement');
        Schema::dropIfExists('tl_secure_value_type_secure_value_type_address');
        Schema::dropIfExists('tl_secure_value_type');
    }
};
