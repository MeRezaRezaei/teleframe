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
        Schema::create('tl_account_password', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_28dc9e2d1dac664fbbc71c96');
            $table->index('account_id', 'ix_dd83eb30f376db36f6f4789e');
        });
        Schema::create('tl_account_password_password', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_password')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_recovery')->default(false);
            $table->boolean('has_secure_values')->default(false);
            $table->boolean('has_password')->default(false);
            $table->uuid('current_algo')->nullable();
            $table->index('current_algo', 'ix_de32a6ced0ddf7f8a9e55a85');
            $table->binary('srp__b')->nullable();
            $table->bigInteger('srp_id')->nullable();
            $table->index('srp_id', 'ix_6200226f0e32fd1fe5872c13');
            $table->text('hint')->nullable();
            $table->text('email_unconfirmed_pattern')->nullable();
            $table->uuid('new_algo');
            $table->index('new_algo', 'ix_2453cb4d520fb1db207519ce');
            $table->uuid('new_secure_algo');
            $table->index('new_secure_algo', 'ix_f0c4fb49e95b5ef94e7ca8d4');
            $table->binary('secure_random');
            $table->integer('pending_reset_date')->nullable();
            $table->text('login_email_pattern')->nullable();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_30241ef78985c71f785d7ec2');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_password');
        Schema::dropIfExists('tl_account_password');
    }
};
