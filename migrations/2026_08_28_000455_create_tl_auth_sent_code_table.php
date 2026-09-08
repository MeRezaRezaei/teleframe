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
        Schema::create('tl_auth_sent_code', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('constructor_id'); // crc32, may exceed signed i32
            $table->string('constructor_name', 96);
            $table->bigInteger('account_id'); // tenant (roadmap: account_id on every anchor)
            $table->timestamps();
            $table->index('constructor_id');
            $table->index('account_id');
        });
        Schema::create('tl_auth_sent_code_sent_code', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->bigInteger('flags')->nullable();
            $table->uuid('tl_type');
            $table->text('phone_code_hash');
            $table->uuid('next_type')->nullable();
            $table->integer('timeout')->nullable();
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_sent_code_payment_required', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->text('store_product');
            $table->text('phone_code_hash');
            $table->text('support_email_address');
            $table->text('support_email_subject');
            $table->integer('premium_days');
            $table->text('currency');
            $table->bigInteger('amount');
            $table->timestamps();
        });
        Schema::create('tl_auth_sent_code_sent_code_success', function (Blueprint $table) {
            $table->foreignUuid('id')->primary()->constrained('tl_auth_sent_code')->cascadeOnDelete();
            $table->uuid('tl_authorization');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tl_auth_sent_code_sent_code_success');
        Schema::dropIfExists('tl_auth_sent_code_sent_code_payment_required');
        Schema::dropIfExists('tl_auth_sent_code_sent_code');
        Schema::dropIfExists('tl_auth_sent_code');
    }
};
