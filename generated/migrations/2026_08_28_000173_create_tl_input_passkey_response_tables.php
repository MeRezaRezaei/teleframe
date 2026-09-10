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
        Schema::create('tl_input_passkey_response_input_passkey_response_login', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('client_data')->nullable();
            $table->index('client_data', 'ix_3910d00833e285e288c6f988');
            $table->binary('authenticator_data')->nullable();
            $table->binary('signature')->nullable();
            $table->text('user_handle')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_39bdc445286ea1a27a180f54');
            $table->index('account_id', 'ix_0fe9f96ca06c965f525a4bdd');
        });
        Schema::create('tl_input_passkey_response_input_passkey_response_register', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('client_data')->nullable();
            $table->index('client_data', 'ix_8d23f1f6bc0a91dee0f2c0ad');
            $table->binary('attestation_data')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_1bcd8455947463c7941eb075');
            $table->index('account_id', 'ix_7974fb1d6875ff9417685b01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_register');
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_login');
    }
};
