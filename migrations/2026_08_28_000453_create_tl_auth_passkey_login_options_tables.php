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
        Schema::create('tl_auth_passkey_login_options_passkey_login_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('options');
            $table->index('options', 'ix_4f658b2e6a59c258ce47da31');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_b98960f9a9c19c260bea96b5');
            $table->index('account_id', 'ix_e88d063d986f7b7a539231fe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_passkey_login_options_passkey_login_options');
    }
};
