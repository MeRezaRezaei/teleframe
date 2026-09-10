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
        Schema::create('tl_account_email_verified_email_verified', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('email')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_c4f82ed34eb339f76bc2f233');
            $table->index('account_id', 'ix_b18360f14c4d09fe33709d9e');
        });
        Schema::create('tl_account_email_verified_email_verified_login', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('email')->nullable();
            $table->bigInteger('sent_code')->nullable();
            $table->index('sent_code', 'ix_0fb3a311104dca7cb6ef2492');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_7e7c8135b71f100d6f3c9fc0');
            $table->index('account_id', 'ix_ede1791e604c78ae8d7a0c42');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_email_verified_email_verified_login');
        Schema::dropIfExists('tl_account_email_verified_email_verified');
    }
};
