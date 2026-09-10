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
        Schema::create('tl_auth_sent_code_type_sent_code_type_app', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f90f48580ca8962c401310a9');
            $table->index('account_id', 'ix_44a76cc5aace08b1e233c0f6');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_6cbd1a279f7e7def4e9e968c');
            $table->index('account_id', 'ix_6be85e1c36634b8489090df3');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_email_code', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('apple_signin_allowed')->default(false);
            $table->boolean('google_signin_allowed')->default(false);
            $table->text('email_pattern')->nullable();
            $table->integer('length')->nullable();
            $table->integer('reset_available_period')->nullable();
            $table->integer('reset_pending_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ac1dc7fd1cbbffaa0e9db903');
            $table->index('account_id', 'ix_91abc3ccdbf87ae395f42ce2');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_firebase_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->binary('nonce')->nullable();
            $table->bigInteger('play_integrity_project_id')->nullable();
            $table->index('play_integrity_project_id', 'ix_0a576caa2f479ebfe31c5f26');
            $table->binary('play_integrity_nonce')->nullable();
            $table->text('receipt')->nullable();
            $table->integer('push_timeout')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_0786d2774aaf06b8d55c53cc');
            $table->index('account_id', 'ix_b7020d6918b4eb20f94c91d9');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_flash_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('pattern')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_4f5c9aa457185b5571656b38');
            $table->index('account_id', 'ix_2fd154ce02fb0ce280ebc3ff');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_fragment_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('url')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_dae5fedd340d8f038881c14e');
            $table->index('account_id', 'ix_35798b58ba524286557fabd1');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_missed_call', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('prefix')->nullable();
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d02ac454805c883d48309829');
            $table->index('account_id', 'ix_d6e4ff4ad4ad9dca62fbeb82');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_set_up__b88c5cb2dd2c', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('apple_signin_allowed')->default(false);
            $table->boolean('google_signin_allowed')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_e2788dece940b087ae1dac99');
            $table->index('account_id', 'ix_27848aeb4667a08f246fbce4');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->integer('length')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_346a5fc7e585abf09d385f5e');
            $table->index('account_id', 'ix_70fe15f5a745f73b11c6e707');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_phrase', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_ba18815b88d96602d7055e2d');
            $table->index('account_id', 'ix_67c68c13d822246400d1c011');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_word', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1ffb54911f587217c9c919e0');
            $table->index('account_id', 'ix_00290859656b878d85561162');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_sms_word');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_sms_phrase');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_sms');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_set_up__b88c5cb2dd2c');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_missed_call');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_fragment_sms');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_flash_call');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_firebase_sms');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_email_code');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_call');
        Schema::dropIfExists('tl_auth_sent_code_type_sent_code_type_app');
    }
};
