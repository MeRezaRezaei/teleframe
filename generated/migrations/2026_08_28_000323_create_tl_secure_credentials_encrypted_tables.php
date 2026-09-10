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
        Schema::create('tl_secure_credentials_encrypted_secure_creden_5d7271a97981', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->binary('data')->nullable();
            $table->binary('hash')->nullable();
            $table->binary('secret')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_603d692b212c649db6dc92fd');
            $table->index('account_id', 'ix_ced44552b1610704cd599319');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_secure_credentials_encrypted_secure_creden_5d7271a97981');
    }
};
