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
        Schema::create('tl_email_verification', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id', 'ix_94ebfc76b4abfc539993d8f9');
            $table->index('account_id', 'ix_c00b91c02efcac0e383de02b');
        });
        Schema::create('tl_email_verification_email_verification_apple', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verification')->cascadeOnDelete();
            $table->text('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_2eaeeae231f97049f3a86d86');
        });
        Schema::create('tl_email_verification_email_verification_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verification')->cascadeOnDelete();
            $table->text('code');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_41749a172b68a0a5ec0e3e19');
        });
        Schema::create('tl_email_verification_email_verification_google', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verification')->cascadeOnDelete();
            $table->text('token');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_4b016a498583b240eb35d235');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_email_verification_email_verification_google');
        Schema::dropIfExists('tl_email_verification_email_verification_code');
        Schema::dropIfExists('tl_email_verification_email_verification_apple');
        Schema::dropIfExists('tl_email_verification');
    }
};
