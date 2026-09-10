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
        Schema::create('tl_secure_value_type_secure_value_type_address', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2df87260fa3c0fb9a7708352');
            $table->index('account_id', 'ix_66d66ecf6ce370574c81dbe3');
        });
        Schema::create('tl_secure_value_type_secure_value_type_bank_statement', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_313ace32729a48fd84e1d194');
            $table->index('account_id', 'ix_1df4572a84e5acc030521f12');
        });
        Schema::create('tl_secure_value_type_secure_value_type_driver_license', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d2d5273435ced7686239236d');
            $table->index('account_id', 'ix_2708aaa1518e32c0eed7cf21');
        });
        Schema::create('tl_secure_value_type_secure_value_type_email', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b251523c0762556c1ffd0e44');
            $table->index('account_id', 'ix_2389c2af04c047b8748eeb47');
        });
        Schema::create('tl_secure_value_type_secure_value_type_identity_card', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ffabd050556285508c7bc60c');
            $table->index('account_id', 'ix_cb1ef0e048e075b75dec83b9');
        });
        Schema::create('tl_secure_value_type_secure_value_type_internal_passport', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_307f9eb50575d9bb480bc25b');
            $table->index('account_id', 'ix_6e2ae79d4b1667a18fc4d010');
        });
        Schema::create('tl_secure_value_type_secure_value_type_passport', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_776053cb2f2f16f574629b2b');
            $table->index('account_id', 'ix_aac055d2167ef1d2965fa2cd');
        });
        Schema::create('tl_secure_value_type_secure_value_type_passpo_7641251fdc42', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_5ecad00fe926072b06e5f5e6');
            $table->index('account_id', 'ix_70c32c6219e2646e5b5dc8df');
        });
        Schema::create('tl_secure_value_type_secure_value_type_personal_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_34f3dd718f832c9af61f6424');
            $table->index('account_id', 'ix_a4f57e6972e5b2c07386f251');
        });
        Schema::create('tl_secure_value_type_secure_value_type_phone', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4235196ed7c37cef80cd0b16');
            $table->index('account_id', 'ix_ea46b11a83bd4e626db16e11');
        });
        Schema::create('tl_secure_value_type_secure_value_type_rental_agreement', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e45afa6420322fee2ee7b027');
            $table->index('account_id', 'ix_a5c6064a318edee519fb30c7');
        });
        Schema::create('tl_secure_value_type_secure_value_type_tempor_020a5e2a279c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_67cd52055ffbff0934b3b2c4');
            $table->index('account_id', 'ix_f09959c8f5d16effbb0f90a7');
        });
        Schema::create('tl_secure_value_type_secure_value_type_utility_bill', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f9108a5e9e8ec9f8046f34a1');
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
    }
};
