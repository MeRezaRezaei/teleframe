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
        Schema::create('tl_email_verify_purpose', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_change', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_setup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('phone_code_hash');
            $table->timestamps();
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_passport');
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_login_setup');
        Schema::dropIfExists('tl_email_verify_purpose_email_verify_purpose_login_change');
        Schema::dropIfExists('tl_email_verify_purpose');
    }
};
