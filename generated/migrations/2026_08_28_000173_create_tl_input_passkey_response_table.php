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
        Schema::create('tl_input_passkey_response', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_5c771828a889b9244e4c4b91');
            $table->index('account_id', 'ix_a6b141b4b2ec34218c7394bf');
        });
        Schema::create('tl_input_passkey_response_input_passkey_response_login', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_response')->cascadeOnDelete();
            $table->uuid('client_data');
            $table->index('client_data', 'ix_3910d00833e285e288c6f988');
            $table->binary('authenticator_data');
            $table->binary('signature');
            $table->text('user_handle');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_0fe9f96ca06c965f525a4bdd');
        });
        Schema::create('tl_input_passkey_response_input_passkey_response_register', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_response')->cascadeOnDelete();
            $table->uuid('client_data');
            $table->index('client_data', 'ix_8d23f1f6bc0a91dee0f2c0ad');
            $table->binary('attestation_data');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_7974fb1d6875ff9417685b01');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_register');
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_login');
        Schema::dropIfExists('tl_input_passkey_response');
    }
};
