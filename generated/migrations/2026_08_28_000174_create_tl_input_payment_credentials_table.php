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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_payment_credentials_input_payment_credentials', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('save')->default(false);
            $table->uuid('data');
            $table->timestamps();
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_cf69945d7b14', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->uuid('payment_data');
            $table->timestamps();
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_19f70d5158de', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->uuid('payment_token');
            $table->timestamps();
        });
        Schema::create('tl_input_payment_credentials_input_payment_cr_3e3f562190d6', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_payment_credentials')->cascadeOnDelete();
            $table->text('tl_id');
            $table->binary('tmp_password');
            $table->timestamps();
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
