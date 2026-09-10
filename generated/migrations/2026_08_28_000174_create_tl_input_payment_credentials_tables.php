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
        Schema::create('tl_input_payment_credentials_input_payment_credentials', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('save')->default(false);
            $table->bigInteger('data')->nullable();
            $table->index('data', 'ix_ffba89660c51a820924376a8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d5826861a9867cbe7feaa208');
            $table->index('account_id', 'ix_872ea051b2332f566d4e27bc');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_cf69945d7b14', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('payment_data')->nullable();
            $table->index('payment_data', 'ix_06e210f7150cf8b2274b502c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_d32ab0a0667ac3afebdfc89a');
            $table->index('account_id', 'ix_d45e79bae27b920c5b97e546');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_19f70d5158de', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('payment_token')->nullable();
            $table->index('payment_token', 'ix_6d69580c30c5dad5e20ee186');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7a35b9df2399d246f5aea99c');
            $table->index('account_id', 'ix_403483f6513c733e31df3dae');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_3e3f562190d6', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('tl_id')->nullable();
            $table->binary('tmp_password')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_2141ca3bafe1d35576bcede3');
            $table->index('account_id', 'ix_71cd94fab2ed786125a88693');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_3e3f562190d6');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_19f70d5158de');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_cf69945d7b14');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_credentials');
    }
};
