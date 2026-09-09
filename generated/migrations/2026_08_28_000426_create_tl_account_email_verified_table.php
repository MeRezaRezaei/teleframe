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
        Schema::create('tl_account_email_verified', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_0a1f9c0c6e6e558f728cf4ad');
            $table->index('account_id', 'ix_5b0280a12cad2c65817ea7b4');
        });
        Schema::create('tl_account_email_verified_email_verified', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_email_verified')->cascadeOnDelete();
            $table->text('email');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_b18360f14c4d09fe33709d9e');
        });
        Schema::create('tl_account_email_verified_email_verified_login', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_account_email_verified')->cascadeOnDelete();
            $table->text('email');
            $table->uuid('sent_code');
            $table->index('sent_code', 'ix_0fb3a311104dca7cb6ef2492');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_ede1791e604c78ae8d7a0c42');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_account_email_verified_email_verified_login');
        Schema::dropIfExists('tl_account_email_verified_email_verified');
        Schema::dropIfExists('tl_account_email_verified');
    }
};
