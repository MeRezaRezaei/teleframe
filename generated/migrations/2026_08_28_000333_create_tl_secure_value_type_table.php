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
            $table->index('constructor_id', 'ix_9fe159bbdd3f25785e279b31');
            $table->index('account_id', 'ix_44b63ef05020a2544de012cd');
        });
        Schema::create('tl_secure_value_type_secure_value_type_address', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_66d66ecf6ce370574c81dbe3');
        });
        Schema::create('tl_secure_value_type_secure_value_type_bank_statement', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_1df4572a84e5acc030521f12');
        });
        Schema::create('tl_secure_value_type_secure_value_type_driver_license', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2708aaa1518e32c0eed7cf21');
        });
        Schema::create('tl_secure_value_type_secure_value_type_email', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2389c2af04c047b8748eeb47');
        });
        Schema::create('tl_secure_value_type_secure_value_type_identity_card', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cb1ef0e048e075b75dec83b9');
        });
        Schema::create('tl_secure_value_type_secure_value_type_internal_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6e2ae79d4b1667a18fc4d010');
        });
        Schema::create('tl_secure_value_type_secure_value_type_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_aac055d2167ef1d2965fa2cd');
        });
        Schema::create('tl_secure_value_type_secure_value_type_passpo_7641251fdc42', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70c32c6219e2646e5b5dc8df');
        });
        Schema::create('tl_secure_value_type_secure_value_type_personal_details', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a4f57e6972e5b2c07386f251');
        });
        Schema::create('tl_secure_value_type_secure_value_type_phone', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ea46b11a83bd4e626db16e11');
        });
        Schema::create('tl_secure_value_type_secure_value_type_rental_agreement', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_a5c6064a318edee519fb30c7');
        });
        Schema::create('tl_secure_value_type_secure_value_type_tempor_020a5e2a279c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_f09959c8f5d16effbb0f90a7');
        });
        Schema::create('tl_secure_value_type_secure_value_type_utility_bill', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_secure_value_type')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_06d4220de50a46dd7496e6da');
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
