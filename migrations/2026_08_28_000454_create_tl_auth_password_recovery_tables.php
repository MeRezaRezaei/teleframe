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
        Schema::create('tl_auth_password_recovery_password_recovery', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->text('email_pattern')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_cbcedc3fd5160b2fdb1b001e');
            $table->index('account_id', 'ix_94d5dcaf9b00b3607fc0d444');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_password_recovery_password_recovery');
    }
};
