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
        Schema::create('tl_account_password_password', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('constructor_id');
            $table->string('constructor_name', 96);
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_recovery')->default(false);
            $table->boolean('has_secure_values')->default(false);
            $table->boolean('has_password')->default(false);
            $table->bigInteger('current_algo')->nullable();
            $table->index('current_algo', 'ix_de32a6ced0ddf7f8a9e55a85');
            $table->binary('srp__b')->nullable();
            $table->bigInteger('srp_id')->nullable();
            $table->index('srp_id', 'ix_6200226f0e32fd1fe5872c13');
            $table->text('hint')->nullable();
            $table->text('email_unconfirmed_pattern')->nullable();
            $table->bigInteger('new_algo')->nullable();
            $table->index('new_algo', 'ix_2453cb4d520fb1db207519ce');
            $table->bigInteger('new_secure_algo')->nullable();
            $table->index('new_secure_algo', 'ix_f0c4fb49e95b5ef94e7ca8d4');
            $table->binary('secure_random')->nullable();
            $table->integer('pending_reset_date')->nullable();
            $table->text('login_email_pattern')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('constructor_id', 'ix_f84f34bd5871e65511756506');
            $table->index('account_id', 'ix_30241ef78985c71f785d7ec2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_password');
    }
};
