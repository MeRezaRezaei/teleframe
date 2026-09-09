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
            $table->index('constructor_id', 'ix_8b2dc3023bbd3ebf8763f793');
            $table->index('account_id', 'ix_e4ac815946a7de2f3d43a904');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_change', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_abac247c747d3a839c824139');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_login_setup', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->text('phone_number');
            $table->text('phone_code_hash');
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_cda727acbd5aba7869b33eb6');
        });
        Schema::create('tl_email_verify_purpose_email_verify_purpose_passport', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_email_verify_purpose')->cascadeOnDelete();
            $table->bigInteger('account_id');
            $table->timestamps();
            $table->index('account_id', 'ix_36aed09093468eefcce25f3b');
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
