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
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_account_password_password', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_password')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->boolean('has_recovery')->default(false);
            $table->boolean('has_secure_values')->default(false);
            $table->boolean('has_password')->default(false);
            $table->uuid('current_algo')->nullable();
            $table->binary('srp__b')->nullable();
            $table->bigInteger('srp_id')->nullable();
            $table->text('hint')->nullable();
            $table->text('email_unconfirmed_pattern')->nullable();
            $table->uuid('new_algo');
            $table->uuid('new_secure_algo');
            $table->binary('secure_random');
            $table->integer('pending_reset_date')->nullable();
            $table->text('login_email_pattern')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_password');
        Schema::dropIfExists('tl_account_password');
    }
};
