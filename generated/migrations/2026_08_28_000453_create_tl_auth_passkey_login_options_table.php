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
        Schema::create('tl_auth_passkey_login_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_435c70ed1a99fb51c2221f5b');
            $table->index('account_id', 'ix_d379a59a3053f978d7ff295d');
        });
        Schema::create('tl_auth_passkey_login_options_passkey_login_options', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_passkey_login_options')->cascadeOnDelete();
            $table->uuid('options');
            $table->index('options', 'ix_4f658b2e6a59c258ce47da31');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_e88d063d986f7b7a539231fe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_passkey_login_options_passkey_login_options');
        Schema::dropIfExists('tl_auth_passkey_login_options');
    }
};
