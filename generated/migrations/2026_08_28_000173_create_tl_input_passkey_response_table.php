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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_input_passkey_response_input_passkey_response_login', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_response')->cascadeOnDelete();
            $table->uuid('client_data');
            $table->binary('authenticator_data');
            $table->binary('signature');
            $table->text('user_handle');
            $table->timestamps();
        });
        Schema::create('tl_input_passkey_response_input_passkey_response_register', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_input_passkey_response')->cascadeOnDelete();
            $table->uuid('client_data');
            $table->binary('attestation_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_register');
        Schema::dropIfExists('tl_input_passkey_response_input_passkey_response_login');
        Schema::dropIfExists('tl_input_passkey_response');
    }
};
