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
        Schema::create('tl_auth_sent_code_type', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_68b6907e13db6a673556d684');
            $table->index('account_id', 'ix_b38209b41cb598306f3762ca');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_44a76cc5aace08b1e233c0f6');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_6be85e1c36634b8489090df3');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_email_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('apple_signin_allowed')->default(false);
            $table->boolean('google_signin_allowed')->default(false);
            $table->text('email_pattern');
            $table->integer('length');
            $table->integer('reset_available_period')->nullable();
            $table->integer('reset_pending_date')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_91abc3ccdbf87ae395f42ce2');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_firebase_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->binary('nonce')->nullable();
            $table->bigInteger('play_integrity_project_id')->nullable();
            $table->index('play_integrity_project_id', 'ix_0a576caa2f479ebfe31c5f26');
            $table->binary('play_integrity_nonce')->nullable();
            $table->text('receipt')->nullable();
            $table->integer('push_timeout')->nullable();
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b7020d6918b4eb20f94c91d9');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_flash_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('pattern');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2fd154ce02fb0ce280ebc3ff');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_fragment_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('url');
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_35798b58ba524286557fabd1');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_missed_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('prefix');
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d6e4ff4ad4ad9dca62fbeb82');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_set_up__b88c5cb2dd2c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('apple_signin_allowed')->default(false);
            $table->boolean('google_signin_allowed')->default(false);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_27848aeb4667a08f246fbce4');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_70fe15f5a745f73b11c6e707');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_phrase', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_67c68c13d822246400d1c011');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_word', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
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
        Schema::dropIfExists('tl_auth_sent_code_type');
    }
};
