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
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_change', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1d9f20076467fea45d432e63');
            $table->index('account_id', 'ix_abac247c747d3a839c824139');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_setup', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('phone_number')->nullable();
            $table->text('phone_code_hash')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_8f959521d60b51da1f3599f1');
            $table->index('account_id', 'ix_cda727acbd5aba7869b33eb6');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_passport', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b789bbf4bea32665da69c350');
            $table->index('account_id', 'ix_36aed09093468eefcce25f3b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_passport');
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_login_setup');
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_login_change');
    }
};
