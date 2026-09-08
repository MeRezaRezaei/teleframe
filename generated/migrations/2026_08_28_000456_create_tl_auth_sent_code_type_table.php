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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_app', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->timestamps();
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
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_firebase_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->binary('nonce')->nullable();
            $table->bigInteger('play_integrity_project_id')->nullable();
            $table->binary('play_integrity_nonce')->nullable();
            $table->text('receipt')->nullable();
            $table->integer('push_timeout')->nullable();
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_flash_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('pattern');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_fragment_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('url');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_missed_call', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->text('prefix');
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_set_up__b88c5cb2dd2c', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('apple_signin_allowed')->default(false);
            $table->boolean('google_signin_allowed')->default(false);
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->integer('length');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_phrase', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_type_sent_code_type_sms_word', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code_type')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('beginning')->nullable();
            $table->timestamps();
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
