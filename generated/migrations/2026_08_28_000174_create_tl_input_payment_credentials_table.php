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
        Schema::create('tl_input_payment_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_8349a66817fd3688341178b1');
            $table->index('account_id', 'ix_c5931b4a1384819882f450db');
        });
        Schema::create('tl_input_payment_credentials_input_payment_credentials', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('save')->default(false);
            $table->uuid('data');
            $table->index('data', 'ix_ffba89660c51a820924376a8');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_872ea051b2332f566d4e27bc');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_cf69945d7b14', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->uuid('payment_data');
            $table->index('payment_data', 'ix_06e210f7150cf8b2274b502c');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_d45e79bae27b920c5b97e546');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_19f70d5158de', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->uuid('payment_token');
            $table->index('payment_token', 'ix_6d69580c30c5dad5e20ee186');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_403483f6513c733e31df3dae');
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_3e3f562190d6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->text('tl_id');
            $table->binary('tmp_password');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_71cd94fab2ed786125a88693');
            $table->unique(['account_id', 'tl_id'], 'ux_f962d75e98c116896c7b');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_3e3f562190d6');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_19f70d5158de');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_cr_cf69945d7b14');
        Schema::dropIfExists('tl_input_payment_credentials_input_payment_credentials');
        Schema::dropIfExists('tl_input_payment_credentials');
    }
};
