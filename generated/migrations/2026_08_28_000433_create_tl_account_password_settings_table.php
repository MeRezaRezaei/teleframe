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
        Schema::create('tl_account_password_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_281c9fce1f116dc657d07218');
            $table->index('account_id', 'ix_d59b0b81b19d8373ca83a84e');
        });
        Schema::create('tl_account_password_settings_password_settings', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_password_settings')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->text('email')->nullable();
            $table->uuid('secure_settings')->nullable();
            $table->index('secure_settings', 'ix_c4f970ca366331fea399ef3e');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_51a56f659a75fb3c9fb9ca0e');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_password_settings_password_settings');
        Schema::dropIfExists('tl_account_password_settings');
    }
};
